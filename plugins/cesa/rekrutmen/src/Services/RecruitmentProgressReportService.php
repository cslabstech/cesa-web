<?php

namespace Cesa\Rekrutmen\Services;

use Cesa\Rekrutmen\Enums\JobApplicationStatus;
use Cesa\Rekrutmen\Enums\RequestManPowerStatus;
use Cesa\Rekrutmen\Models\JobApplicationHistory;
use Cesa\Rekrutmen\Models\JobPosting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RecruitmentProgressReportService
{
    /**
     * @param  array{
     *     date_from?: ?string,
     *     date_to?: ?string,
     *     job_posting_id?: ?int,
     *     stage_id?: ?int,
     *     company_id?: ?int
     * }  $filters
     * @return array{
     *     posting_ids: int[],
     *     postings: Collection<int, JobPosting>,
     *     summary: array<string, int>,
     *     activities: Collection<int, array<string, mixed>>,
     *     timeline: Collection<int, array<string, mixed>>,
     *     positions: Collection<int, array<string, mixed>>,
     *     overview: Collection<int, array<string, mixed>>
     * }
     */
    public function build(array $filters): array
    {
        $postingIds = $this->resolvePostingIds($filters);
        $postings = $this->loadPostings($postingIds);
        $statsByPosting = $this->loadApplicationStats($postingIds);
        $decisionCounts = $this->loadDecisionCounts($postingIds, $filters);
        $hiredCandidatesByPosting = $this->loadHiredCandidates($postingIds, $filters);
        $stageCountsByPosting = $this->loadCurrentStageCounts($postingIds);
        $historyEntries = $this->loadHistoryEntries($filters, $postingIds);
        $entriesByGroup = $historyEntries->groupBy('activity_group_id');
        $groupedActivities = $historyEntries
            ->filter(fn (JobApplicationHistory $history): bool => filled($history->activity_group_id))
            ->unique('activity_group_id')
            ->values();

        $groupMetrics = $this->buildGroupMetrics($entriesByGroup);
        $passedCountsByPostingStage = $this->buildPassedCountsByPostingStage($historyEntries);
        $formattedActivities = $this->formatActivities($groupedActivities, $entriesByGroup, $groupMetrics);
        $positions = $this->buildPositions(
            $postings,
            $statsByPosting,
            $stageCountsByPosting,
            $passedCountsByPostingStage,
            $formattedActivities,
            $hiredCandidatesByPosting,
        );

        return [
            'posting_ids' => $postingIds,
            'postings'    => $postings,
            'summary'     => $this->buildSummary($postingIds, $statsByPosting, $groupedActivities, $decisionCounts),
            'activities'  => $formattedActivities,
            'timeline'    => $this->buildTimeline($formattedActivities),
            'positions'   => $positions,
            'overview'    => $this->buildOverview($positions),
        ];
    }

    /**
     * @param  array{job_posting_id?: ?int, company_id?: ?int}  $filters
     * @return int[]
     */
    protected function resolvePostingIds(array $filters): array
    {
        return JobPosting::query()
            ->when($filters['job_posting_id'] ?? null, fn (Builder $query, int $jobPostingId) => $query->whereKey($jobPostingId))
            ->when(
                $filters['company_id'] ?? null,
                fn (Builder $query, int $companyId) => $query->whereHas(
                    'requestManPower',
                    fn (Builder $requestManPowerQuery) => $requestManPowerQuery->where('company_id', $companyId)
                )
            )
            ->pluck('id')
            ->all();
    }

    /**
     * @param  int[]  $postingIds
     * @return Collection<int, JobPosting>
     */
    protected function loadPostings(array $postingIds): Collection
    {
        if ($postingIds === []) {
            return collect();
        }

        return JobPosting::query()
            ->with(['requestManPower.company', 'rekrutmenPipeline.stages'])
            ->whereIn('id', $postingIds)
            ->orderBy('title')
            ->get();
    }

    /**
     * @param  int[]  $postingIds
     * @return Collection<int, object{job_posting_id:int,total:int,in_progress:int,hired:int,rejected:int}>
     */
    protected function loadApplicationStats(array $postingIds): Collection
    {
        if ($postingIds === []) {
            return collect();
        }

        return collect(DB::table('rekrutmen_job_applications')
            ->whereIn('job_posting_id', $postingIds)
            ->whereNull('deleted_at')
            ->selectRaw('
                job_posting_id,
                COUNT(*) as total,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as hired,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as rejected
            ', [
                JobApplicationStatus::IN_PROGRESS->value,
                JobApplicationStatus::HIRED->value,
                JobApplicationStatus::REJECTED->value,
            ])
            ->groupBy('job_posting_id')
            ->get())
            ->keyBy('job_posting_id');
    }

    /**
     * @param  int[]  $postingIds
     * @param  array{
     *     date_from?: ?string,
     *     date_to?: ?string
     * }  $filters
     * @return array{hired:int,rejected:int}
     */
    protected function loadDecisionCounts(array $postingIds, array $filters): array
    {
        if ($postingIds === []) {
            return [
                'hired'    => 0,
                'rejected' => 0,
            ];
        }

        $baseQuery = JobApplicationHistory::query()
            ->whereIn('status', [
                JobApplicationStatus::HIRED->value,
                JobApplicationStatus::REJECTED->value,
            ])
            ->whereIn('job_application_id', function ($query) use ($postingIds): void {
                $query->select('id')
                    ->from('rekrutmen_job_applications')
                    ->whereIn('job_posting_id', $postingIds)
                    ->whereNull('deleted_at');
            });

        $this->applyDecisionDateFilters($baseQuery, $filters);

        return [
            'hired' => (clone $baseQuery)
                ->where('status', JobApplicationStatus::HIRED->value)
                ->distinct()
                ->count('job_application_id'),
            'rejected' => (clone $baseQuery)
                ->where('status', JobApplicationStatus::REJECTED->value)
                ->distinct()
                ->count('job_application_id'),
        ];
    }

    /**
     * @param  int[]  $postingIds
     * @param  array{
     *     date_from?: ?string,
     *     date_to?: ?string
     * }  $filters
     * @return Collection<int, Collection<int, array{id:int,job_posting_id:int,full_name:string,hired_at:?string,hired_at_label:string,performed_by:?string,notes:?string}>>
     */
    protected function loadHiredCandidates(array $postingIds, array $filters): Collection
    {
        if ($postingIds === []) {
            return collect();
        }

        $query = JobApplicationHistory::query()
            ->where('status', JobApplicationStatus::HIRED->value)
            ->whereIn('job_application_id', function ($query) use ($postingIds): void {
                $query->select('id')
                    ->from('rekrutmen_job_applications')
                    ->whereIn('job_posting_id', $postingIds)
                    ->whereNull('deleted_at');
            })
            ->with(['jobApplication', 'performer'])
            ->orderByDesc('activity_date')
            ->orderByDesc('created_at');

        $this->applyDecisionDateFilters($query, $filters);

        return $query
            ->get()
            ->unique('job_application_id')
            ->filter(fn (JobApplicationHistory $history): bool => filled($history->jobApplication?->job_posting_id))
            ->map(function (JobApplicationHistory $history): array {
                $eventDate = $this->historyEventDate($history);

                return [
                    'id'             => (int) $history->job_application_id,
                    'job_posting_id' => (int) $history->jobApplication->job_posting_id,
                    'full_name'      => $history->jobApplication->full_name ?? '-',
                    'hired_at'       => $eventDate?->toDateString(),
                    'hired_at_label' => $this->formatDate($eventDate),
                    'performed_by'   => $history->performer?->name,
                    'notes'          => $history->notes,
                ];
            })
            ->groupBy('job_posting_id');
    }

    /**
     * @param  int[]  $postingIds
     * @return array<int, array<int, int>>
     */
    protected function loadCurrentStageCounts(array $postingIds): array
    {
        if ($postingIds === []) {
            return [];
        }

        return DB::table('rekrutmen_job_applications')
            ->whereIn('job_posting_id', $postingIds)
            ->whereNull('deleted_at')
            ->where('status', JobApplicationStatus::IN_PROGRESS->value)
            ->selectRaw('job_posting_id, current_stage_id, COUNT(*) as aggregate_count')
            ->groupBy('job_posting_id', 'current_stage_id')
            ->get()
            ->reduce(function (array $carry, object $row): array {
                $carry[(int) $row->job_posting_id][(int) $row->current_stage_id] = (int) $row->aggregate_count;

                return $carry;
            }, []);
    }

    /**
     * @param  array{
     *     date_from?: ?string,
     *     date_to?: ?string,
     *     stage_id?: ?int
     * }  $filters
     * @param  int[]  $postingIds
     * @return Collection<int, JobApplicationHistory>
     */
    protected function loadHistoryEntries(array $filters, array $postingIds): Collection
    {
        if ($postingIds === []) {
            return collect();
        }

        return JobApplicationHistory::query()
            ->whereNotNull('activity_group_id')
            ->whereIn('job_application_id', function ($query) use ($postingIds): void {
                $query->select('id')
                    ->from('rekrutmen_job_applications')
                    ->whereIn('job_posting_id', $postingIds)
                    ->whereNull('deleted_at');
            })
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $dateFrom) => $query->whereDate('activity_date', '>=', $dateFrom))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $dateTo) => $query->whereDate('activity_date', '<=', $dateTo))
            ->when($filters['stage_id'] ?? null, fn (Builder $query, int $stageId) => $query->where('to_stage_id', $stageId))
            ->with(['jobApplication.jobPosting', 'toStage', 'performer'])
            ->orderByDesc('activity_date')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * @param  Collection<int, Collection<int, JobApplicationHistory>>  $entriesByGroup
     * @return array<string, array{total:int,passed:int,failed:int,pending:int,summary:string}>
     */
    protected function buildGroupMetrics(Collection $entriesByGroup): array
    {
        return $entriesByGroup->mapWithKeys(function (Collection $entries, string $groupId): array {
            $total = $entries->count();
            $passed = $entries->where('result', 'passed')->count();
            $failed = $entries->where('result', 'failed')->count();
            $pending = $entries->where('result', 'pending')->count();

            return [
                $groupId => [
                    'total'   => $total,
                    'passed'  => $passed,
                    'failed'  => $failed,
                    'pending' => $pending,
                    'summary' => self::activitySummaryText($total, $passed, $failed, $pending),
                ],
            ];
        })->all();
    }

    /**
     * @param  Builder<JobApplicationHistory>  $query
     * @param  array{
     *     date_from?: ?string,
     *     date_to?: ?string
     * }  $filters
     */
    protected function applyDecisionDateFilters(Builder $query, array $filters): void
    {
        $query
            ->when($filters['date_from'] ?? null, function (Builder $builder, string $dateFrom): Builder {
                return $builder->where(function (Builder $query) use ($dateFrom): void {
                    $query->whereDate('activity_date', '>=', $dateFrom)
                        ->orWhere(function (Builder $query) use ($dateFrom): void {
                            $query->whereNull('activity_date')
                                ->whereDate('created_at', '>=', $dateFrom);
                        });
                });
            })
            ->when($filters['date_to'] ?? null, function (Builder $builder, string $dateTo): Builder {
                return $builder->where(function (Builder $query) use ($dateTo): void {
                    $query->whereDate('activity_date', '<=', $dateTo)
                        ->orWhere(function (Builder $query) use ($dateTo): void {
                            $query->whereNull('activity_date')
                                ->whereDate('created_at', '<=', $dateTo);
                        });
                });
            });
    }

    /**
     * @param  Collection<int, JobApplicationHistory>  $historyEntries
     * @return array<int, array<int, int>>
     */
    protected function buildPassedCountsByPostingStage(Collection $historyEntries): array
    {
        return $historyEntries
            ->filter(fn (JobApplicationHistory $history): bool => $history->result?->value === 'passed'
                && filled($history->to_stage_id)
                && filled($history->jobApplication?->job_posting_id))
            ->reduce(function (array $carry, JobApplicationHistory $history): array {
                $postingId = (int) $history->jobApplication->job_posting_id;
                $stageId = (int) $history->to_stage_id;

                $carry[$postingId][$stageId] = ($carry[$postingId][$stageId] ?? 0) + 1;

                return $carry;
            }, []);
    }

    /**
     * @param  Collection<int, JobApplicationHistory>  $groupedActivities
     * @param  Collection<int, Collection<int, JobApplicationHistory>>  $entriesByGroup
     * @param  array<string, array{total:int,passed:int,failed:int,pending:int,summary:string}>  $groupMetrics
     * @return Collection<int, array<string, mixed>>
     */
    protected function formatActivities(Collection $groupedActivities, Collection $entriesByGroup, array $groupMetrics): Collection
    {
        return $groupedActivities->map(function (JobApplicationHistory $history) use ($entriesByGroup, $groupMetrics): array {
            $groupId = (string) $history->activity_group_id;
            $entries = $entriesByGroup->get($groupId, collect())->values();
            $metrics = $groupMetrics[$groupId] ?? [
                'total'   => 0,
                'passed'  => 0,
                'failed'  => 0,
                'pending' => 0,
                'summary' => self::activitySummaryText(0, 0, 0, 0),
            ];

            return [
                'group_id'          => $groupId,
                'activity_date'     => $history->activity_date,
                'activity_type'     => $history->activityKey(),
                'activity_label'    => $history->activityLabel(),
                'activity_color'    => $history->activityColor(),
                'activity_title'    => $history->activity_title,
                'to_stage'          => $history->toStage,
                'performer'         => $history->performer,
                'job_posting'       => $history->jobApplication?->jobPosting,
                'job_posting_id'    => $history->jobApplication?->job_posting_id,
                'total_candidates'  => $metrics['total'],
                'passed_count'      => $metrics['passed'],
                'failed_count'      => $metrics['failed'],
                'pending_count'     => $metrics['pending'],
                'summary'           => $metrics['summary'],
                'entries'           => $entries,
            ];
        })->values();
    }

    /**
     * @param  Collection<int, JobPosting>  $postings
     * @param  Collection<int, object{job_posting_id:int,total:int,in_progress:int,hired:int,rejected:int}>  $statsByPosting
     * @param  array<int, array<int, int>>  $stageCountsByPosting
     * @param  array<int, array<int, int>>  $passedCountsByPostingStage
     * @param  Collection<int, array<string, mixed>>  $formattedActivities
     * @param  Collection<int, Collection<int, array{id:int,job_posting_id:int,full_name:string,hired_at:?string,hired_at_label:string,performed_by:?string,notes:?string}>>  $hiredCandidatesByPosting
     * @return Collection<int, array<string, mixed>>
     */
    protected function buildPositions(
        Collection $postings,
        Collection $statsByPosting,
        array $stageCountsByPosting,
        array $passedCountsByPostingStage,
        Collection $formattedActivities,
        Collection $hiredCandidatesByPosting,
    ): Collection {
        $activitiesByPosting = $formattedActivities
            ->filter(fn (array $activity): bool => filled($activity['job_posting_id']))
            ->groupBy('job_posting_id');

        return $postings->map(function (JobPosting $posting) use ($statsByPosting, $stageCountsByPosting, $passedCountsByPostingStage, $activitiesByPosting, $hiredCandidatesByPosting): array {
            $stats = $statsByPosting->get($posting->id);
            $request = $posting->requestManPower;
            $needed = (int) ($posting->requestManPower?->jumlah_karyawan_dibutuhkan ?? 1);
            $hired = (int) ($stats->hired ?? 0);
            $hiredCandidates = $hiredCandidatesByPosting->get($posting->id, collect());
            $postingActivities = $activitiesByPosting->get($posting->id, collect())->values();
            $isOnHold = $request?->status === RequestManPowerStatus::HOLD;

            $pipelineStages = $posting->rekrutmenPipeline?->stages
                ?->sortBy('order_column')
                ->map(fn ($stage): array => [
                    'id'            => $stage->id,
                    'name'          => $stage->name,
                    'current_count' => $stageCountsByPosting[$posting->id][$stage->id] ?? 0,
                    'total_passed'  => $passedCountsByPostingStage[$posting->id][$stage->id] ?? 0,
                ])
                ->values()
                ?? collect();

            return [
                'posting'    => $posting,
                'request'    => $request,
                'statistics' => [
                    'total_applicants' => (int) ($stats->total ?? 0),
                    'in_progress'      => (int) ($stats->in_progress ?? 0),
                    'hired'            => $hired,
                    'rejected'         => (int) ($stats->rejected ?? 0),
                ],
                'activities'             => $postingActivities,
                'hired_candidates'       => $hiredCandidates->values(),
                'pipeline_stages'        => $pipelineStages,
                'latest_activity'        => $postingActivities->first(),
                'needed'                 => $needed,
                'fulfillment_percentage' => $needed > 0 ? min(100, round(($hired / $needed) * 100)) : 0,
                'request_status'         => $request?->status?->value,
                'request_status_label'   => $request?->status?->getLabel() ?? '-',
                'is_on_hold'             => $isOnHold,
            ];
        })->values();
    }

    /**
     * @param  int[]  $postingIds
     * @param  Collection<int, object{job_posting_id:int,total:int,in_progress:int,hired:int,rejected:int}>  $statsByPosting
     * @param  Collection<int, JobApplicationHistory>  $groupedActivities
     * @param  array{hired:int,rejected:int}  $decisionCounts
     * @return array<string, int>
     */
    protected function buildSummary(
        array $postingIds,
        Collection $statsByPosting,
        Collection $groupedActivities,
        array $decisionCounts,
    ): array {
        return [
            'total_positions_active'       => count($postingIds),
            'total_candidates_in_process'  => $statsByPosting->sum(fn (object $stats): int => (int) $stats->in_progress),
            'total_activities_this_period' => $groupedActivities->count(),
            'total_hired_this_period'      => $decisionCounts['hired'],
            'total_rejected_this_period'   => $decisionCounts['rejected'],
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $formattedActivities
     * @return Collection<int, array<string, mixed>>
     */
    protected function buildTimeline(Collection $formattedActivities): Collection
    {
        return $formattedActivities
            ->groupBy(fn (array $activity): string => $activity['activity_date']?->format('Y-m-d') ?? '')
            ->map(function (Collection $activities, string $date): array {
                $representative = $activities->first();

                return [
                    'date'       => $date,
                    'date_label' => $date !== '' ? Carbon::parse($date)->translatedFormat('l, d F Y') : '-',
                    'activities' => $activities->values(),
                    'count'      => $activities->count(),
                    'activity'   => $representative,
                ];
            })
            ->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $positions
     * @return Collection<int, array<string, mixed>>
     */
    protected function buildOverview(Collection $positions): Collection
    {
        return $positions->map(function (array $position): array {
            return [
                'job_posting_id'         => $position['posting']->id,
                'position'               => $position['posting']->title,
                'company'                => $position['request']?->company?->name,
                'location'               => $position['posting']->location,
                'needed'                 => $position['needed'],
                'total_applicants'       => $position['statistics']['total_applicants'],
                'hired'                  => $position['statistics']['hired'],
                'hired_candidates'       => $position['hired_candidates'],
                'in_progress'            => $position['statistics']['in_progress'],
                'rejected'               => $position['statistics']['rejected'],
                'latest_activity'        => $position['latest_activity'],
                'pipeline_progress'      => $position['pipeline_stages'],
                'fulfillment_percentage' => $position['fulfillment_percentage'],
                'request_status'         => $position['request_status'],
                'request_status_label'   => $position['request_status_label'],
                'is_on_hold'             => $position['is_on_hold'],
            ];
        })->values();
    }

    public static function activitySummaryText(int $total, int $passed, int $failed, int $pending): string
    {
        $parts = [
            __('rekrutmen::livewire/recruitment-progress-report.summary_text.total_candidates', ['count' => $total]),
        ];

        if ($passed > 0) {
            $parts[] = __('rekrutmen::livewire/recruitment-progress-report.summary_text.passed', ['count' => $passed]);
        }

        if ($failed > 0) {
            $parts[] = __('rekrutmen::livewire/recruitment-progress-report.summary_text.failed', ['count' => $failed]);
        }

        if ($pending > 0) {
            $parts[] = __('rekrutmen::livewire/recruitment-progress-report.summary_text.pending', ['count' => $pending]);
        }

        return implode(' ', $parts);
    }

    protected function historyEventDate(?JobApplicationHistory $history): ?Carbon
    {
        if (! $history instanceof JobApplicationHistory) {
            return null;
        }

        if ($history->activity_date instanceof Carbon) {
            return $history->activity_date->copy()->endOfDay();
        }

        return $history->created_at?->copy();
    }

    protected function formatDate(mixed $date): string
    {
        if ($date instanceof Carbon) {
            return $date->format('d M Y');
        }

        return '-';
    }
}
