<?php

namespace Cesa\Rekrutmen\Services;

use Cesa\Rekrutmen\Enums\WhatsAppAccountStatus;
use Cesa\Rekrutmen\Models\WhatsAppAccount;
use Cesa\Rekrutmen\Models\WhatsAppSetting;
use Illuminate\Support\Facades\Log;
use Throwable;

class WhatsAppGateway
{
    public function __construct(
        protected WhatsAppEngineClient $engine,
        protected WhatsAppEngineProcess $process,
    ) {}

    /**
     * @param  array{purpose?: string, mode?: string, client_reference?: string, idempotency_key?: string}  $options
     * @return array{success: bool, message: string, phone?: string, data?: mixed, account_id?: int|null, session_id?: string}
     */
    public function sendText(?WhatsAppAccount $account, string $phone, string $message, array $options = []): array
    {
        if (! $this->isEnabled()) {
            return [
                'success' => false,
                'message' => 'Pengiriman WhatsApp rekrutmen sedang nonaktif.',
            ];
        }

        if (! $account) {
            return [
                'success' => false,
                'message' => 'Belum ada nomor WhatsApp yang terhubung. Scan QR di Pengaturan Rekrutmen.',
            ];
        }

        if (! $this->ensureEngine()) {
            return [
                'success'    => false,
                'message'    => 'Engine WhatsApp rekrutmen belum berjalan. Jalankan: php artisan rekrutmen:whatsapp-engine',
                'account_id' => $account->id,
            ];
        }

        try {
            $payload = $this->sendWithRetry($account, $phone, $message);
            $account->markConnected($account->phone_number);

            return [
                'success'    => true,
                'message'    => 'Pesan WhatsApp berhasil dikirim ke '.$phone,
                'phone'      => $phone,
                'data'       => $payload,
                'account_id' => $account->id,
                'session_id' => $account->sessionId(),
            ];
        } catch (Throwable $e) {
            $this->rememberSendFailure($account, $e);

            Log::error('Rekrutmen WhatsApp engine failed to send.', [
                'account_id' => $account->id,
                'phone'      => $phone,
                'error'      => $e->getMessage(),
            ]);

            return [
                'success'    => false,
                'message'    => $e->getMessage(),
                'phone'      => $phone,
                'account_id' => $account->id,
                'session_id' => $account->sessionId(),
            ];
        }
    }

    /**
     * @return array{success: bool, message: string, data?: array<string, mixed>}
     */
    public function connect(WhatsAppAccount $account, ?string $phone = null): array
    {
        if (! $this->ensureEngine()) {
            return [
                'success' => false,
                'message' => 'Engine WhatsApp belum siap. Pastikan Node.js terpasang, lalu jalankan php artisan rekrutmen:whatsapp-engine.',
            ];
        }

        try {
            $sessionPhone = $phone ?: $account->phone_number;
            $session = $this->engine->startSession(
                $account->sessionId(),
                $sessionPhone,
            );

            if (filled($sessionPhone) && empty($session['pairing_code'])) {
                for ($attempt = 0; $attempt < 8; $attempt++) {
                    usleep(400000);
                    $session = $this->engine->session($account->sessionId());

                    if (! empty($session['pairing_code']) || ($session['status'] ?? null) === 'connected') {
                        break;
                    }
                }
            }

            $this->syncAccount($account, $session);

            return [
                'success' => true,
                'message' => 'Scan QR WhatsApp atau masukkan kode pairing di HP.',
                'data'    => $this->sessionPayload($account, $session),
            ];
        } catch (Throwable $e) {
            $account->markDisconnected($e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function session(WhatsAppAccount $account): array
    {
        if (! $this->engine->isReady()) {
            return $this->sessionPayload($account, [
                'status' => $account->status?->value ?? WhatsAppAccountStatus::Disconnected->value,
                'error'  => 'Engine WhatsApp belum berjalan.',
            ]);
        }

        try {
            $session = $this->engine->session($account->sessionId());
            $this->syncAccount($account, $session);

            return $this->sessionPayload($account->fresh() ?? $account, $session);
        } catch (Throwable $e) {
            return $this->sessionPayload($account, [
                'status' => WhatsAppAccountStatus::Disconnected->value,
                'error'  => $e->getMessage(),
            ]);
        }
    }

    public function disconnect(WhatsAppAccount $account): void
    {
        try {
            if ($this->engine->isReady()) {
                $this->engine->logout($account->sessionId());
            }
        } catch (Throwable) {
            // The local record should still be marked disconnected.
        }

        $account->markDisconnected('Nomor diputuskan dari CESA.');
    }

    /**
     * @return array{success: bool, message: string}
     */
    public function testAccount(WhatsAppAccount $account, ?string $recipient = null): array
    {
        $phone = $this->formatPhone($recipient ?: (string) $account->phone_number);

        if (! $phone) {
            return [
                'success' => false,
                'message' => 'Nomor tujuan tes tidak valid.',
            ];
        }

        $result = $this->sendText($account, $phone, 'CESA Rekrutmen: tes koneksi WhatsApp berhasil.');

        return [
            'success' => $result['success'],
            'message' => $result['success']
                ? 'Koneksi berhasil. Pesan tes terkirim ke '.$phone.'.'
                : ($result['message'] ?? 'Koneksi WhatsApp gagal.'),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function saveSettings(array $payload): WhatsAppSetting
    {
        $setting = WhatsAppSetting::query()->first() ?? new WhatsAppSetting;
        $setting->fill($payload);
        $setting->save();

        return $setting->fresh() ?? $setting;
    }

    public function isEnabled(): bool
    {
        $stored = WhatsAppSetting::query()->first();

        if ($stored) {
            return (bool) $stored->enabled;
        }

        return (bool) config('rekrutmen.notifications.whatsapp.enabled', true);
    }

    public function engineReady(): bool
    {
        return $this->engine->isReady();
    }

    public function formatPhone(?string $phone): ?string
    {
        if (! is_string($phone)) {
            return null;
        }

        $trimmed = trim($phone);

        if ($trimmed === '') {
            return null;
        }

        $digits = preg_replace('/[^\d]/', '', $trimmed);

        if (! is_string($digits) || strlen($digits) < 8) {
            return null;
        }

        if (str_starts_with($digits, '62')) {
            return $digits;
        }

        if (str_starts_with($digits, '0')) {
            return '62'.substr($digits, 1);
        }

        if (str_starts_with($digits, '8')) {
            return '62'.$digits;
        }

        return $digits;
    }

    protected function ensureEngine(): bool
    {
        try {
            return $this->process->ensureRunning();
        } catch (Throwable $e) {
            Log::warning('Failed to auto-start rekrutmen WhatsApp engine.', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * @param  array<string, mixed>  $session
     */
    protected function syncAccount(WhatsAppAccount $account, array $session): void
    {
        $status = $this->mapStatus($session['status'] ?? null);
        $phone = $this->formatPhone(isset($session['phone']) ? (string) $session['phone'] : null);
        $error = isset($session['error']) ? (string) $session['error'] : null;

        if (
            $account->status === WhatsAppAccountStatus::Connected
            && in_array($status, [WhatsAppAccountStatus::Disconnected, WhatsAppAccountStatus::Unknown], true)
            && ! $this->isSessionDeadMessage((string) $error)
        ) {
            $account->forceFill([
                'last_checked_at' => now(),
                'last_error'      => $error,
            ])->save();

            return;
        }

        $account->forceFill([
            'status'          => $status,
            'phone_number'    => $phone ?: $account->phone_number,
            'last_checked_at' => now(),
            'last_error'      => $status === WhatsAppAccountStatus::Connected ? null : $error,
        ])->save();
    }

    protected function mapStatus(mixed $status): WhatsAppAccountStatus
    {
        return match ($status) {
            'qr'          => WhatsAppAccountStatus::Qr,
            'pairing'     => WhatsAppAccountStatus::Pairing,
            'connecting'  => WhatsAppAccountStatus::Connecting,
            'connected'   => WhatsAppAccountStatus::Connected,
            'disconnected'=> WhatsAppAccountStatus::Disconnected,
            default       => WhatsAppAccountStatus::Unknown,
        };
    }

    /**
     * @param  array<string, mixed>  $session
     * @return array<string, mixed>
     */
    protected function sessionPayload(WhatsAppAccount $account, array $session): array
    {
        return array_merge($account->toApiArray(), [
            'qr'            => $session['qr'] ?? null,
            'pairing_code'  => $session['pairing_code'] ?? null,
            'engine_ready'  => $this->engine->isReady(),
            'engine_error'  => $session['error'] ?? $account->last_error,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function sendWithRetry(WhatsAppAccount $account, string $phone, string $message): array
    {
        try {
            return $this->engine->sendText($account->sessionId(), $phone, $message);
        } catch (Throwable $e) {
            if (! $this->isRetryableSendError($e)) {
                throw $e;
            }

            $this->ensureEngine();

            try {
                $this->engine->startSession($account->sessionId());
            } catch (Throwable) {
                // Restore is best-effort; the retry below is the source of truth.
            }

            return $this->engine->sendText($account->sessionId(), $phone, $message);
        }
    }

    protected function rememberSendFailure(WhatsAppAccount $account, Throwable $e): void
    {
        if ($this->isSessionDeadMessage($e->getMessage())) {
            $account->markDisconnected($e->getMessage());

            return;
        }

        $account->forceFill([
            'last_error'      => $e->getMessage(),
            'last_checked_at' => now(),
        ])->save();
    }

    protected function isRetryableSendError(Throwable $e): bool
    {
        $message = strtolower($e->getMessage());

        return str_contains($message, 'timed out')
            || str_contains($message, 'timeout')
            || str_contains($message, 'connection')
            || str_contains($message, 'refused')
            || str_contains($message, 'unavailable')
            || str_contains($message, 'not found')
            || str_contains($message, 'belum terhubung');
    }

    protected function isSessionDeadMessage(string $message): bool
    {
        $normalized = strtolower($message);

        return str_contains($normalized, 'logout')
            || str_contains($normalized, 'logged out')
            || str_contains($normalized, 'sesi rusak')
            || str_contains($normalized, 'sesi dihapus')
            || str_contains($normalized, 'scan qr')
            || str_contains($normalized, 'kode pairing')
            || str_contains($normalized, 'belum terhubung');
    }
}
