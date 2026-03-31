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

export type JobPostingMetadata = {
    id: number;
    slug: string;
    pipelineId: number | null;
    firstStageId: number | null;
    secondStageId: number | null;
    firstStageName: string | null;
    secondStageName: string | null;
};

export type JobApplicationMetadata = {
    id: number;
    status: string;
    currentStageId: number | null;
    currentStageName: string | null;
    historyCount: number;
    lastHistoryStatus: string | null;
    lastHistoryNotes: string | null;
};

export function getJobPostingMetadata(slug: string): JobPostingMetadata {
    const script = `<?php
require ${JSON.stringify(AUTOLOAD_PATH)};
$app = require ${JSON.stringify(BOOTSTRAP_APP_PATH)};
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

$slug = $argv[1] ?? null;

if (! $slug) {
    fwrite(STDERR, "Missing job posting slug\\n");
    exit(1);
}

$jobPosting = Cesa\\Rekrutmen\\Models\\JobPosting::query()
    ->where('slug', $slug)
    ->first();

if (! $jobPosting) {
    fwrite(STDERR, "Job posting not found\\n");
    exit(1);
}

$stages = $jobPosting->rekrutmenPipeline?->stages()->orderBy('order_column')->get(['id', 'name']) ?? collect();

echo json_encode([
    'id' => $jobPosting->id,
    'slug' => $jobPosting->slug,
    'pipelineId' => $jobPosting->rekrutmen_pipeline_id,
    'firstStageId' => $stages->get(0)?->id,
    'secondStageId' => $stages->get(1)?->id,
    'firstStageName' => $stages->get(0)?->name,
    'secondStageName' => $stages->get(1)?->name,
], JSON_THROW_ON_ERROR);
`;

    return runPhpScript<JobPostingMetadata>(script, [slug]);
}

export function getJobApplicationMetadata(email: string): JobApplicationMetadata {
    const script = `<?php
require ${JSON.stringify(AUTOLOAD_PATH)};
$app = require ${JSON.stringify(BOOTSTRAP_APP_PATH)};
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

$email = $argv[1] ?? null;

if (! $email) {
    fwrite(STDERR, "Missing application email\\n");
    exit(1);
}

$application = Cesa\\Rekrutmen\\Models\\JobApplication::query()
    ->where('email', $email)
    ->latest('id')
    ->first();

if (! $application) {
    fwrite(STDERR, "Job application not found\\n");
    exit(1);
}

$lastHistory = $application->histories()->latest('id')->first();

echo json_encode([
    'id' => $application->id,
    'status' => $application->status?->value ?? (string) $application->status,
    'currentStageId' => $application->current_stage_id,
    'currentStageName' => $application->currentStage?->name,
    'historyCount' => $application->histories()->count(),
    'lastHistoryStatus' => $lastHistory?->status?->value ?? ($lastHistory?->status ? (string) $lastHistory->status : null),
    'lastHistoryNotes' => $lastHistory?->notes,
], JSON_THROW_ON_ERROR);
`;

    return runPhpScript<JobApplicationMetadata>(script, [email]);
}

function runPhpScript<T>(script: string, args: string[] = []): T {
    const tempDir = fs.mkdtempSync(path.join(os.tmpdir(), "rekrutmen-workflow-"));
    const scriptPath = path.join(tempDir, "script.php");

    try {
        fs.writeFileSync(scriptPath, script, "utf8");

        const output = execFileSync(PHP_BINARY_PATH, [scriptPath, ...args], {
            cwd: REPO_ROOT,
            encoding: "utf8",
        });

        return JSON.parse(output) as T;
    } finally {
        fs.rmSync(tempDir, { recursive: true, force: true });
    }
}
