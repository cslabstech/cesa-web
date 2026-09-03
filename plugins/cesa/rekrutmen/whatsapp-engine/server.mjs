import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import pino from 'pino';
import QRCode from 'qrcode';
import makeWASocket, {
    Browsers,
    DisconnectReason,
    fetchLatestBaileysVersion,
    makeCacheableSignalKeyStore,
    useMultiFileAuthState,
} from '@whiskeysockets/baileys';

const PORT = Number(process.env.REKRUTMEN_WA_ENGINE_PORT || 3318);
const HOST = process.env.REKRUTMEN_WA_ENGINE_HOST || '127.0.0.1';
const SESSION_ROOT = process.env.REKRUTMEN_WA_SESSION_ROOT
    || path.join(path.dirname(fileURLToPath(import.meta.url)), 'sessions');
const MAX_RECONNECT = Number(process.env.REKRUTMEN_WA_MAX_RECONNECT || 8);
const startedAt = Date.now();

const logger = pino({ level: process.env.REKRUTMEN_WA_LOG_LEVEL || 'error' });
const sessions = new Map();
const starting = new Map();
let cachedVersion = null;

function sessionDir(id) {
    return path.join(SESSION_ROOT, sanitizeId(id));
}

function sanitizeId(id) {
    return String(id).replace(/[^a-zA-Z0-9._-]/g, '_');
}

function delay(ms) {
    return new Promise((resolve) => setTimeout(resolve, ms));
}

async function waitUntil(predicate, timeoutMs) {
    const started = Date.now();

    while (Date.now() - started < timeoutMs) {
        if (predicate()) {
            return true;
        }

        await delay(200);
    }

    return false;
}

function formatPhone(phone) {
    let digits = String(phone || '').replace(/[^\d]/g, '');

    if (digits.startsWith('0')) {
        digits = '62' + digits.slice(1);
    } else if (digits.startsWith('8')) {
        digits = '62' + digits;
    }

    return digits;
}

function formatPairingCode(code) {
    const raw = String(code || '').replace(/[^A-Za-z0-9]/g, '').toUpperCase();

    if (raw.length === 8) {
        return raw.slice(0, 4) + '-' + raw.slice(4);
    }

    return String(code || '');
}

function jidToPhone(jid) {
    if (!jid || typeof jid !== 'string') {
        return null;
    }

    return jid.split('@')[0].split(':')[0] || null;
}

function hasAuth(id) {
    return fs.existsSync(path.join(sessionDir(id), 'creds.json'));
}

function clearAuth(id) {
    fs.rmSync(sessionDir(id), { recursive: true, force: true });
}

function reconnectDelay(attempt) {
    return Math.min(30000, 1000 * (2 ** Math.max(0, attempt)));
}

function disconnectCode(lastDisconnect) {
    return lastDisconnect?.error?.output?.statusCode
        ?? lastDisconnect?.error?.statusCode
        ?? null;
}

function shouldClearAuth(code) {
    return code === DisconnectReason.loggedOut
        || code === DisconnectReason.badSession
        || code === DisconnectReason.multideviceMismatch
        || code === DisconnectReason.forbidden;
}

function shouldReconnect(code) {
    if (code === DisconnectReason.loggedOut
        || code === DisconnectReason.badSession
        || code === DisconnectReason.connectionReplaced
        || code === DisconnectReason.multideviceMismatch
        || code === DisconnectReason.forbidden) {
        return false;
    }

    return true;
}

function disconnectMessage(code) {
    if (code === DisconnectReason.loggedOut) {
        return 'Sesi WhatsApp logout. Scan QR atau minta kode pairing baru.';
    }

    if (code === DisconnectReason.connectionReplaced) {
        return 'Sesi digantikan perangkat lain. Jangan jalankan engine dobel.';
    }

    if (code === DisconnectReason.badSession) {
        return 'Sesi rusak. Hubungkan ulang WhatsApp.';
    }

    if (code === DisconnectReason.multideviceMismatch) {
        return 'Perangkat tidak cocok. Hubungkan ulang WhatsApp.';
    }

    return 'Koneksi WhatsApp terputus.';
}

function getOrCreateRecord(id) {
    if (!sessions.has(id)) {
        sessions.set(id, {
            id,
            status: 'disconnected',
            qr: null,
            pairingCode: null,
            phone: null,
            error: null,
            sock: null,
            generation: 0,
            stopping: false,
            reconnectAttempt: 0,
            pairingRequested: false,
        });
    }

    return sessions.get(id);
}

function publicSession(record) {
    return {
        ok: true,
        id: record.id,
        status: record.status,
        qr: record.qr,
        pairing_code: record.pairingCode,
        phone: record.phone,
        error: record.error,
        reconnect_attempt: record.reconnectAttempt || 0,
    };
}

async function baileysVersion() {
    if (cachedVersion) {
        return cachedVersion;
    }

    try {
        const { version } = await fetchLatestBaileysVersion();
        if (Array.isArray(version)) {
            cachedVersion = version;
        }
    } catch (error) {
        logger.warn({ err: error.message }, 'Gagal mengambil versi WA Web, pakai default Baileys.');
    }

    return cachedVersion;
}

async function endSocket(sock) {
    if (!sock) {
        return;
    }

    try {
        sock.ev?.removeAllListeners?.();
    } catch {
        // ignore
    }

    try {
        sock.end(undefined);
    } catch {
        // ignore
    }

    try {
        sock.ws?.close?.();
    } catch {
        // ignore
    }
}

async function startSession(id, phone = null) {
    const pending = starting.get(id);
    if (pending) {
        await pending.catch(() => {});
        const current = sessions.get(id);
        if (current && (!phone || current.pairingCode || current.status === 'connected')) {
            return current;
        }
    }

    const run = startSessionUnlocked(id, phone).finally(() => {
        if (starting.get(id) === run) {
            starting.delete(id);
        }
    });
    starting.set(id, run);

    return run;
}

async function startSessionUnlocked(id, phone = null) {
    const record = getOrCreateRecord(id);
    const pairingPhone = phone ? formatPhone(phone) : null;

    if (record.sock && record.status === 'connected' && !record.stopping) {
        return record;
    }

    if (record.sock && pairingPhone && record.pairingCode && !record.stopping) {
        return record;
    }

    if (record.sock && !pairingPhone && ['qr', 'connecting', 'pairing'].includes(record.status) && !record.stopping) {
        return record;
    }

    record.stopping = true;
    await endSocket(record.sock);
    record.sock = null;
    record.stopping = false;

    fs.mkdirSync(sessionDir(id), { recursive: true });

    let state;
    let saveCreds;
    try {
        ({ state, saveCreds } = await useMultiFileAuthState(sessionDir(id)));
    } catch (error) {
        logger.error({ id, err: error.message }, 'Auth state rusak, menghapus sesi.');
        clearAuth(id);
        fs.mkdirSync(sessionDir(id), { recursive: true });
        ({ state, saveCreds } = await useMultiFileAuthState(sessionDir(id)));
    }

    const version = await baileysVersion();
    const generation = (record.generation || 0) + 1;

    record.generation = generation;
    record.status = pairingPhone && !state.creds?.registered ? 'pairing' : (state.creds?.registered ? 'connecting' : 'connecting');
    record.qr = null;
    record.pairingCode = null;
    record.error = null;
    record.pairingRequested = false;
    record.phone = pairingPhone || record.phone;
    record.stopping = false;

    const sock = makeWASocket({
        version: version || undefined,
        auth: {
            creds: state.creds,
            keys: makeCacheableSignalKeyStore(state.keys, logger),
        },
        logger,
        printQRInTerminal: false,
        browser: Browsers.ubuntu('Chrome'),
        syncFullHistory: false,
        markOnlineOnConnect: false,
        connectTimeoutMs: 30_000,
        keepAliveIntervalMs: 15_000,
        retryRequestDelayMs: 500,
        emitOwnEvents: false,
    });

    record.sock = sock;
    sock.ev.on('creds.update', saveCreds);

    const isCurrent = () => record.generation === generation && record.sock === sock;

    const requestPairing = async () => {
        if (!isCurrent() || !pairingPhone || record.pairingRequested || state.creds?.registered) {
            return;
        }

        record.pairingRequested = true;

        try {
            await delay(1500);
            if (!isCurrent()) {
                return;
            }

            const code = await sock.requestPairingCode(pairingPhone);
            if (!isCurrent()) {
                return;
            }

            record.pairingCode = formatPairingCode(code);
            record.status = 'pairing';
            record.qr = null;
            record.error = null;
        } catch (error) {
            if (!isCurrent()) {
                return;
            }

            record.error = error.message || 'Gagal membuat kode pairing.';
            record.pairingRequested = false;
        }
    };

    sock.ev.on('connection.update', async (update) => {
        if (!isCurrent()) {
            return;
        }

        const { connection, lastDisconnect, qr } = update;

        if (pairingPhone && !record.pairingCode) {
            requestPairing().catch(() => {});
        }

        if (qr && !pairingPhone) {
            try {
                record.qr = await QRCode.toDataURL(qr, { margin: 1, width: 280 });
                record.status = 'qr';
                record.error = null;
            } catch (error) {
                record.error = error.message;
            }
        }

        if (connection === 'open') {
            record.status = 'connected';
            record.qr = null;
            record.pairingCode = null;
            record.phone = jidToPhone(sock.user?.id) || pairingPhone || record.phone;
            record.error = null;
            record.reconnectAttempt = 0;
            logger.info({ id, phone: record.phone }, 'WhatsApp terhubung.');
        }

        if (connection === 'close') {
            const code = disconnectCode(lastDisconnect);

            if (record.stopping || !isCurrent()) {
                return;
            }

            record.sock = null;

            if (shouldClearAuth(code)) {
                record.status = 'disconnected';
                record.qr = null;
                record.pairingCode = null;
                record.error = disconnectMessage(code);
                record.reconnectAttempt = 0;
                clearAuth(id);
                logger.warn({ id, code }, 'Sesi dihapus karena logout/rusak.');
                return;
            }

            if (!shouldReconnect(code)) {
                record.status = 'disconnected';
                record.qr = null;
                record.pairingCode = null;
                record.error = disconnectMessage(code);
                logger.warn({ id, code }, 'Tidak reconnect.');
                return;
            }

            record.reconnectAttempt = (record.reconnectAttempt || 0) + 1;

            if (record.reconnectAttempt > MAX_RECONNECT) {
                record.status = 'disconnected';
                record.error = 'Koneksi putus berulang. Hubungkan ulang WhatsApp.';
                logger.error({ id, attempt: record.reconnectAttempt }, 'Batas reconnect tercapai.');
                return;
            }

            record.status = 'connecting';
            record.error = disconnectMessage(code);
            const wait = code === DisconnectReason.restartRequired ? 500 : reconnectDelay(record.reconnectAttempt);
            logger.warn({ id, code, wait, attempt: record.reconnectAttempt }, 'Reconnect WhatsApp.');

            setTimeout(() => {
                if (record.stopping || record.generation !== generation) {
                    return;
                }

                startSession(id, pairingPhone).catch((error) => {
                    if (record.generation !== generation) {
                        return;
                    }

                    record.status = 'disconnected';
                    record.error = error.message;
                });
            }, wait);
        }
    });

    if (pairingPhone && !state.creds?.registered) {
        requestPairing().catch(() => {});
        await waitUntil(() => Boolean(record.pairingCode) || Boolean(record.error) || record.status === 'connected', 12000);
    }

    return record;
}

async function stopSession(id, logout = false) {
    const record = sessions.get(id);
    if (record) {
        record.stopping = true;
        record.generation = (record.generation || 0) + 1;
        await endSocket(record.sock);
        record.sock = null;
        record.status = 'disconnected';
        record.qr = null;
        record.pairingCode = null;
    }

    sessions.delete(id);
    starting.delete(id);

    if (logout) {
        clearAuth(id);
    }
}

async function sendText(id, phone, text) {
    let record = sessions.get(id);

    if ((!record?.sock || record.status !== 'connected') && hasAuth(id)) {
        await startSession(id);
        await waitUntil(() => sessions.get(id)?.status === 'connected', 10000);
        record = sessions.get(id);
    }

    if (record?.status === 'connecting') {
        await waitUntil(() => record.status === 'connected' || record.status === 'disconnected', 8000);
    }

    if (!record?.sock || record.status !== 'connected') {
        const error = new Error('Nomor WhatsApp belum terhubung. Scan QR atau minta kode pairing di pengaturan rekrutmen.');
        error.statusCode = 409;
        throw error;
    }

    const digits = formatPhone(phone);
    if (digits.length < 8) {
        const error = new Error('Nomor tujuan WhatsApp tidak valid.');
        error.statusCode = 422;
        throw error;
    }

    const jid = `${digits}@s.whatsapp.net`;

    try {
        const result = await record.sock.sendMessage(jid, { text });

        return { ok: true, status: 'sent', id: result?.key?.id ?? null };
    } catch (error) {
        logger.warn({ id, err: error.message }, 'Kirim gagal, mencoba sekali lagi.');
        await delay(800);

        if (record.status !== 'connected' && hasAuth(id)) {
            await startSession(id);
            await waitUntil(() => sessions.get(id)?.status === 'connected', 8000);
            record = sessions.get(id);
        }

        if (!record?.sock || record.status !== 'connected') {
            throw error;
        }

        const result = await record.sock.sendMessage(jid, { text });

        return { ok: true, status: 'sent', retry: true, id: result?.key?.id ?? null };
    }
}

async function restoreSessions() {
    if (!fs.existsSync(SESSION_ROOT)) {
        fs.mkdirSync(SESSION_ROOT, { recursive: true });
        return;
    }

    const entries = fs.readdirSync(SESSION_ROOT, { withFileTypes: true });
    for (const entry of entries) {
        if (!entry.isDirectory() || !hasAuth(entry.name)) {
            continue;
        }

        startSession(entry.name).catch((error) => {
            logger.error({ id: entry.name, err: error.message }, 'Gagal restore sesi.');
        });
    }
}

function readJson(req) {
    return new Promise((resolve, reject) => {
        const chunks = [];
        let size = 0;

        req.on('data', (chunk) => {
            size += chunk.length;
            if (size > 1_000_000) {
                reject(new Error('Payload terlalu besar.'));
                return;
            }
            chunks.push(chunk);
        });

        req.on('end', () => {
            if (chunks.length === 0) {
                resolve({});
                return;
            }

            try {
                resolve(JSON.parse(Buffer.concat(chunks).toString('utf8')));
            } catch (error) {
                reject(error);
            }
        });

        req.on('error', reject);
    });
}

function send(res, status, payload) {
    const body = JSON.stringify(payload);
    res.writeHead(status, {
        'Content-Type': 'application/json',
        'Content-Length': Buffer.byteLength(body),
    });
    res.end(body);
}

function match(pathname, pattern) {
    const keys = [];
    const regex = new RegExp('^' + pattern.replace(/:([a-zA-Z_]+)/g, (_, key) => {
        keys.push(key);
        return '([^/]+)';
    }) + '$');
    const found = pathname.match(regex);
    if (!found) {
        return null;
    }

    const params = {};
    keys.forEach((key, index) => {
        params[key] = decodeURIComponent(found[index + 1]);
    });

    return params;
}

const server = http.createServer(async (req, res) => {
    try {
        const url = new URL(req.url || '/', `http://${HOST}:${PORT}`);
        const pathname = url.pathname.replace(/\/+$/, '') || '/';

        if (req.method === 'GET' && pathname === '/health') {
            const list = [...sessions.values()];
            send(res, 200, {
                ok: true,
                uptime_ms: Date.now() - startedAt,
                sessions: list.length,
                connected: list.filter((item) => item.status === 'connected').length,
                pid: process.pid,
            });
            return;
        }

        if (req.method === 'POST' && pathname === '/sessions') {
            const payload = await readJson(req);
            const id = sanitizeId(payload.id || '');
            if (!id) {
                send(res, 422, { ok: false, message: 'ID sesi wajib diisi.' });
                return;
            }

            const record = await startSession(id, payload.phone || null);
            send(res, 200, publicSession(record));
            return;
        }

        const sessionParams = match(pathname, '/sessions/:id');
        if (sessionParams && req.method === 'GET') {
            const record = sessions.get(sessionParams.id) || getOrCreateRecord(sessionParams.id);
            send(res, 200, publicSession(record));
            return;
        }

        if (sessionParams && req.method === 'DELETE') {
            const payload = await readJson(req).catch(() => ({}));
            await stopSession(sessionParams.id, payload.logout !== false);
            send(res, 200, { ok: true, status: 'disconnected' });
            return;
        }

        const sendParams = match(pathname, '/sessions/:id/send');
        if (sendParams && req.method === 'POST') {
            const payload = await readJson(req);
            if (!payload.phone || !payload.text) {
                send(res, 422, { ok: false, message: 'Nomor tujuan dan isi pesan wajib diisi.' });
                return;
            }

            const result = await sendText(sendParams.id, payload.phone, payload.text);
            send(res, 200, result);
            return;
        }

        send(res, 404, { ok: false, message: 'Not found' });
    } catch (error) {
        logger.error({ err: error.message }, 'Engine gagal memproses permintaan.');
        send(res, error.statusCode || 500, {
            ok: false,
            message: error.message || 'Engine WhatsApp gagal memproses permintaan.',
        });
    }
});

async function shutdown(signal) {
    logger.info({ signal }, 'Engine WhatsApp berhenti.');
    for (const id of [...sessions.keys()]) {
        const record = sessions.get(id);
        if (record) {
            record.stopping = true;
            record.generation = (record.generation || 0) + 1;
            await endSocket(record.sock);
        }
    }

    server.close(() => process.exit(0));
    setTimeout(() => process.exit(0), 3000).unref();
}

process.on('SIGTERM', () => shutdown('SIGTERM'));
process.on('SIGINT', () => shutdown('SIGINT'));
process.on('unhandledRejection', (error) => {
    logger.error({ err: error?.message || String(error) }, 'unhandledRejection');
});
process.on('uncaughtException', (error) => {
    logger.error({ err: error.message }, 'uncaughtException');
});

await restoreSessions();

server.listen(PORT, HOST, () => {
    logger.info({ HOST, PORT, SESSION_ROOT, pid: process.pid }, 'Rekrutmen WhatsApp engine ready');
});
