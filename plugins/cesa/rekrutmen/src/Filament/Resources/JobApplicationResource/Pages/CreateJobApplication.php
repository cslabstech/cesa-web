<?php

namespace Cesa\Rekrutmen\Filament\Resources\JobApplicationResource\Pages;

use Cesa\Rekrutmen\Enums\JobApplicationStatus;
use Cesa\Rekrutmen\Filament\Resources\JobApplicationResource;
use Cesa\Rekrutmen\Models\JobApplication;
use Cesa\Rekrutmen\Models\JobPosting;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateJobApplication extends CreateRecord
{
    protected static string $resource = JobApplicationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $jobPosting = is_numeric($data['job_posting_id'] ?? null)
            ? JobPosting::query()->find((int) $data['job_posting_id'])
            : null;

        $data['current_stage_id'] = JobApplication::resolveInitialStageIdForJobPostingId($data['job_posting_id'] ?? null);
        $data['status'] = JobApplicationStatus::IN_PROGRESS;

        if ($data['current_stage_id'] === null) {
            throw ValidationException::withMessages([
                'job_posting_id' => __('rekrutmen::filament/resources/job-application.workflow_errors.job_posting_has_no_stage'),
            ]);
        }

        return $data;
    }
}
