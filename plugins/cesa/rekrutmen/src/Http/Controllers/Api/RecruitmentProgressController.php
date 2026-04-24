<?php

namespace Cesa\Rekrutmen\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Cesa\Rekrutmen\Models\JobApplicationHistory;
use Cesa\Rekrutmen\Services\RecruitmentProgressReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecruitmentProgressController extends Controller
{
    public function __construct(
        protected RecruitmentProgressReportService $reportService,
    ) {}

    public function report(Request $request): JsonResponse
    {
        $this->authorizeReportAccess($request);

        $report = $this->reportService->build($this->normalizeFilters($request));

        return response()->json([
            'meta' => [
                'title'        => 'Progres Rekrutmen',
                'generated_at' => now()->toIso8601String(),
                'period'       => [
                    'from' => $request->input('date_from'),
                    'to'   => $request->input('date_to'),
                ],
            ],
            'summary'    => $report['summary'],
            'activities' => $report['activities']->map(fn (array $activity): array => $this->formatActivity($activity))->values(),
            'positions'  => $report['positions']->map(fn (array $position): array => [
                'job_posting_id'         => $position['posting']->id,
                'position'               => $position['posting']->title,
                'company'                => $position['request']?->company?->name,
                'location'               => $position['posting']->location,
                'needed'                 => $position['needed'],
                'total_applicants'       => $position['statistics']['total_applicants'],
                'hired'                  => $position['statistics']['hired'],
                'in_progress'            => $position['statistics']['in_progress'],
                'rejected'               => $position['statistics']['rejected'],
                'request_status'         => $position['request_status'],
                'request_status_label'   => $position['request_status_label'],
                'is_on_hold'             => $position['is_on_hold'],
                'latest_activity'        => $position['latest_activity']
                    ? [
                        'date'    => $position['latest_activity']['activity_date']?->format('Y-m-d'),
                        'type'    => $position['latest_activity']['activity_type'],
                        'label'   => $position['latest_activity']['activity_label'],
                        'stage'   => $position['latest_activity']['to_stage']?->name,
                        'summary' => $position['latest_activity']['summary'],
                    ]
                    : null,
                'pipeline_progress'      => $position['pipeline_stages'],
                'fulfillment_percentage' => $position['fulfillment_percentage'],
            ])->values(),
        ]);
    }

    public function timeline(Request $request): JsonResponse
    {
        $this->authorizeReportAccess($request);

        $report = $this->reportService->build($this->normalizeFilters($request));

        return response()->json([
            'meta' => [
                'title'        => 'Progres Rekrutmen - Timeline',
                'generated_at' => now()->toIso8601String(),
            ],
            'timeline' => $report['timeline']->map(fn (array $timeline): array => [
                'date'       => $timeline['date'],
                'date_label' => $timeline['date_label'],
                'count'      => $timeline['count'],
                'activities' => collect($timeline['activities'])
                    ->map(fn (array $activity): array => $this->formatActivity($activity))
                    ->values(),
                'activity'   => $this->formatActivity($timeline['activity']),
            ])->values(),
        ]);
    }

    public function overview(Request $request): JsonResponse
    {
        $this->authorizeReportAccess($request);

        $report = $this->reportService->build($this->normalizeFilters($request));

        return response()->json([
            'meta' => [
                'title'        => 'Progres Rekrutmen - Ringkasan',
                'generated_at' => now()->toIso8601String(),
            ],
            'overview' => $report['overview']->map(fn (array $overview): array => [
                ...$overview,
                'latest_activity' => $overview['latest_activity']
                    ? [
                        'date'    => $overview['latest_activity']['activity_date']?->format('Y-m-d'),
                        'type'    => $overview['latest_activity']['activity_type'],
                        'label'   => $overview['latest_activity']['activity_label'],
                        'stage'   => $overview['latest_activity']['to_stage']?->name,
                        'summary' => $overview['latest_activity']['summary'],
                    ]
                    : null,
            ])->values(),
        ]);
    }

    /**
     * @return array{
     *     date_from: ?string,
     *     date_to: ?string,
     *     job_posting_id: ?int,
     *     stage_id: ?int,
     *     company_id: ?int
     * }
     */
    private function normalizeFilters(Request $request): array
    {
        return [
            'date_from'      => $request->input('date_from'),
            'date_to'        => $request->input('date_to'),
            'job_posting_id' => $request->integer('job_posting_id') ?: null,
            'stage_id'       => $request->integer('stage_id') ?: null,
            'company_id'     => $request->integer('company_id') ?: null,
        ];
    }

    /**
     * @param  array<string, mixed>  $activity
     * @return array<string, mixed>
     */
    private function formatActivity(array $activity): array
    {
        return [
            'id'                  => $activity['group_id'],
            'activity_date'       => $activity['activity_date']?->format('Y-m-d'),
            'activity_type'       => $activity['activity_type'],
            'activity_type_label' => $activity['activity_label'],
            'title'               => $activity['activity_title'],
            'position'            => $activity['job_posting']?->title,
            'job_posting_id'      => $activity['job_posting_id'],
            'stage_name'          => $activity['to_stage']?->name,
            'performer'           => $activity['performer']?->name,
            'summary'             => $activity['summary'],
            'counts'              => [
                'total'   => $activity['total_candidates'],
                'passed'  => $activity['passed_count'],
                'failed'  => $activity['failed_count'],
                'pending' => $activity['pending_count'],
            ],
            'entries' => $activity['entries']->map(fn ($entry): array => [
                'candidate_name' => $entry->jobApplication?->full_name ?? '-',
                'result'         => $entry->result?->value,
                'result_label'   => $entry->result?->getLabel(),
                'notes'          => $entry->notes,
            ])->values(),
        ];
    }

    private function authorizeReportAccess(Request $request): void
    {
        abort_unless($request->user()?->can('viewAny', JobApplicationHistory::class), 403);
    }
}
