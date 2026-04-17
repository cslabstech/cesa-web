<?php

use Cesa\Rekrutmen\Enums\JobApplicationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $jobPostingPipelineMap = DB::table('rekrutmen_job_postings')
            ->pluck('rekrutmen_pipeline_id', 'id');

        $stagePipelineMap = DB::table('rekrutmen_stages')
            ->pluck('rekrutmen_pipeline_id', 'id');

        $initialStagesByPipeline = $this->resolveInitialStagesByPipeline();
        $hiredStagesByPipeline = $this->resolveHiredStagesByPipeline();

        DB::table('rekrutmen_job_applications')
            ->orderBy('id')
            ->get(['id', 'job_posting_id', 'current_stage_id', 'status'])
            ->each(function (object $application) use (
                $jobPostingPipelineMap,
                $stagePipelineMap,
                $initialStagesByPipeline,
                $hiredStagesByPipeline
            ): void {
                $pipelineId = $jobPostingPipelineMap->get($application->job_posting_id);

                if (! is_numeric($pipelineId)) {
                    if ($application->current_stage_id !== null) {
                        DB::table('rekrutmen_job_applications')
                            ->where('id', $application->id)
                            ->update(['current_stage_id' => null]);
                    }

                    return;
                }

                $pipelineId = (int) $pipelineId;
                $currentStageId = is_numeric($application->current_stage_id)
                    ? (int) $application->current_stage_id
                    : null;
                $targetStageId = $initialStagesByPipeline->get($pipelineId);

                if (
                    $application->status === JobApplicationStatus::HIRED->value
                    && is_numeric($hiredStagesByPipeline->get($pipelineId))
                ) {
                    $targetStageId = (int) $hiredStagesByPipeline->get($pipelineId);
                } elseif (
                    $currentStageId !== null
                    && (int) $stagePipelineMap->get($currentStageId) === $pipelineId
                ) {
                    $targetStageId = $currentStageId;
                }

                if ($targetStageId === $application->current_stage_id) {
                    return;
                }

                DB::table('rekrutmen_job_applications')
                    ->where('id', $application->id)
                    ->update(['current_stage_id' => $targetStageId]);
            });
    }

    public function down(): void
    {
        //
    }

    /**
     * @return Collection<int, int>
     */
    private function resolveInitialStagesByPipeline(): Collection
    {
        return DB::table('rekrutmen_stages')
            ->orderBy('rekrutmen_pipeline_id')
            ->orderBy('order_column')
            ->orderBy('id')
            ->get(['id', 'rekrutmen_pipeline_id'])
            ->unique('rekrutmen_pipeline_id')
            ->mapWithKeys(fn (object $stage): array => [
                (int) $stage->rekrutmen_pipeline_id => (int) $stage->id,
            ]);
    }

    /**
     * @return Collection<int, int>
     */
    private function resolveHiredStagesByPipeline(): Collection
    {
        return DB::table('rekrutmen_stages')
            ->select('id', 'rekrutmen_pipeline_id')
            ->whereRaw('LOWER(name) = ?', ['hired'])
            ->orderBy('rekrutmen_pipeline_id')
            ->orderByDesc('order_column')
            ->orderByDesc('id')
            ->get()
            ->unique('rekrutmen_pipeline_id')
            ->mapWithKeys(fn (object $stage): array => [
                (int) $stage->rekrutmen_pipeline_id => (int) $stage->id,
            ]);
    }
};
