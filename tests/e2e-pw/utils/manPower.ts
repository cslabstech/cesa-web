import { execFileSync } from "node:child_process";
import fs from "node:fs";
import os from "node:os";
import path from "node:path";
import { fileURLToPath } from "node:url";

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const REPO_ROOT = path.resolve(__dirname, "../../..");
const AUTOLOAD_PATH = path.join(REPO_ROOT, "vendor/autoload.php");
const BOOTSTRAP_APP_PATH = path.join(REPO_ROOT, "bootstrap/app.php");
const PHP_BINARY_PATH = process.env.PHP_BINARY ?? "php";

export type ManPowerRequestMetadata = {
    requestId: number;
    responseId: string;
    progressPath: string;
    status: string;
};

export function ensureManPowerPipelineFixture(): void {
    const script = `<?php
require ${JSON.stringify(AUTOLOAD_PATH)};
$app = require ${JSON.stringify(BOOTSTRAP_APP_PATH)};
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

$pipeline = Cesa\\Rekrutmen\\Models\\RekrutmenPipeline::query()->firstOrCreate(
    ['name' => 'Default Recruitment Pipeline'],
    ['description' => 'Pipeline standar proses rekrutmen.'],
);

$stages = [
    1 => 'Screening CV',
    2 => 'Interview HR',
    3 => 'Interview User',
    4 => 'Offering',
    5 => 'Hired',
];

foreach ($stages as $order => $name) {
    $pipeline->stages()->updateOrCreate(
        ['name' => $name],
        ['order_column' => $order],
    );
}

echo json_encode(['ok' => true], JSON_THROW_ON_ERROR);
`;

    runPhpScript(script);
}

export function getManPowerRequestMetadata(email: string): ManPowerRequestMetadata {
    const script = `<?php
require ${JSON.stringify(AUTOLOAD_PATH)};
$app = require ${JSON.stringify(BOOTSTRAP_APP_PATH)};
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

$email = $argv[1] ?? null;

if (! $email) {
    fwrite(STDERR, "Missing email\\n");
    exit(1);
}

$request = Cesa\\Rekrutmen\\Models\\RequestManPower::query()
    ->where('email_address', $email)
    ->latest('id')
    ->first();

if (! $request) {
    fwrite(STDERR, "Man power request not found\\n");
    exit(1);
}

$progressUrl = route('rekrutmen.public.request-man-power.progress', ['response' => $request->status_response_id]);
$path = parse_url($progressUrl, PHP_URL_PATH) ?? '';
$query = parse_url($progressUrl, PHP_URL_QUERY);
$progressPath = $query ? $path . '?' . $query : $path;

echo json_encode([
    'requestId' => $request->id,
    'responseId' => $request->status_response_id,
    'progressPath' => $progressPath,
    'status' => $request->status?->value ?? (string) $request->status,
], JSON_THROW_ON_ERROR);
`;

    return runPhpScript<ManPowerRequestMetadata>(script, [email]);
}

export function setManPowerRequestStatus(
    requestId: number,
    status: "pending" | "approved" | "rejected",
): string {
    const script = `<?php
require ${JSON.stringify(AUTOLOAD_PATH)};
$app = require ${JSON.stringify(BOOTSTRAP_APP_PATH)};
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

[$requestId, $status] = array_pad(array_slice($argv, 1), 2, null);

if (! $requestId || ! $status) {
    fwrite(STDERR, "Missing request status arguments\\n");
    exit(1);
}

$request = Cesa\\Rekrutmen\\Models\\RequestManPower::query()->find($requestId);

if (! $request) {
    fwrite(STDERR, "Request man power not found\\n");
    exit(1);
}

$statusEnum = Cesa\\Rekrutmen\\Enums\\RequestManPowerStatus::from($status);

$request->update([
    'status' => $statusEnum,
]);

echo json_encode($request->status?->value ?? (string) $request->status, JSON_THROW_ON_ERROR);
`;

    return runPhpScript<string>(script, [String(requestId), status]);
}

function runPhpScript<T = { ok: boolean }>(script: string, args: string[] = []): T {
    const tempDir = fs.mkdtempSync(path.join(os.tmpdir(), "man-power-"));
    const scriptPath = path.join(tempDir, "script.php");

    try {
        fs.writeFileSync(scriptPath, script, "utf8");

        const output = execFileSync(PHP_BINARY_PATH, [scriptPath, ...args], {
            cwd: REPO_ROOT,
            encoding: "utf8",
        });

        try {
            return JSON.parse(output) as T;
        } catch (parseError) {
            const message = parseError instanceof Error ? parseError.message : String(parseError);

            throw new Error(`Invalid JSON output from PHP script.\n${message}\n\nOutput:\n${output}`);
        }
    } catch (error) {
        const stderr =
            typeof error === "object" && error !== null && "stderr" in error
                ? String(error.stderr)
                : "";
        const stdout =
            typeof error === "object" && error !== null && "stdout" in error
                ? String(error.stdout)
                : "";
        const message = error instanceof Error ? error.message : String(error);

        throw new Error(stderr || stdout || message || "Unknown PHP script failure.");
    } finally {
        fs.rmSync(tempDir, { recursive: true, force: true });
    }
}
