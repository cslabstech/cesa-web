<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Relaticle\Flowforge\Services\DecimalPosition;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('rekrutmen_request_man_powers', 'job_posting_id')) {
            Schema::table('rekrutmen_request_man_powers', function (Blueprint $table): void {
                $table->foreignId('job_posting_id')
                    ->nullable()
                    ->after('approved_by')
                    ->constrained('rekrutmen_job_postings')
                    ->nullOnDelete();
            });
        }

        $this->backfillExistingJobPostingLinks();
        $this->consolidateDuplicateRequestLinks();
    }

    private function backfillExistingJobPostingLinks(): void
    {
        DB::table('rekrutmen_job_postings')
            ->whereNotNull('request_man_power_id')
            ->orderBy('id')
            ->select(['id', 'request_man_power_id'])
            ->chunk(500, function ($jobPostings): void {
                foreach ($jobPostings as $jobPosting) {
                    DB::table('rekrutmen_request_man_powers')
                        ->where('id', $jobPosting->request_man_power_id)
                        ->whereNull('job_posting_id')
                        ->update([
                            'job_posting_id' => $jobPosting->id,
                            'updated_at'     => now(),
                        ]);
                }
            });
    }

    private function consolidateDuplicateRequestLinks(): void
    {
        $requests = DB::table('rekrutmen_request_man_powers as requests')
            ->join('rekrutmen_job_postings as postings', 'postings.id', '=', 'requests.job_posting_id')
            ->leftJoin('rekrutmen_job_applications as applications', function ($join): void {
                $join->on('applications.job_posting_id', '=', 'postings.id')
                    ->whereNull('applications.deleted_at');
            })
            ->whereNull('requests.deleted_at')
            ->whereNull('postings.deleted_at')
            ->where('requests.status', 'approved')
            ->selectRaw('
                requests.id,
                requests.job_posting_id,
                requests.company_id,
                requests.division_id,
                LOWER(TRIM(requests.divisi)) as division_key,
                LOWER(TRIM(requests.posisi_dibutuhkan)) as position_key,
                LOWER(TRIM(requests.lokasi_penempatan)) as location_key,
                postings.is_published,
                COUNT(applications.id) as applications_count
            ')
            ->groupBy([
                'requests.id',
                'requests.job_posting_id',
                'requests.company_id',
                'requests.division_id',
                'requests.divisi',
                'requests.posisi_dibutuhkan',
                'requests.lokasi_penempatan',
                'postings.is_published',
            ])
            ->get();

        $requests
            ->filter(fn (object $request): bool => filled($request->position_key) && filled($request->location_key))
            ->groupBy(fn (object $request): string => implode('|', [
                $request->position_key,
                $request->location_key,
                (string) $request->company_id,
                is_numeric($request->division_id)
                    ? 'division_id:'.$request->division_id
                    : 'division:'.$request->division_key,
            ]))
            ->each(function ($group): void {
                $postingSummaries = $group
                    ->groupBy('job_posting_id')
                    ->map(fn ($requests, int|string $jobPostingId): array => [
                        'id'                 => (int) $jobPostingId,
                        'is_published'       => (int) $requests->max('is_published'),
                        'applications_count' => (int) $requests->max('applications_count'),
                    ])
                    ->values();

                if ($postingSummaries->count() < 2) {
                    return;
                }

                $canonicalPosting = $postingSummaries
                    ->sort(function (array $first, array $second): int {
                        if ($first['is_published'] !== $second['is_published']) {
                            return $second['is_published'] <=> $first['is_published'];
                        }

                        if ($first['applications_count'] !== $second['applications_count']) {
                            return $second['applications_count'] <=> $first['applications_count'];
                        }

                        return $first['id'] <=> $second['id'];
                    })
                    ->first();

                if (! is_array($canonicalPosting)) {
                    return;
                }

                $canonicalPostingId = (int) $canonicalPosting['id'];
                $duplicatePostingIds = $postingSummaries
                    ->pluck('id')
                    ->reject(fn (int $jobPostingId): bool => $jobPostingId === $canonicalPostingId)
                    ->values();

                DB::table('rekrutmen_request_man_powers')
                    ->whereIn('id', $group->pluck('id')->all())
                    ->update([
                        'job_posting_id' => $canonicalPostingId,
                        'updated_at'     => now(),
                    ]);

                $this->moveDuplicatePostingApplications($duplicatePostingIds->all(), $canonicalPostingId);

                $postingSummaries
                    ->whereIn('id', $duplicatePostingIds->all())
                    ->each(function (array $postingSummary): void {
                        $payload = [
                            'is_published' => false,
                            'updated_at'   => now(),
                        ];

                        if ((int) $postingSummary['applications_count'] === 0) {
                            $payload['deleted_at'] = now();
                        }

                        DB::table('rekrutmen_job_postings')
                            ->where('id', $postingSummary['id'])
                            ->update($payload);
                    });
            });
    }

    /**
     * @param  array<int, int>  $duplicatePostingIds
     */
    private function moveDuplicatePostingApplications(array $duplicatePostingIds, int $canonicalPostingId): void
    {
        if ($duplicatePostingIds === []) {
            return;
        }

        DB::table('rekrutmen_job_applications')
            ->whereIn('job_posting_id', $duplicatePostingIds)
            ->orderBy('id')
            ->select(['id', 'active_email', 'active_whatsapp', 'current_stage_id', 'deleted_at'])
            ->get()
            ->each(function (object $application) use ($canonicalPostingId): void {
                $activeEmail = is_string($application->active_email ?? null) && $application->active_email !== ''
                    ? $application->active_email
                    : null;
                $activeWhatsapp = is_string($application->active_whatsapp ?? null) && $application->active_whatsapp !== ''
                    ? $application->active_whatsapp
                    : null;

                if (
                    $application->deleted_at === null
                    && $activeEmail !== null
                    && DB::table('rekrutmen_job_applications')
                        ->where('job_posting_id', $canonicalPostingId)
                        ->where('active_email', $activeEmail)
                        ->whereNull('deleted_at')
                        ->exists()
                ) {
                    $activeEmail = null;
                }

                if (
                    $application->deleted_at === null
                    && $activeWhatsapp !== null
                    && DB::table('rekrutmen_job_applications')
                        ->where('job_posting_id', $canonicalPostingId)
                        ->where('active_whatsapp', $activeWhatsapp)
                        ->whereNull('deleted_at')
                        ->exists()
                ) {
                    $activeWhatsapp = null;
                }

                $canonicalStageId = $this->resolveCanonicalStageId(
                    $canonicalPostingId,
                    is_numeric($application->current_stage_id) ? (int) $application->current_stage_id : null,
                );

                DB::table('rekrutmen_job_applications')
                    ->where('id', $application->id)
                    ->update([
                        'job_posting_id'  => $canonicalPostingId,
                        'current_stage_id'=> $canonicalStageId,
                        'position'        => $this->nextApplicationPositionForStage($canonicalPostingId, $canonicalStageId, (int) $application->id),
                        'active_email'    => $activeEmail,
                        'active_whatsapp' => $activeWhatsapp,
                        'updated_at'      => now(),
                    ]);
            });
    }

    private function resolveCanonicalStageId(int $canonicalPostingId, ?int $currentStageId): ?int
    {
        $canonicalPipelineId = DB::table('rekrutmen_job_postings')
            ->where('id', $canonicalPostingId)
            ->value('rekrutmen_pipeline_id');

        if (! is_numeric($canonicalPipelineId)) {
            return null;
        }

        if ($currentStageId !== null) {
            $currentStageOrder = DB::table('rekrutmen_stages')
                ->where('id', $currentStageId)
                ->value('order_column');

            if (is_numeric($currentStageOrder)) {
                $matchingStageId = DB::table('rekrutmen_stages')
                    ->where('rekrutmen_pipeline_id', (int) $canonicalPipelineId)
                    ->whereNull('deleted_at')
                    ->where('order_column', (int) $currentStageOrder)
                    ->value('id');

                if (is_numeric($matchingStageId)) {
                    return (int) $matchingStageId;
                }
            }
        }

        $firstStageId = DB::table('rekrutmen_stages')
            ->where('rekrutmen_pipeline_id', (int) $canonicalPipelineId)
            ->whereNull('deleted_at')
            ->orderBy('order_column')
            ->value('id');

        return is_numeric($firstStageId) ? (int) $firstStageId : null;
    }

    private function nextApplicationPositionForStage(int $canonicalPostingId, ?int $stageId, int $applicationId): ?string
    {
        if ($stageId === null) {
            return null;
        }

        $lastPosition = DB::table('rekrutmen_job_applications')
            ->where('job_posting_id', $canonicalPostingId)
            ->where('current_stage_id', $stageId)
            ->where('id', '!=', $applicationId)
            ->whereNull('deleted_at')
            ->whereNotNull('position')
            ->orderByDesc('position')
            ->value('position');

        return $lastPosition === null
            ? DecimalPosition::forEmptyColumn()
            : DecimalPosition::after((string) $lastPosition);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('rekrutmen_request_man_powers', 'job_posting_id')) {
            Schema::table('rekrutmen_request_man_powers', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('job_posting_id');
            });
        }
    }
};
