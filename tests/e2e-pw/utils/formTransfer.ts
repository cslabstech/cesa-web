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

export type FormTransferFixture = {
    formTransferId: number;
    code: string;
    name: string;
    publicPath: string;
    divisionName: string;
    bankName: string;
};

export type FormTransferRequestMetadata = {
    requestId: number;
    uid: string;
    responseId: string;
    progressPath: string;
    approvalPath: string | null;
    approvalPaths: string[];
};

export type FormTransferWorkflowFixture = {
    workflowId: number;
    formTransferName: string;
    divisionName: string;
};

export type ManagedTransferRequestFixture = {
    requestId: number;
    uid: string;
    email: string;
    requesterName: string;
    progressPath: string;
    approvalPath: string | null;
    invoiceDownloadPath: string | null;
    accountAttachmentDownloadPath: string | null;
};

export function createPublicFormTransferFixture(key: number): FormTransferFixture {
    const script = `<?php
require ${JSON.stringify(AUTOLOAD_PATH)};
$app = require ${JSON.stringify(BOOTSTRAP_APP_PATH)};
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

$key = $argv[1] ?? null;

if (! $key) {
    fwrite(STDERR, "Missing key\\n");
    exit(1);
}

$code = 'E2E_FT_' . $key;
$name = 'E2E Form Transfer ' . $key;
$divisionName = 'E2E Division ' . $key;
$bankShortName = 'E2E BANK ' . $key;
$bankCode = 'E2E-BANK-' . $key;
$workflowCode = 'E2E-WF-' . $key;
$divisionCode = 'E2E-DIV-' . $key;
    $uidPrefix = 'E2E' . substr((string) $key, -8);

$formTransfer = Cesa\\FormTransfer\\Models\\FormTransfer::query()->updateOrCreate(
    ['company_id' => null, 'code' => $code],
    [
        'creator_id'                 => null,
        'name'                       => $name,
        'uid_prefix'                 => $uidPrefix,
        'uid_padding'                => 5,
        'uid_sequence'               => 0,
        'description'                => 'E2E fixture for form transfer public flow',
        'is_active'                  => true,
        'approver_mail_subject'      => null,
        'approver_mail_greeting'     => null,
        'approver_mail_action_text'  => null,
        'approver_mail_template'     => null,
        'requester_mail_subject'     => null,
        'requester_mail_greeting'    => null,
        'requester_mail_action_text' => null,
        'requester_mail_template'    => null,
        'approver_whatsapp_template' => null,
    ]
);

$bank = Cesa\\FormTransfer\\Models\\TransferBank::query()->updateOrCreate(
    ['code' => $bankCode],
    [
        'name'       => 'E2E Bank ' . $key,
        'short_name' => $bankShortName,
        'is_active'  => true,
        'sort_order' => 9999,
    ]
);

$division = Cesa\\FormTransfer\\Models\\TransferDivision::query()->updateOrCreate(
    [
        'form_transfer_id' => $formTransfer->id,
        'code'             => $divisionCode,
    ],
    [
        'name'        => $divisionName,
        'description' => 'E2E Division',
        'is_active'   => true,
    ]
);

Cesa\\FormTransfer\\Models\\TransferApprovalWorkflow::query()->updateOrCreate(
    [
        'form_transfer_id' => $formTransfer->id,
        'code'             => $workflowCode,
    ],
    [
        'division_id'  => $division->id,
        'name'         => 'E2E Workflow ' . $key,
        'description'  => 'E2E Workflow',
        'is_active'    => true,
        'steps'        => [
            [
                'label'         => 'Approval 1',
                'default_name'  => 'Approver One',
                'default_email' => 'form-transfer-approver-1-' . $key . '@example.com',
                'default_phone' => null,
                'default_title' => 'Finance Lead',
                'is_mandatory'  => true,
                'sort_order'    => 1,
            ],
            [
                'label'         => 'Approval 2',
                'default_name'  => 'Approver Two',
                'default_email' => 'form-transfer-approver-2-' . $key . '@example.com',
                'default_phone' => null,
                'default_title' => 'Finance Manager',
                'is_mandatory'  => true,
                'sort_order'    => 2,
            ],
        ],
    ]
);

$publicUrl = route('form-transfer.public.form', ['formTransfer' => $formTransfer->code]);
$path = parse_url($publicUrl, PHP_URL_PATH) ?? '';
$query = parse_url($publicUrl, PHP_URL_QUERY);
$publicPath = $query ? $path . '?' . $query : $path;

echo json_encode([
    'formTransferId' => $formTransfer->id,
    'code' => $formTransfer->code,
    'name' => $formTransfer->name,
    'publicPath' => $publicPath,
    'divisionName' => $division->name,
    'bankName' => $bank->display_name,
], JSON_THROW_ON_ERROR);
`;

    return runPhpScript<FormTransferFixture>(script, [String(key)]);
}

export function getFormTransferRequestMetadata(email: string): FormTransferRequestMetadata {
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

$request = Cesa\\FormTransfer\\Models\\TransferRequest::query()
    ->where('email', $email)
    ->latest('id')
    ->first();

if (! $request) {
    fwrite(STDERR, "Transfer request not found\\n");
    exit(1);
}

$approvalPaths = collect($request->approvals ?? [])
    ->map(fn (array $approval) => route('form-transfer.public.approval', ['task' => $approval['task_id']]))
    ->map(function (string $url): string {
        $path = parse_url($url, PHP_URL_PATH) ?? '';
        $query = parse_url($url, PHP_URL_QUERY);

        return $query ? $path . '?' . $query : $path;
    })
    ->all();

$progressUrl = route('form-transfer.public.progress', ['response' => $request->status_response_id]);
$progressPath = (function (string $url): string {
    $path = parse_url($url, PHP_URL_PATH) ?? '';
    $query = parse_url($url, PHP_URL_QUERY);

    return $query ? $path . '?' . $query : $path;
})($progressUrl);

echo json_encode([
    'requestId' => $request->id,
    'uid' => $request->uid,
    'responseId' => $request->status_response_id,
    'progressPath' => $progressPath,
    'approvalPath' => $approvalPaths[0] ?? null,
    'approvalPaths' => $approvalPaths,
], JSON_THROW_ON_ERROR);
`;

    return runPhpScript<FormTransferRequestMetadata>(script, [email]);
}

export function createFormTransferWorkflowFixture(key: number): FormTransferWorkflowFixture {
    const fixture = createPublicFormTransferFixture(key);

    const script = `<?php
require ${JSON.stringify(AUTOLOAD_PATH)};
$app = require ${JSON.stringify(BOOTSTRAP_APP_PATH)};
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

$code = $argv[1] ?? null;

if (! $code) {
    fwrite(STDERR, "Missing form transfer code\\n");
    exit(1);
}

$formTransfer = Cesa\\FormTransfer\\Models\\FormTransfer::query()->where('code', $code)->first();

if (! $formTransfer) {
    fwrite(STDERR, "Form transfer fixture not found\\n");
    exit(1);
}

$workflow = Cesa\\FormTransfer\\Models\\TransferApprovalWorkflow::query()
    ->where('form_transfer_id', $formTransfer->id)
    ->latest('id')
    ->first();

if (! $workflow) {
    fwrite(STDERR, "Approval workflow fixture not found\\n");
    exit(1);
}

$workflow->load('division');

echo json_encode([
    'workflowId' => $workflow->id,
    'formTransferName' => $formTransfer->name,
    'divisionName' => $workflow->division?->name,
], JSON_THROW_ON_ERROR);
`;

    return runPhpScript<FormTransferWorkflowFixture>(script, [fixture.code]);
}

export function createManagedTransferRequestFixture(input: {
    key: number;
    state?: "pending" | "approved" | "rejected";
    withAttachments?: boolean;
}): ManagedTransferRequestFixture {
    const fixture = createPublicFormTransferFixture(input.key);

    const script = `<?php
require ${JSON.stringify(AUTOLOAD_PATH)};
$app = require ${JSON.stringify(BOOTSTRAP_APP_PATH)};
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

[$code, $key, $state, $withAttachments] = array_pad(array_slice($argv, 1), 4, null);

if (! $code || ! $key) {
    fwrite(STDERR, "Missing transfer request fixture arguments\\n");
    exit(1);
}

$formTransfer = Cesa\\FormTransfer\\Models\\FormTransfer::query()->where('code', $code)->first();

if (! $formTransfer) {
    fwrite(STDERR, "Form transfer fixture not found\\n");
    exit(1);
}

$division = Cesa\\FormTransfer\\Models\\TransferDivision::query()
    ->where('form_transfer_id', $formTransfer->id)
    ->latest('id')
    ->first();
$bank = Cesa\\FormTransfer\\Models\\TransferBank::query()->where('short_name', 'BCA')->first()
    ?? Cesa\\FormTransfer\\Models\\TransferBank::query()->latest('id')->first();
$workflow = Cesa\\FormTransfer\\Models\\TransferApprovalWorkflow::query()
    ->where('form_transfer_id', $formTransfer->id)
    ->latest('id')
    ->first();

if (! $division || ! $bank || ! $workflow) {
    fwrite(STDERR, "Supporting transfer request fixture data is missing\\n");
    exit(1);
}

$state = $state ?: 'pending';
$withAttachments = filter_var($withAttachments, FILTER_VALIDATE_BOOLEAN);
$email = 'managed-form-transfer+' . $key . '@example.com';
$requesterName = 'Managed Transfer ' . $key;

$invoicePath = null;
$accountAttachmentPath = null;

if ($withAttachments) {
    $invoicePath = 'form-transfer/invoices/e2e-invoice-' . $key . '.txt';
    $accountAttachmentPath = 'form-transfer/account-attachments/e2e-account-' . $key . '.txt';

    \\Illuminate\\Support\\Facades\\Storage::disk(config('filesystems.default'))->put($invoicePath, 'Invoice fixture ' . $key);
    \\Illuminate\\Support\\Facades\\Storage::disk(config('filesystems.default'))->put($accountAttachmentPath, 'Account attachment fixture ' . $key);
}

$approvals = [
    [
        'label' => 'Approval 1',
        'name' => 'Approver One',
        'email' => 'managed-approver-1-' . $key . '@example.com',
        'title' => 'Finance Lead',
        'status' => 'pending',
        'comments' => null,
        'noted_at' => null,
        'task_id' => (string) \\Illuminate\\Support\\Str::uuid(),
        'notified_at' => now()->toISOString(),
        'is_mandatory' => true,
        'has_next' => true,
        'sort_order' => 1,
    ],
    [
        'label' => 'Approval 2',
        'name' => 'Approver Two',
        'email' => 'managed-approver-2-' . $key . '@example.com',
        'title' => 'Finance Manager',
        'status' => 'waiting',
        'comments' => null,
        'noted_at' => null,
        'task_id' => (string) \\Illuminate\\Support\\Str::uuid(),
        'notified_at' => null,
        'is_mandatory' => true,
        'has_next' => false,
        'sort_order' => 2,
    ],
];

$approvalStatus = Cesa\\FormTransfer\\Enums\\TransferRequestApprovalStatus::PENDING;
$realizationStatus = Cesa\\FormTransfer\\Enums\\TransferRequestRealizationStatus::PENDING;

if ($state === 'approved') {
    $approvals[0]['status'] = 'approved';
    $approvals[0]['comments'] = 'Approved 1';
    $approvals[0]['noted_at'] = now()->subDay()->toISOString();
    $approvals[1]['status'] = 'approved';
    $approvals[1]['comments'] = 'Approved 2';
    $approvals[1]['noted_at'] = now()->toISOString();
    $approvals[1]['notified_at'] = now()->subHours(12)->toISOString();
    $approvalStatus = Cesa\\FormTransfer\\Enums\\TransferRequestApprovalStatus::APPROVED;
} elseif ($state === 'rejected') {
    $approvals[0]['status'] = 'ditolak';
    $approvals[0]['comments'] = 'Rejected';
    $approvals[0]['noted_at'] = now()->toISOString();
    $approvalStatus = Cesa\\FormTransfer\\Enums\\TransferRequestApprovalStatus::REJECTED;
}

$request = Cesa\\FormTransfer\\Models\\TransferRequest::query()->create([
    'form_transfer_id' => $formTransfer->id,
    'division_name' => $division->name,
    'division_id' => $division->id,
    'email' => $email,
    'requester_name' => $requesterName,
    'account_number' => '12345' . substr((string) $key, -5),
    'account_name' => 'Pemohon ' . $key,
    'bank_id' => $bank->id,
    'transfer_amount' => 1250000,
    'purpose' => 'Managed fixture purpose ' . $key,
    'reference_note' => 'Managed fixture note ' . $key,
    'approval_workflow_id' => $workflow->id,
    'approvals' => $approvals,
    'approval_status' => $approvalStatus,
    'realization_status' => $realizationStatus,
    'invoice_path' => $invoicePath,
    'account_attachment_path' => $accountAttachmentPath,
]);

$progressUrl = route('form-transfer.public.progress', ['response' => $request->status_response_id]);
$approvalUrl = route('form-transfer.public.approval', ['task' => $approvals[0]['task_id']]);
$invoiceDownloadUrl = $invoicePath
    ? \\Illuminate\\Support\\Facades\\URL::temporarySignedRoute(
        'form-transfer.public.attachments.download',
        now()->addMinutes(60),
        ['statusResponseId' => $request->status_response_id, 'attachment' => 'invoice', 'file' => 0]
    )
    : null;
$accountAttachmentDownloadUrl = $accountAttachmentPath
    ? \\Illuminate\\Support\\Facades\\URL::temporarySignedRoute(
        'form-transfer.public.attachments.download',
        now()->addMinutes(60),
        ['statusResponseId' => $request->status_response_id, 'attachment' => 'account-attachment', 'file' => 0]
    )
    : null;

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
    'uid' => $request->uid,
    'email' => $request->email,
    'requesterName' => $request->requester_name,
    'progressPath' => $toPath($progressUrl),
    'approvalPath' => $state === 'pending' ? $toPath($approvalUrl) : null,
    'invoiceDownloadPath' => $toPath($invoiceDownloadUrl),
    'accountAttachmentDownloadPath' => $toPath($accountAttachmentDownloadUrl),
], JSON_THROW_ON_ERROR);
`;

    return runPhpScript<ManagedTransferRequestFixture>(script, [
        fixture.code,
        String(input.key),
        input.state ?? "pending",
        String(Boolean(input.withAttachments)),
    ]);
}

function runPhpScript<T>(script: string, args: string[] = []): T {
    const tempDir = fs.mkdtempSync(path.join(os.tmpdir(), "form-transfer-"));
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
