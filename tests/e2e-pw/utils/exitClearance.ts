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

export type ExitClearanceRequestMetadata = {
    requestId: number;
    uid: string;
    responseId: string;
    progressPath: string;
    approvalPath: string | null;
    approvalPaths: string[];
};

export type ExitClearanceResourcePermission = "global" | "individual" | "group";

export type ExitClearanceDepartmentFixture = {
    id: number;
    code: string;
    name: string;
};

export type ExitClearanceApproverFixture = {
    id: number;
    email: string;
    name: string;
};

export function createPublicExitClearanceRequest(input: {
    name: string;
    email: string;
    departmentCode?: string;
    departmentName?: string;
}): ExitClearanceRequestMetadata {
    const script = `<?php
require ${JSON.stringify(AUTOLOAD_PATH)};
$app = require ${JSON.stringify(BOOTSTRAP_APP_PATH)};
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

[$name, $email, $departmentCode, $departmentName] = array_pad(array_slice($argv, 1), 4, null);

if (! $name || ! $email) {
    fwrite(STDERR, "Missing public request arguments\\n");
    exit(1);
}

$department = null;

if ($departmentCode) {
    $department = Cesa\\ExitClearance\\Models\\Department::query()->firstOrCreate(
        ['code' => $departmentCode],
        ['name' => $departmentName ?: $departmentCode]
    );
}

$request = app(Cesa\\ExitClearance\\Services\\ExitClearanceRequestService::class)->createPublicRequest([
    'department_id' => $department?->id,
    'name' => $name,
    'email' => $email,
]);

$request->load(['approvers', 'department']);
$notificationService = app(Cesa\\ExitClearance\\Services\\ExitClearanceNotificationService::class);
$progressUrl = route('exit-clearance.public.progress', ['response' => $request->form_response_id]);
$approvalUrls = $request->approvers
    ->sortBy('id')
    ->values()
    ->map(fn ($approver) => $notificationService->buildApprovalUrl($request, $approver))
    ->all();

$toPath = function (?string $url): ?string {
    if (! $url) {
        return null;
    }

    $path = parse_url($url, PHP_URL_PATH) ?? '';
    $query = parse_url($url, PHP_URL_QUERY);

    return $query ? $path . '?' . $query : $path;
};

echo json_encode([
    'requestId' => $request->id,
    'uid' => $request->form_uid,
    'responseId' => $request->form_response_id,
    'progressPath' => $toPath($progressUrl),
    'approvalPath' => $toPath($approvalUrls[0] ?? null),
    'approvalPaths' => array_map($toPath, $approvalUrls),
], JSON_THROW_ON_ERROR);
`;

    return runPhpScript<ExitClearanceRequestMetadata>(script, [
        input.name,
        input.email,
        input.departmentCode ?? "",
        input.departmentName ?? "",
    ]);
}

function runPhpScript<T>(script: string, args: string[] = []): T {
    const tempDir = fs.mkdtempSync(path.join(os.tmpdir(), "exit-clearance-"));
    const scriptPath = path.join(tempDir, "script.php");

    try {
        fs.writeFileSync(scriptPath, script, "utf8");

        const output = execFileSync("php", [scriptPath, ...args], {
            cwd: REPO_ROOT,
            encoding: "utf8",
        });

        return JSON.parse(output) as T;
    } catch (error) {
        const stderr =
            typeof error === "object" && error !== null && "stderr" in error
                ? String(error.stderr)
                : "";
        const stdout =
            typeof error === "object" && error !== null && "stdout" in error
                ? String(error.stdout)
                : "";

        throw new Error(stderr || stdout || String(error));
    } finally {
        fs.rmSync(tempDir, { recursive: true, force: true });
    }
}

export function getExitClearanceRequestMetadata(email: string): ExitClearanceRequestMetadata {
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

$request = Cesa\\ExitClearance\\Models\\Request::query()
    ->with(['approvers', 'department'])
    ->where('email', $email)
    ->latest('id')
    ->first();

if (! $request) {
    fwrite(STDERR, "Request not found\\n");
    exit(1);
}

$approvers = $request->approvers->sortBy('id')->values();
$notificationService = app(Cesa\\ExitClearance\\Services\\ExitClearanceNotificationService::class);
$progressUrl = route('exit-clearance.public.progress', ['response' => $request->form_response_id]);
$approvalUrls = $approvers->map(fn ($approver) => $notificationService->buildApprovalUrl($request, $approver))->all();

$toPath = function (?string $url): ?string {
    if (! $url) {
        return null;
    }

    $path = parse_url($url, PHP_URL_PATH) ?? '';
    $query = parse_url($url, PHP_URL_QUERY);

    return $query ? $path . '?' . $query : $path;
};

echo json_encode([
    'requestId' => $request->id,
    'uid' => $request->form_uid,
    'responseId' => $request->form_response_id,
    'progressPath' => $toPath($progressUrl),
    'approvalPath' => $toPath($approvalUrls[0] ?? null),
    'approvalPaths' => array_map($toPath, $approvalUrls),
], JSON_THROW_ON_ERROR);
`;

    return runPhpScript<ExitClearanceRequestMetadata>(script, [email]);
}

export function getUserResourcePermission(email: string): ExitClearanceResourcePermission | null {
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

$user = Webkul\\Security\\Models\\User::query()->where('email', $email)->first();

echo json_encode($user?->resource_permission?->value, JSON_THROW_ON_ERROR);
`;

    return runPhpScript<ExitClearanceResourcePermission | null>(script, [email]);
}

export function setUserResourcePermission(
    email: string,
    permission: ExitClearanceResourcePermission
): ExitClearanceResourcePermission {
    const script = `<?php
require ${JSON.stringify(AUTOLOAD_PATH)};
$app = require ${JSON.stringify(BOOTSTRAP_APP_PATH)};
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

$email = $argv[1] ?? null;
$permission = $argv[2] ?? null;

if (! $email || ! $permission) {
    fwrite(STDERR, "Missing arguments\\n");
    exit(1);
}

$user = Webkul\\Security\\Models\\User::query()->where('email', $email)->first();

if (! $user) {
    fwrite(STDERR, "User not found\\n");
    exit(1);
}

$user->resource_permission = $permission;
$user->save();

echo json_encode($user->resource_permission?->value, JSON_THROW_ON_ERROR);
`;

    return runPhpScript<ExitClearanceResourcePermission>(script, [email, permission]);
}

export function upsertExitClearanceDepartment(input: {
    code: string;
    name: string;
    description?: string | null;
    createdByEmail?: string | null;
}): ExitClearanceDepartmentFixture {
    const script = `<?php
require ${JSON.stringify(AUTOLOAD_PATH)};
$app = require ${JSON.stringify(BOOTSTRAP_APP_PATH)};
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

[$code, $name, $description, $createdByEmail] = array_pad(array_slice($argv, 1), 4, null);

if (! $code || ! $name) {
    fwrite(STDERR, "Missing department arguments\\n");
    exit(1);
}

$creatorId = null;

if ($createdByEmail) {
    $creatorId = Webkul\\Security\\Models\\User::query()
        ->where('email', $createdByEmail)
        ->value('id');
}

$department = Cesa\\ExitClearance\\Models\\Department::query()->updateOrCreate(
    ['code' => $code],
    [
        'name' => $name,
        'description' => $description ?: null,
        'created_by' => $creatorId,
        'deleted_at' => null,
    ],
);

echo json_encode([
    'id' => $department->id,
    'code' => $department->code,
    'name' => $department->name,
], JSON_THROW_ON_ERROR);
`;

    return runPhpScript<ExitClearanceDepartmentFixture>(script, [
        input.code,
        input.name,
        input.description ?? "",
        input.createdByEmail ?? "",
    ]);
}

export function upsertExitClearanceApprover(input: {
    name: string;
    email: string;
    title: string;
    phone?: string | null;
    createdByEmail?: string | null;
}): ExitClearanceApproverFixture {
    const script = `<?php
require ${JSON.stringify(AUTOLOAD_PATH)};
$app = require ${JSON.stringify(BOOTSTRAP_APP_PATH)};
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

[$name, $email, $title, $phone, $createdByEmail] = array_pad(array_slice($argv, 1), 5, null);

if (! $name || ! $email || ! $title) {
    fwrite(STDERR, "Missing approver arguments\\n");
    exit(1);
}

$creatorId = null;

if ($createdByEmail) {
    $creatorId = Webkul\\Security\\Models\\User::query()
        ->where('email', $createdByEmail)
        ->value('id');
}

$approver = Cesa\\ExitClearance\\Models\\Approver::query()->updateOrCreate(
    ['email' => $email],
    [
        'name' => $name,
        'phone' => $phone ?: null,
        'title' => $title,
        'created_by' => $creatorId,
        'deleted_at' => null,
    ],
);

echo json_encode([
    'id' => $approver->id,
    'email' => $approver->email,
    'name' => $approver->name,
], JSON_THROW_ON_ERROR);
`;

    return runPhpScript<ExitClearanceApproverFixture>(script, [
        input.name,
        input.email,
        input.title,
        input.phone ?? "",
        input.createdByEmail ?? "",
    ]);
}

export function softDeleteExitClearanceDepartment(code: string): void {
    const script = `<?php
require ${JSON.stringify(AUTOLOAD_PATH)};
$app = require ${JSON.stringify(BOOTSTRAP_APP_PATH)};
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

$code = $argv[1] ?? null;

if (! $code) {
    fwrite(STDERR, "Missing department code\\n");
    exit(1);
}

$department = Cesa\\ExitClearance\\Models\\Department::query()->where('code', $code)->first();

if (! $department) {
    fwrite(STDERR, "Department not found\\n");
    exit(1);
}

if (! $department->trashed()) {
    $department->delete();
}

echo json_encode(true, JSON_THROW_ON_ERROR);
`;

    runPhpScript<boolean>(script, [code]);
}

export function softDeleteExitClearanceApprover(email: string): void {
    const script = `<?php
require ${JSON.stringify(AUTOLOAD_PATH)};
$app = require ${JSON.stringify(BOOTSTRAP_APP_PATH)};
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

$email = $argv[1] ?? null;

if (! $email) {
    fwrite(STDERR, "Missing approver email\\n");
    exit(1);
}

$approver = Cesa\\ExitClearance\\Models\\Approver::query()->where('email', $email)->first();

if (! $approver) {
    fwrite(STDERR, "Approver not found\\n");
    exit(1);
}

if (! $approver->trashed()) {
    $approver->delete();
}

echo json_encode(true, JSON_THROW_ON_ERROR);
`;

    runPhpScript<boolean>(script, [email]);
}
