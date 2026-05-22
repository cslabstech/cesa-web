<?php

namespace Cesa\Rekrutmen\Services;

use Cesa\Rekrutmen\Enums\JobApplicationStatus;
use Cesa\Rekrutmen\Enums\RequestManPowerStatus;
use Cesa\Rekrutmen\Models\JobApplicationHistory;
use Cesa\Rekrutmen\Models\RequestManPower;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RecruitmentProgressReportExport implements WithMultipleSheets
{
    protected Collection $positions;

    protected Collection $activities;

    /**
     * @var array<string, int>
     */
    protected array $summary;

    /**
     * @var array{
     *     date_from?: ?string,
     *     date_to?: ?string,
     *     period_label?: string,
     *     position_label?: string,
     *     company_label?: string
     * }
     */
    protected array $filters;

    /**
     * @var Collection<int, array<string, mixed>>
     */
    protected Collection $positionsByPostingId;

    /**
     * @var Collection<int, JobApplicationHistory>|null
     */
    protected ?Collection $histories = null;

    /**
     * @var Collection<int, array<string, mixed>>|null
     */
    protected ?Collection $monthlySections = null;

    /**
     * @param  array{
     *      positions?: Collection<int, array<string, mixed>>,
     *      activities?: Collection<int, array<string, mixed>>,
     *      summary?: array<string, int>
     *  }  $reportData
     * @param  array{
     *      date_from?: ?string,
     *      date_to?: ?string,
     *      period_label?: string,
     *      position_label?: string,
     *      company_label?: string
     *  }  $filters
     */
    public function __construct(array $reportData, array $filters = [])
    {
        $this->positions = collect($reportData['positions'] ?? []);
        $this->activities = collect($reportData['activities'] ?? []);
        $this->summary = $reportData['summary'] ?? [];
        $this->filters = $filters;
        $this->positionsByPostingId = $this->positions
            ->filter(fn (array $position): bool => filled($position['posting']?->id))
            ->mapWithKeys(fn (array $position): array => [(int) $position['posting']->id => $position]);
    }

    /**
     * @return array<int, WithTitle>
     */
    public function sheets(): array
    {
        $monthlySections = $this->monthlySections();
        $activityRows = $this->activityRows();

        return [
            new RecruitmentProgressMonthlyOverviewSheet($monthlySections),
            new RecruitmentProgressWorkbookSheet(
                title: 'Ringkasan Bulanan',
                summaryLine: $this->monthlySummaryLine($monthlySections),
                filterLine: $this->filterLine(),
                noteLine: 'Ringkasan ini merangkum sheet Overview MPP pada snapshot tiap bulan: total kebutuhan, join, sisa MPP, dan MPP hold.',
                headers: [
                    'Bulan',
                    'Snapshot',
                    'MPP Open',
                    'Total Kebutuhan MPP',
                    'Join Bulan Ini',
                    'Join s/d Snapshot',
                    'Sisa MPP',
                    'Sisa Hold',
                    'Rata-rata Umur MPP Open (Hari)',
                    'Perusahaan',
                    'Status Ringkas',
                ],
                rows: $this->monthlySummaryRows($monthlySections)->all(),
                columnWidths: [
                    'A' => 16,
                    'B' => 16,
                    'C' => 12,
                    'D' => 20,
                    'E' => 14,
                    'F' => 14,
                    'G' => 12,
                    'H' => 12,
                    'I' => 28,
                    'J' => 28,
                    'K' => 36,
                ],
                centeredColumns: ['C', 'D', 'E', 'F', 'G', 'H', 'I'],
            ),
            new RecruitmentProgressWorkbookSheet(
                title: 'Detail Posisi',
                summaryLine: $this->positionSummaryLine(),
                filterLine: $this->filterLine(),
                noteLine: 'Detail ini mengikuti snapshot akhir periode dan diturunkan per MPP request, bukan digabung per job posting.',
                headers: [
                    'ID Permintaan MPP',
                    'ID Lowongan',
                    'Perusahaan',
                    'Tanggal Req',
                    'Snapshot',
                    'Umur Req (Hari)',
                    'Posisi',
                    'Penempatan',
                    'Kebutuhan MPP',
                    'Join Bulan Ini',
                    'Join s/d Snapshot',
                    'Sisa MPP',
                    'Dalam Proses Saat Ini',
                    'User',
                    'PIC Terakhir / Join',
                    'Jenis Kebutuhan',
                    'Status Permintaan',
                    'Status Pemenuhan',
                    'Prioritas Tindak Lanjut',
                    'Update Progres Terakhir',
                    'Karyawan Join Bulan Ini',
                ],
                rows: $this->positionSnapshotRows()->all(),
                columnWidths: [
                    'A' => 18,
                    'B' => 18,
                    'C' => 24,
                    'D' => 16,
                    'E' => 16,
                    'F' => 14,
                    'G' => 28,
                    'H' => 24,
                    'I' => 14,
                    'J' => 14,
                    'K' => 16,
                    'L' => 12,
                    'M' => 18,
                    'N' => 18,
                    'O' => 22,
                    'P' => 18,
                    'Q' => 18,
                    'R' => 20,
                    'S' => 30,
                    'T' => 18,
                    'U' => 30,
                ],
                centeredColumns: ['A', 'B', 'F', 'I', 'J', 'K', 'L', 'M'],
            ),
            new RecruitmentProgressWorkbookSheet(
                title: 'Aktivitas Rekrutmen',
                summaryLine: 'Total batch aktivitas pada periode terpilih: '.($this->summary['total_activities_this_period'] ?? $activityRows->count()),
                filterLine: $this->filterLine(),
                noteLine: 'Sheet ini berisi detail aktivitas seleksi yang terekam di sistem pada periode yang dipilih.',
                headers: [
                    'Tanggal',
                    'Bulan',
                    'Perusahaan',
                    'ID Lowongan',
                    'Job Posting',
                    'ID Permintaan MPP',
                    'MPP Terkait',
                    'Tahap',
                    'Aktivitas',
                    'Ringkasan',
                    'Total Kandidat',
                    'Lolos',
                    'Tidak Lolos',
                    'Menunggu',
                    'PIC',
                ],
                rows: $activityRows->all(),
                columnWidths: [
                    'A' => 14,
                    'B' => 12,
                    'C' => 24,
                    'D' => 18,
                    'E' => 28,
                    'F' => 24,
                    'G' => 34,
                    'H' => 20,
                    'I' => 24,
                    'J' => 34,
                    'K' => 14,
                    'L' => 10,
                    'M' => 14,
                    'N' => 12,
                    'O' => 18,
                ],
                centeredColumns: ['D', 'F', 'K', 'L', 'M', 'N'],
            ),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function monthlySections(): Collection
    {
        if ($this->monthlySections instanceof Collection) {
            return $this->monthlySections;
        }

        $this->monthlySections = $this->selectedMonths()
            ->map(function (array $month): array {
                $overviewRows = $this->overviewRowsForMonth($month['start_date'], $month['snapshot_date']);
                $openRows = $overviewRows
                    ->filter(fn (array $row): bool => (int) $row['outstanding'] > 0)
                    ->map(fn (array $row): array => [
                        'company'    => $row['company'],
                        'quantity'   => (int) $row['outstanding'],
                        'is_on_hold' => (bool) $row['is_on_hold'],
                        'age_days'   => (int) $row['age_days'],
                    ])
                    ->values();
                $joinRows = $overviewRows
                    ->filter(fn (array $row): bool => (int) $row['join_month'] > 0)
                    ->map(fn (array $row): array => [
                        'company'  => $row['company'],
                        'quantity' => (int) $row['join_month'],
                        'days'     => (int) $row['average_join_days'],
                    ])
                    ->values();

                return [
                    'month_key'     => $month['month_key'],
                    'month_label'   => $month['month_label'],
                    'snapshot_date' => $month['snapshot_date'],
                    'overview_rows' => $overviewRows,
                    'open_rows'     => $openRows,
                    'join_rows'     => $joinRows,
                    'open_total'    => (int) $openRows->sum('quantity'),
                    'join_total'    => (int) $joinRows->sum('quantity'),
                ];
            })
            ->values();

        return $this->monthlySections;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $monthlySections
     * @return Collection<int, array<int, int|string>>
     */
    protected function monthlySummaryRows(Collection $monthlySections): Collection
    {
        return $monthlySections->map(function (array $section): array {
            $overviewRows = collect($section['overview_rows']);
            $openRows = collect($section['open_rows']);
            $joinDays = collect($section['join_rows'])
                ->pluck('days')
                ->filter(fn (mixed $days): bool => is_int($days) || is_float($days));
            $openAgeDays = $openRows
                ->pluck('age_days')
                ->filter(fn (mixed $days): bool => is_int($days) || is_float($days));
            $companies = $overviewRows
                ->pluck('company')
                ->filter(fn (?string $company): bool => filled($company) && $company !== '-')
                ->unique()
                ->values();
            $holdTotal = (int) $openRows
                ->where('is_on_hold', true)
                ->sum('quantity');

            return [
                $section['month_label'],
                $this->formatDate($section['snapshot_date']),
                $openRows->count(),
                (int) $overviewRows->sum(fn (array $row): int => (int) $row['needed']),
                $section['join_total'],
                (int) $overviewRows->sum(fn (array $row): int => (int) $row['fulfilled']),
                $section['open_total'],
                $holdTotal,
                $openAgeDays->isEmpty() ? 0 : (int) round($openAgeDays->avg()),
                $this->companyScopeLabel($companies),
                $this->monthlyStatusLabel($section['open_total'], $section['join_total'], $holdTotal),
            ];
        })->values();
    }

    protected function monthlySummaryLine(Collection $monthlySections): string
    {
        $totalNeed = (int) $monthlySections->sum(
            fn (array $section): int => (int) collect($section['overview_rows'])
                ->sum(fn (array $row): int => (int) $row['needed'])
        );
        $holdTotal = (int) $monthlySections->sum(
            fn (array $section): int => (int) collect($section['open_rows'])
                ->where('is_on_hold', true)
                ->sum('quantity')
        );

        return sprintf(
            'Ringkasan: %d bulan | Kebutuhan MPP %d | Join bulan ini %d | Sisa MPP %d | Sisa hold %d',
            $monthlySections->count(),
            $totalNeed,
            (int) $monthlySections->sum('join_total'),
            (int) $monthlySections->sum('open_total'),
            $holdTotal,
        );
    }

    /**
     * @return Collection<int, array<int, int|string>>
     */
    protected function positionSnapshotRows(): Collection
    {
        return $this->finalOverviewRows()
            ->filter(fn (array $row): bool => (int) $row['outstanding'] > 0)
            ->map(fn (array $row): array => [
                $row['request_reference'],
                $row['job_posting_reference'],
                $row['company'],
                $row['request_date'],
                $row['snapshot_date'],
                $row['age_days'],
                $row['position'],
                $row['location'],
                $row['needed'],
                $row['join_month'],
                $row['fulfilled'],
                $row['outstanding'],
                $row['in_progress'],
                $row['user'],
                $row['pic'],
                $row['need_type'],
                $row['request_status'],
                $row['fulfillment_status'],
                $row['priority'],
                $row['update_date'],
                $row['candidate_names'],
            ])
            ->sortBy(fn (array $row): string => sprintf(
                '%04d|%04d|%s|%s',
                9999 - (int) $row[5],
                9999 - (int) $row[11],
                mb_strtolower((string) $row[2]),
                mb_strtolower((string) $row[6]),
            ))
            ->values();
    }

    protected function positionSummaryLine(): string
    {
        $snapshotDate = $this->finalSnapshotDate();
        $openRows = $this->finalOverviewRows()
            ->filter(fn (array $row): bool => (int) $row['outstanding'] > 0)
            ->values();
        $outstanding = (int) $openRows->sum(fn (array $row): int => (int) $row['outstanding']);
        $urgent = $openRows->where('priority', 'Perlu eskalasi segera')->count();
        $hold = (int) $openRows
            ->where('is_on_hold', true)
            ->sum(fn (array $row): int => (int) $row['outstanding']);

        return sprintf(
            'Snapshot %s | MPP open %d | Outstanding MPP %d | Outstanding hold %d | Perlu eskalasi %d',
            $this->formatDate($snapshotDate),
            $openRows->count(),
            $outstanding,
            $hold,
            $urgent,
        );
    }

    /**
     * @return Collection<int, array<int, int|string>>
     */
    protected function activityRows(): Collection
    {
        $snapshotDate = $this->finalSnapshotDate();

        return $this->activities
            ->map(function (array $activity) use ($snapshotDate): array {
                $postingId = (int) ($activity['job_posting_id'] ?? 0);
                $position = $this->positionsByPostingId->get($postingId);
                $activityDate = $activity['activity_date'] instanceof Carbon
                    ? $activity['activity_date']
                    : null;

                return [
                    $this->formatDate($activityDate),
                    $activityDate?->format('M Y') ?? '-',
                    $this->companyLabelForPosition(is_array($position) ? $position : null, $snapshotDate),
                    $this->postingReferenceLabel($postingId),
                    $activity['job_posting']?->title ?? '-',
                    $this->mppReferenceScopeLabel(is_array($position) ? $position : null, $snapshotDate),
                    $this->mppScopeLabel(is_array($position) ? $position : null, $snapshotDate),
                    $activity['to_stage']?->name ?? '-',
                    $activity['activity_title'] ?? '-',
                    $activity['summary'] ?? '-',
                    (int) ($activity['total_candidates'] ?? 0),
                    (int) ($activity['passed_count'] ?? 0),
                    (int) ($activity['failed_count'] ?? 0),
                    (int) ($activity['pending_count'] ?? 0),
                    $activity['performer_label'] ?? $activity['performer']?->name ?? '-',
                ];
            })
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function finalOverviewRows(): Collection
    {
        $section = $this->monthlySections()->last();

        return is_array($section)
            ? collect($section['overview_rows'] ?? [])
            : collect();
    }

    /**
     * @param  array<string, mixed>|null  $position
     */
    protected function companyLabelForPosition(?array $position, Carbon $snapshotDate): string
    {
        if (! is_array($position)) {
            return '-';
        }

        $companies = $this->visibleRequestsForPosition($position, $snapshotDate)
            ->map(fn (RequestManPower $request): string => $this->uppercase($request->company?->name))
            ->reject(fn (string $company): bool => $company === '-')
            ->unique()
            ->values();

        return $this->companyScopeLabel($companies);
    }

    /**
     * @param  array<string, mixed>|null  $position
     */
    protected function mppScopeLabel(?array $position, Carbon $snapshotDate): string
    {
        if (! is_array($position)) {
            return '-';
        }

        $requests = $this->visibleRequestsForPosition($position, $snapshotDate)
            ->map(fn (RequestManPower $request): string => $this->uppercase($request->posisi_dibutuhkan))
            ->reject(fn (string $positionName): bool => $positionName === '-')
            ->unique()
            ->values();

        if ($requests->isEmpty()) {
            return '-';
        }

        if ($requests->count() > 3) {
            return $requests->count().' MPP';
        }

        return $requests->implode("\n");
    }

    /**
     * @param  array<string, mixed>|null  $position
     */
    protected function mppReferenceScopeLabel(?array $position, Carbon $snapshotDate): string
    {
        if (! is_array($position)) {
            return '-';
        }

        $requestReferences = $this->visibleRequestsForPosition($position, $snapshotDate)
            ->map(fn (RequestManPower $request): string => $this->requestReferenceLabel($request->getKey()))
            ->reject(fn (string $requestReference): bool => $requestReference === '-')
            ->unique()
            ->values();

        return $requestReferences->isEmpty() ? '-' : $requestReferences->implode("\n");
    }

    /**
     * @return Collection<int, array{month_key:string,month_label:string,start_date:Carbon,snapshot_date:Carbon}>
     */
    protected function selectedMonths(): Collection
    {
        $dateFrom = $this->parseDate($this->filters['date_from'] ?? null)
            ?? $this->parseDate($this->filters['date_to'] ?? null)
            ?? now();
        $dateTo = $this->parseDate($this->filters['date_to'] ?? null)
            ?? $this->parseDate($this->filters['date_from'] ?? null)
            ?? now();

        if ($dateFrom->greaterThan($dateTo)) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $cursor = $dateFrom->copy()->startOfMonth();
        $lastMonth = $dateTo->copy()->startOfMonth();
        $months = collect();

        while ($cursor->lessThanOrEqualTo($lastMonth)) {
            $snapshotDate = $cursor->isSameMonth($dateTo)
                ? $dateTo->copy()->endOfDay()
                : $cursor->copy()->endOfMonth()->endOfDay();

            $months->push([
                'month_key'     => $cursor->format('Y-m'),
                'month_label'   => mb_strtoupper($cursor->translatedFormat('F Y')),
                'start_date'    => $cursor->copy()->startOfMonth(),
                'snapshot_date' => $snapshotDate,
            ]);

            $cursor->addMonth();
        }

        return $months;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function overviewRowsForMonth(Carbon $monthStart, Carbon $snapshotDate): Collection
    {
        return $this->positions
            ->flatMap(fn (array $position): Collection => $this->requestOverviewRowsForPosition($position, $monthStart, $snapshotDate))
            ->filter(fn (array $row): bool => (int) $row['outstanding'] > 0 || (int) $row['join_month'] > 0)
            ->sortBy(fn (array $row): string => sprintf(
                '%s|%s|%s|%010d',
                mb_strtolower((string) $row['company']),
                (string) $row['request_date_sort'],
                mb_strtolower((string) $row['position']),
                (int) $row['request_id'],
            ))
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function requestOverviewRowsForPosition(array $position, Carbon $monthStart, Carbon $snapshotDate): Collection
    {
        $postingId = (int) ($position['posting']?->id ?? 0);
        $requests = $this->visibleRequestsForPosition($position, $snapshotDate);

        if ($postingId <= 0 || $requests->isEmpty()) {
            return collect();
        }

        $latestHistory = $this->latestHistoryForPostingUntil($postingId, $snapshotDate);
        $filledHistoriesByRequest = $this->allocatedHiredHistoriesByRequest($requests, $postingId, $snapshotDate);
        $positionNeeded = (int) ($position['needed'] ?? 0);

        if ($positionNeeded <= 0) {
            $positionNeeded = (int) $requests->sum(fn (RequestManPower $request): int => $this->requestNeededHeadcount($request));
        }

        $positionOutstanding = max(0, $positionNeeded - $this->hiredHistoriesForPostingUntil($postingId, $snapshotDate)->count());

        return $requests
            ->map(function (RequestManPower $request) use ($position, $monthStart, $snapshotDate, $latestHistory, $filledHistoriesByRequest, $positionOutstanding, $postingId): array {
                $requestId = (int) $request->getKey();
                $requestDate = $request->tanggal_pengajuan instanceof Carbon
                    ? $request->tanggal_pengajuan->copy()->startOfDay()
                    : null;
                $filledHistories = $filledHistoriesByRequest[$requestId] ?? collect();
                $joinHistories = $filledHistories
                    ->filter(function (JobApplicationHistory $history) use ($monthStart, $snapshotDate): bool {
                        $eventDate = $this->historyEventDate($history);

                        return $eventDate instanceof Carbon
                            && $eventDate->greaterThanOrEqualTo($monthStart)
                            && $eventDate->lessThanOrEqualTo($snapshotDate);
                    })
                    ->values();
                $needed = $this->requestNeededHeadcount($request);
                $fulfilled = min($needed, $filledHistories->count());
                $outstanding = max(0, $needed - $fulfilled);
                $inProgress = (int) ($position['statistics']['in_progress'] ?? 0);
                $latestJoinDate = $this->latestHistoryEventDate($joinHistories);
                $updateDate = $latestJoinDate ?? $this->historyEventDate($latestHistory) ?? $requestDate;

                return [
                    'request_id'            => $requestId,
                    'request_reference'     => $this->requestReferenceLabel($requestId),
                    'job_posting_id'        => $postingId,
                    'job_posting_reference' => $this->postingReferenceLabel($postingId),
                    'month_label'           => mb_strtoupper($snapshotDate->translatedFormat('F Y')),
                    'company'               => $this->uppercase($request->company?->name),
                    'request_date'          => $this->formatDate($requestDate),
                    'request_date_sort'     => $requestDate?->format('Y-m-d') ?? '9999-12-31',
                    'snapshot_date'         => $this->formatDate($snapshotDate),
                    'age_days'              => $requestDate instanceof Carbon ? $this->fulfillmentDays($requestDate, $snapshotDate) : 0,
                    'needed'                => $needed,
                    'join_month'            => $joinHistories->count(),
                    'fulfilled'             => $fulfilled,
                    'outstanding'           => $outstanding,
                    'in_progress'           => $inProgress,
                    'position'              => $this->uppercase($request->posisi_dibutuhkan ?? $position['posting']?->title),
                    'location'              => $this->uppercase($request->lokasi_penempatan ?? $position['posting']?->location),
                    'user'                  => $this->uppercase($request->nama_pengaju),
                    'pic'                   => $this->overviewPicLabel($joinHistories, $latestHistory),
                    'need_type'             => $this->needTypeLabel($request),
                    'request_status'        => $this->requestStatusLabel($request),
                    'fulfillment_status'    => $this->fulfillmentStatusLabel($request, $fulfilled, $outstanding),
                    'priority'              => $requestDate instanceof Carbon
                        ? $this->positionPriorityLabel(
                            $request->status === RequestManPowerStatus::HOLD ? $outstanding : $positionOutstanding,
                            $inProgress,
                            $requestDate,
                            $snapshotDate,
                            $request->status === RequestManPowerStatus::HOLD
                        )
                        : '-',
                    'is_on_hold'           => $request->status === RequestManPowerStatus::HOLD,
                    'replacement_note'     => $this->replacementNote($request),
                    'update_date'          => $this->formatDate($updateDate),
                    'candidate_names'      => $this->candidateNames($joinHistories),
                    'average_join_days'    => $requestDate instanceof Carbon ? $this->averageFulfillmentDays($joinHistories, $requestDate) : 0,
                ];
            })
            ->values();
    }

    /**
     * @return Collection<int, RequestManPower>
     */
    protected function visibleRequestsForPosition(array $position, Carbon $snapshotDate): Collection
    {
        $sourceRequest = $position['request'] ?? null;
        $requests = collect($position['requests'] ?? [])
            ->filter(fn (mixed $request): bool => $request instanceof RequestManPower && $this->requestIsVisibleAtSnapshot($request, $snapshotDate));

        if ($requests->isEmpty() && $sourceRequest instanceof RequestManPower && $this->requestIsVisibleAtSnapshot($sourceRequest, $snapshotDate)) {
            $requests->push($sourceRequest);
        }

        return $requests
            ->unique(fn (RequestManPower $request): int => (int) $request->getKey())
            ->sortBy(fn (RequestManPower $request): string => $this->requestSortKey($request))
            ->values();
    }

    protected function requestIsVisibleAtSnapshot(RequestManPower $request, Carbon $snapshotDate): bool
    {
        if ($request->trashed()) {
            return false;
        }

        if (! in_array($request->status, [
            RequestManPowerStatus::PENDING,
            RequestManPowerStatus::APPROVED,
            RequestManPowerStatus::HOLD,
        ], true)) {
            return false;
        }

        return $request->tanggal_pengajuan instanceof Carbon
            && $request->tanggal_pengajuan->copy()->startOfDay()->lessThanOrEqualTo($snapshotDate);
    }

    /**
     * @param  Collection<int, RequestManPower>  $requests
     * @return array<int, Collection<int, JobApplicationHistory>>
     */
    protected function allocatedHiredHistoriesByRequest(Collection $requests, int $postingId, Carbon $snapshotDate): array
    {
        $countedRequests = $requests
            ->filter(fn (RequestManPower $request): bool => in_array($request->status, [
                RequestManPowerStatus::APPROVED,
                RequestManPowerStatus::HOLD,
            ], true))
            ->values();

        if ($countedRequests->isEmpty() && $requests->isNotEmpty()) {
            $countedRequests = $requests->take(1)->values();
        }

        $hiredHistories = $this->hiredHistoriesForPostingUntil($postingId, $snapshotDate);
        $filledHistoriesByRequest = [];
        $historyIndex = 0;

        foreach ($countedRequests as $request) {
            $requestId = (int) $request->getKey();
            $filledHistoriesByRequest[$requestId] = collect();

            for ($slot = 0; $slot < $this->requestNeededHeadcount($request); $slot++) {
                $history = $hiredHistories->get($historyIndex);
                $historyIndex++;

                if (! $history instanceof JobApplicationHistory) {
                    break;
                }

                $filledHistoriesByRequest[$requestId]->push($history);
            }
        }

        if ($historyIndex < $hiredHistories->count() && $countedRequests->isNotEmpty()) {
            $lastRequestId = (int) $countedRequests->last()->getKey();
            $filledHistoriesByRequest[$lastRequestId] ??= collect();

            while ($historyIndex < $hiredHistories->count()) {
                $history = $hiredHistories->get($historyIndex);
                $historyIndex++;

                if ($history instanceof JobApplicationHistory) {
                    $filledHistoriesByRequest[$lastRequestId]->push($history);
                }
            }
        }

        return $filledHistoriesByRequest;
    }

    /**
     * @return Collection<int, JobApplicationHistory>
     */
    protected function hiredHistoriesForPostingUntil(int $postingId, Carbon $snapshotDate): Collection
    {
        return $this->hiredHistories()
            ->filter(function (JobApplicationHistory $history) use ($postingId, $snapshotDate): bool {
                $eventDate = $this->historyEventDate($history);

                return (int) ($history->jobApplication?->job_posting_id ?? 0) === $postingId
                    && $eventDate instanceof Carbon
                    && $eventDate->lessThanOrEqualTo($snapshotDate);
            })
            ->values();
    }

    /**
     * @return Collection<int, JobApplicationHistory>
     */
    protected function histories(): Collection
    {
        if ($this->histories instanceof Collection) {
            return $this->histories;
        }

        $postingIds = $this->positionsByPostingId->keys()->map(fn (mixed $postingId): int => (int) $postingId)->all();

        if ($postingIds === []) {
            $this->histories = collect();

            return $this->histories;
        }

        $this->histories = JobApplicationHistory::query()
            ->whereIn('job_application_id', function ($query) use ($postingIds): void {
                $query->select('id')
                    ->from('rekrutmen_job_applications')
                    ->whereIn('job_posting_id', $postingIds)
                    ->whereNull('deleted_at');
            })
            ->with(['jobApplication', 'performer'])
            ->orderBy('created_at')
            ->get();

        return $this->histories;
    }

    /**
     * @return Collection<int, JobApplicationHistory>
     */
    protected function hiredHistories(): Collection
    {
        return $this->histories()
            ->filter(fn (JobApplicationHistory $history): bool => $history->status === JobApplicationStatus::HIRED
                && $history->jobApplication?->status === JobApplicationStatus::HIRED)
            ->sortBy(fn (JobApplicationHistory $history): string => sprintf(
                '%s|%s',
                $this->historyEventDate($history)?->format('Y-m-d H:i:s') ?? '',
                (string) $history->getKey(),
            ))
            ->unique('job_application_id')
            ->values();
    }

    protected function requestSortKey(RequestManPower $request): string
    {
        return sprintf(
            '%s|%010d',
            $request->tanggal_pengajuan?->format('Y-m-d') ?? '9999-12-31',
            (int) $request->getKey(),
        );
    }

    protected function requestNeededHeadcount(RequestManPower $request): int
    {
        return max(1, (int) ($request->jumlah_karyawan_dibutuhkan ?? 1));
    }

    protected function fulfillmentStatusLabel(RequestManPower $request, int $fulfilled, int $outstanding): string
    {
        $status = match (true) {
            $request->status === RequestManPowerStatus::PENDING => 'pending_approval',
            $request->status === RequestManPowerStatus::HOLD    => 'on_hold',
            $outstanding === 0                                  => 'fulfilled',
            $fulfilled > 0                                      => 'partial',
            default                                             => 'open',
        };

        return $this->uppercase(__('rekrutmen::livewire/recruitment-progress-report.workflow.mpp.status.'.$status));
    }

    /**
     * @param  Collection<int, JobApplicationHistory>  $joinHistories
     */
    protected function overviewPicLabel(Collection $joinHistories, ?JobApplicationHistory $latestHistory): string
    {
        $joinPerformers = $joinHistories
            ->map(fn (JobApplicationHistory $history): string => $this->uppercase($history->performer?->name))
            ->reject(fn (string $name): bool => $name === '-')
            ->unique()
            ->values();

        if ($joinPerformers->isNotEmpty()) {
            return $joinPerformers->implode("\n");
        }

        return $this->uppercase($latestHistory?->performer?->name);
    }

    /**
     * @param  Collection<int, JobApplicationHistory>  $joinHistories
     */
    protected function candidateNames(Collection $joinHistories): string
    {
        $names = $joinHistories
            ->map(fn (JobApplicationHistory $history): string => $this->uppercase($history->jobApplication?->full_name))
            ->reject(fn (string $name): bool => $name === '-')
            ->unique()
            ->values();

        return $names->isEmpty() ? '-' : $names->implode("\n");
    }

    /**
     * @param  Collection<int, JobApplicationHistory>  $joinHistories
     */
    protected function averageFulfillmentDays(Collection $joinHistories, Carbon $requestDate): int
    {
        $days = $joinHistories
            ->map(fn (JobApplicationHistory $history): ?int => ($eventDate = $this->historyEventDate($history)) instanceof Carbon
                ? $this->fulfillmentDays($requestDate, $eventDate)
                : null)
            ->filter(fn (?int $days): bool => $days !== null);

        return $days->isEmpty() ? 0 : (int) round($days->avg());
    }

    /**
     * @param  Collection<int, JobApplicationHistory>  $histories
     */
    protected function latestHistoryEventDate(Collection $histories): ?Carbon
    {
        return $histories
            ->map(fn (JobApplicationHistory $history): ?Carbon => $this->historyEventDate($history))
            ->filter()
            ->sortByDesc(fn (Carbon $date): string => $date->format('Y-m-d H:i:s'))
            ->first();
    }

    protected function latestHistoryForPostingUntil(int $postingId, Carbon $snapshotDate): ?JobApplicationHistory
    {
        return $this->histories()
            ->filter(function (JobApplicationHistory $history) use ($postingId, $snapshotDate): bool {
                $eventDate = $this->historyEventDate($history);

                return (int) ($history->jobApplication?->job_posting_id ?? 0) === $postingId
                    && $eventDate instanceof Carbon
                    && $eventDate->lessThanOrEqualTo($snapshotDate);
            })
            ->sortByDesc(fn (JobApplicationHistory $history): string => $this->historyEventDate($history)?->format('Y-m-d H:i:s') ?? '')
            ->first();
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

    protected function needTypeLabel(?RequestManPower $request): string
    {
        return filled($request?->status_kebutuhan?->value)
            ? mb_strtoupper((string) $request->status_kebutuhan->value)
            : '-';
    }

    protected function replacementNote(?RequestManPower $request): string
    {
        if ($request?->status_kebutuhan?->value === 'Replacement' && filled($request->nama_karyawan_replacement)) {
            return 'REP : '.$this->uppercase((string) $request->nama_karyawan_replacement);
        }

        if ($request?->status_kebutuhan?->value === 'New Hiring') {
            return 'NEW HIRING';
        }

        return '-';
    }

    protected function requestStatusLabel(?RequestManPower $request): string
    {
        return filled($request?->status?->value)
            ? mb_strtoupper((string) $request->status->getLabel())
            : '-';
    }

    /**
     * @param  Collection<int, string>  $companies
     */
    protected function companyScopeLabel(Collection $companies): string
    {
        if ($companies->isEmpty()) {
            return '-';
        }

        if ($companies->count() <= 2) {
            return $companies->implode(', ');
        }

        return $companies->count().' perusahaan';
    }

    protected function monthlyStatusLabel(int $openTotal, int $joinTotal, int $holdTotal): string
    {
        if ($openTotal <= 0) {
            return 'Semua kebutuhan pada snapshot ini sudah terpenuhi';
        }

        if ($holdTotal > 0) {
            return 'Ada kebutuhan di-hold, outstanding dipisahkan dari kebutuhan aktif';
        }

        if ($joinTotal > 0) {
            return 'Masih ada kebutuhan terbuka, namun sudah ada progres join';
        }

        return 'Belum ada realisasi join, fokus sourcing dan follow up kandidat';
    }

    protected function filterLine(): string
    {
        return sprintf(
            '%s | Perusahaan: %s | Posisi: %s',
            $this->filters['period_label'] ?? 'Semua Periode',
            $this->filters['company_label'] ?? 'Semua Perusahaan',
            $this->filters['position_label'] ?? 'Semua Posisi',
        );
    }

    protected function finalSnapshotDate(): Carbon
    {
        return $this->parseDate($this->filters['date_to'] ?? null)
            ?? $this->parseDate($this->filters['date_from'] ?? null)
            ?? now();
    }

    protected function parseDate(?string $date): ?Carbon
    {
        if (! is_string($date) || trim($date) === '') {
            return null;
        }

        return Carbon::parse($date);
    }

    protected function formatDate(mixed $date): string
    {
        if ($date instanceof Carbon) {
            return $date->format('d M Y');
        }

        return '-';
    }

    protected function uppercase(?string $value): string
    {
        if (! is_string($value) || trim($value) === '') {
            return '-';
        }

        return mb_strtoupper($value);
    }

    protected function requestReferenceLabel(mixed $requestId): string
    {
        $id = is_numeric($requestId) ? (int) $requestId : 0;

        return $id > 0 ? 'MPP-UID-'.str_pad((string) $id, 6, '0', STR_PAD_LEFT) : '-';
    }

    protected function postingReferenceLabel(mixed $postingId): string
    {
        $id = is_numeric($postingId) ? (int) $postingId : 0;

        return $id > 0 ? 'LOWONGAN-UID-'.str_pad((string) $id, 6, '0', STR_PAD_LEFT) : '-';
    }

    protected function positionPriorityLabel(int $outstanding, int $inProgress, Carbon $requestDate, Carbon $snapshotDate, bool $isOnHold): string
    {
        $ageDays = $this->fulfillmentDays($requestDate, $snapshotDate);

        if ($isOnHold) {
            return 'Hold - menunggu keputusan user';
        }

        if ($outstanding > 0 && $inProgress === 0 && $ageDays >= 30) {
            return 'Perlu eskalasi segera';
        }

        if ($outstanding > 0 && $inProgress === 0) {
            return 'Perlu sourcing kandidat';
        }

        if ($inProgress >= $outstanding) {
            return 'Pipeline cukup, lanjut monitor';
        }

        return 'Pipeline belum cukup, perlu percepatan';
    }

    protected function fulfillmentDays(Carbon $from, Carbon $to): int
    {
        return (int) $from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay());
    }
}

class RecruitmentProgressMonthlyOverviewSheet implements FromArray, WithColumnWidths, WithStyles, WithTitle
{
    /**
     * @var Collection<int, array<string, mixed>>
     */
    protected Collection $sections;

    /**
     * @var array<int, string>
     */
    protected array $titleRows = [];

    /**
     * @var array<int, string>
     */
    protected array $headerRows = [];

    /**
     * @var array<int, string>
     */
    protected array $totalRows = [];

    /**
     * @param  Collection<int, array<string, mixed>>  $sections
     */
    public function __construct(Collection $sections)
    {
        $this->sections = $sections;
    }

    /**
     * @return array<int, array<int, int|string|null>>
     */
    public function array(): array
    {
        $rows = [];
        $this->titleRows = [];
        $this->headerRows = [];
        $this->totalRows = [];
        $currentRow = 1;

        foreach ($this->sections as $section) {
            $this->titleRows[$currentRow] = 'U';
            $rows[] = ['OVERVIEW MPP BULAN '.$section['month_label']];
            $currentRow++;

            $this->headerRows[$currentRow] = 'U';
            $rows[] = $this->overviewHeaders();
            $currentRow++;

            $overviewRows = collect($section['overview_rows']);

            if ($overviewRows->isEmpty()) {
                $rows[] = ['Tidak ada data MPP atau karyawan join pada bulan ini.', null, null, null, null, null, null, 0, 0, 0, 0, null, null, null, null, null, null, null, null, null, null];
                $currentRow++;
            } else {
                foreach ($overviewRows as $row) {
                    $rows[] = [
                        $row['month_label'],
                        $row['request_reference'],
                        $row['job_posting_reference'],
                        $row['company'],
                        $row['request_date'],
                        $row['snapshot_date'],
                        $row['age_days'],
                        $row['needed'],
                        $row['join_month'],
                        $row['fulfilled'],
                        $row['outstanding'],
                        $row['position'],
                        $row['location'],
                        $row['user'],
                        $row['pic'],
                        $row['need_type'],
                        $row['request_status'],
                        $row['fulfillment_status'],
                        $row['replacement_note'],
                        $row['update_date'],
                        $row['candidate_names'],
                    ];
                    $currentRow++;
                }
            }

            $this->totalRows[$currentRow] = 'U';
            $rows[] = [
                'TOTAL',
                null,
                null,
                null,
                null,
                null,
                null,
                (int) $overviewRows->sum(fn (array $row): int => (int) $row['needed']),
                $section['join_total'],
                (int) $overviewRows->sum(fn (array $row): int => (int) $row['fulfilled']),
                $section['open_total'],
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
            ];
            $currentRow++;
            $rows[] = [null];
            $currentRow++;
        }

        return $rows;
    }

    /**
     * @return array<int, string>
     */
    protected function overviewHeaders(): array
    {
        return [
            'BULAN',
            'ID PERMINTAAN MPP',
            'ID LOWONGAN',
            'BADAN USAHA',
            'TANGGAL REQ',
            'SNAPSHOT',
            'UMUR MPP/DAY',
            'KEBUTUHAN MPP',
            'JOIN BULAN INI',
            'JOIN S/D SNAPSHOT',
            'SISA MPP',
            'POSISI',
            'PENEMPATAN',
            'USER',
            'PIC TERAKHIR / JOIN',
            'REPLACEMENT/NEW HIRING',
            'STATUS REQUEST',
            'STATUS PEMENUHAN',
            'KETERANGAN REPLACEMENT',
            'TANGGAL UPDATE PROGRES',
            'KARYAWAN JOIN BULAN INI',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 16,
            'B' => 18,
            'C' => 18,
            'D' => 28,
            'E' => 16,
            'F' => 16,
            'G' => 14,
            'H' => 14,
            'I' => 14,
            'J' => 16,
            'K' => 12,
            'L' => 28,
            'M' => 22,
            'N' => 18,
            'O' => 22,
            'P' => 24,
            'Q' => 18,
            'R' => 20,
            'S' => 28,
            'T' => 18,
            'U' => 30,
        ];
    }

    public function title(): string
    {
        return 'Overview MPP';
    }

    /**
     * @return array<int, mixed>
     */
    public function styles(Worksheet $sheet): array
    {
        $lastRow = $sheet->getHighestRow();

        foreach ($this->titleRows as $row => $lastColumn) {
            $sheet->mergeCells("A{$row}:{$lastColumn}{$row}");
            $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->applyFromArray([
                'font' => [
                    'bold'  => true,
                    'size'  => 16,
                    'color' => ['rgb' => '0F172A'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
            ]);
        }

        foreach ($this->headerRows as $row => $lastColumn) {
            $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->applyFromArray([
                'font' => [
                    'bold'  => true,
                    'color' => ['rgb' => '0F172A'],
                ],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FFF59D'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                    'wrapText'   => true,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color'       => ['rgb' => 'D6D3D1'],
                    ],
                ],
            ]);
        }

        foreach ($this->totalRows as $row => $lastColumn) {
            $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->applyFromArray([
                'font' => [
                    'bold' => true,
                ],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FEF3C7'],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color'       => ['rgb' => 'D6D3D1'],
                    ],
                ],
            ]);
        }

        $sheet->getStyle("A1:U{$lastRow}")->applyFromArray([
            'alignment' => [
                'vertical' => Alignment::VERTICAL_TOP,
                'wrapText' => true,
            ],
        ]);

        $sheet->getStyle("A1:U{$lastRow}")
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        $sheet->getStyle("B1:C{$lastRow}")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle("E1:K{$lastRow}")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        return [];
    }
}

class RecruitmentProgressWorkbookSheet implements FromArray, WithColumnWidths, WithStyles, WithTitle
{
    /**
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, int|string>>  $rows
     * @param  array<string, int>  $columnWidths
     * @param  array<int, string>  $centeredColumns
     */
    public function __construct(
        protected string $title,
        protected string $summaryLine,
        protected string $filterLine,
        protected string $noteLine,
        protected array $headers,
        protected array $rows,
        protected array $columnWidths = [],
        protected array $centeredColumns = [],
    ) {}

    /**
     * @return array<int, array<int, int|string|null>>
     */
    public function array(): array
    {
        return [
            [$this->title],
            [$this->summaryLine],
            [$this->filterLine],
            [$this->noteLine],
            $this->headers,
            ...$this->rows,
        ];
    }

    public function columnWidths(): array
    {
        return $this->columnWidths;
    }

    public function title(): string
    {
        return mb_substr($this->title, 0, 31);
    }

    /**
     * @return array<int, mixed>
     */
    public function styles(Worksheet $sheet): array
    {
        $lastColumn = $sheet->getHighestColumn();
        $lastRow = $sheet->getHighestRow();
        $headerRow = 5;
        $dataStartRow = $headerRow + 1;

        foreach ([1, 2, 3, 4] as $row) {
            $sheet->mergeCells("A{$row}:{$lastColumn}{$row}");
        }

        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'font' => [
                'bold'  => true,
                'size'  => 16,
                'color' => ['rgb' => '0F172A'],
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FEF3C7'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['rgb' => 'D6D3D1'],
                ],
            ],
        ]);

        $sheet->getStyle("A2:{$lastColumn}2")->applyFromArray([
            'font' => [
                'bold'  => true,
                'size'  => 11,
                'color' => ['rgb' => '0F172A'],
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FEF3C7'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical'   => Alignment::VERTICAL_CENTER,
                'wrapText'   => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['rgb' => 'D6D3D1'],
                ],
            ],
        ]);

        $sheet->getStyle("A3:{$lastColumn}3")->applyFromArray([
            'font' => [
                'size'  => 10,
                'color' => ['rgb' => '475569'],
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFFBEB'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical'   => Alignment::VERTICAL_CENTER,
                'wrapText'   => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['rgb' => 'D6D3D1'],
                ],
            ],
        ]);

        $sheet->getStyle("A4:{$lastColumn}4")->applyFromArray([
            'font' => [
                'size'   => 10,
                'italic' => true,
                'color'  => ['rgb' => '475569'],
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F8FAFC'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical'   => Alignment::VERTICAL_CENTER,
                'wrapText'   => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['rgb' => 'D6D3D1'],
                ],
            ],
        ]);

        $sheet->getStyle("A{$headerRow}:{$lastColumn}{$headerRow}")->applyFromArray([
            'font' => [
                'bold'  => true,
                'color' => ['rgb' => '0F172A'],
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFF59D'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
                'wrapText'   => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['rgb' => 'D6D3D1'],
                ],
            ],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(24);
        $sheet->getRowDimension(2)->setRowHeight(22);
        $sheet->getRowDimension(3)->setRowHeight(20);
        $sheet->getRowDimension(4)->setRowHeight(34);
        $sheet->getRowDimension($headerRow)->setRowHeight(24);

        if ($lastRow >= $dataStartRow) {
            for ($row = $dataStartRow; $row <= $lastRow; $row++) {
                $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->applyFromArray([
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_TOP,
                        'wrapText' => true,
                    ],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => [
                            'rgb' => ($row - $dataStartRow) % 2 === 0 ? 'FFFFFFFF' : 'FFFBEB',
                        ],
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color'       => ['rgb' => 'E7E5E4'],
                        ],
                    ],
                ]);

                if (strtoupper((string) $sheet->getCell("A{$row}")->getValue()) === 'TOTAL') {
                    $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->applyFromArray([
                        'font' => [
                            'bold' => true,
                        ],
                        'fill' => [
                            'fillType'   => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'FEF3C7'],
                        ],
                    ]);
                }
            }

            foreach ($this->centeredColumns as $column) {
                $sheet->getStyle("{$column}{$dataStartRow}:{$column}{$lastRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }

            $sheet->setAutoFilter("A{$headerRow}:{$lastColumn}{$lastRow}");
            $sheet->freezePane("A{$dataStartRow}");
        }

        $sheet->getSheetView()->setZoomScale(90);
        $sheet->getStyle("A1:{$lastColumn}{$lastRow}")
            ->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER);

        return [];
    }
}
