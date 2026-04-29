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
     *     stage_label?: string,
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
     * @var Collection<int, array<string, mixed>>|null
     */
    protected ?Collection $openPositionSnapshots = null;

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
     *      stage_label?: string,
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
                noteLine: 'Ringkasan ini hanya membawa kebutuhan yang masih outstanding pada snapshot bulan tersebut. Posisi yang sudah terpenuhi penuh tidak dibawa lagi ke bulan berikutnya.',
                headers: [
                    'Bulan',
                    'Snapshot',
                    'Posisi Open',
                    'Outstanding MPP',
                    'Outstanding Hold',
                    'Karyawan Join',
                    'Rata-rata Lama Pemenuhan (Hari)',
                    'Perusahaan',
                    'Status Ringkas',
                ],
                rows: $this->monthlySummaryRows($monthlySections)->all(),
                columnWidths: [
                    'A' => 16,
                    'B' => 16,
                    'C' => 12,
                    'D' => 16,
                    'E' => 16,
                    'F' => 14,
                    'G' => 24,
                    'H' => 28,
                    'I' => 36,
                ],
                centeredColumns: ['C', 'D', 'E', 'F', 'G'],
            ),
            new RecruitmentProgressWorkbookSheet(
                title: 'Detail Posisi',
                summaryLine: $this->positionSummaryLine(),
                filterLine: $this->filterLine(),
                noteLine: 'Detail posisi hanya menampilkan kebutuhan yang masih outstanding pada snapshot akhir periode, agar user fokus pada posisi yang masih perlu ditindaklanjuti.',
                headers: [
                    'Perusahaan',
                    'Tanggal Req',
                    'Umur Req (Hari)',
                    'Posisi',
                    'Penempatan',
                    'Kebutuhan',
                    'Hired s/d Snapshot',
                    'Outstanding',
                    'Dalam Proses Saat Ini',
                    'User',
                    'PIC Terakhir',
                    'Jenis Kebutuhan',
                    'Status Permintaan',
                    'Prioritas Tindak Lanjut',
                    'Update Progres Terakhir',
                ],
                rows: $this->positionSnapshotRows()->all(),
                columnWidths: [
                    'A' => 24,
                    'B' => 16,
                    'C' => 14,
                    'D' => 28,
                    'E' => 24,
                    'F' => 12,
                    'G' => 16,
                    'H' => 12,
                    'I' => 18,
                    'J' => 18,
                    'K' => 18,
                    'L' => 18,
                    'M' => 18,
                    'N' => 28,
                    'O' => 18,
                ],
                centeredColumns: ['C', 'F', 'G', 'H', 'I'],
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
                    'Posisi',
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
                    'D' => 28,
                    'E' => 20,
                    'F' => 24,
                    'G' => 34,
                    'H' => 14,
                    'I' => 10,
                    'J' => 14,
                    'K' => 12,
                    'L' => 18,
                ],
                centeredColumns: ['H', 'I', 'J', 'K'],
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
                $openRows = $this->openRowsForMonth($month['snapshot_date']);
                $joinRows = $this->joinRowsForMonth($month['start_date'], $month['snapshot_date']);

                return [
                    'month_key'     => $month['month_key'],
                    'month_label'   => $month['month_label'],
                    'snapshot_date' => $month['snapshot_date'],
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
            $openRows = collect($section['open_rows']);
            $joinDays = collect($section['join_rows'])
                ->pluck('days')
                ->filter(fn (mixed $days): bool => is_int($days) || is_float($days));
            $companies = $openRows
                ->pluck('company')
                ->merge(collect($section['join_rows'])->pluck('company'))
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
                $section['open_total'],
                $holdTotal,
                $section['join_total'],
                $joinDays->isEmpty() ? 0 : (int) round($joinDays->avg()),
                $this->companyScopeLabel($companies),
                $this->monthlyStatusLabel($section['open_total'], $section['join_total'], $holdTotal),
            ];
        })->values();
    }

    protected function monthlySummaryLine(Collection $monthlySections): string
    {
        $holdTotal = (int) $monthlySections->sum(
            fn (array $section): int => (int) collect($section['open_rows'])
                ->where('is_on_hold', true)
                ->sum('quantity')
        );

        return sprintf(
            'Ringkasan: %d bulan | Outstanding MPP %d | Outstanding hold %d | Karyawan join %d',
            $monthlySections->count(),
            (int) $monthlySections->sum('open_total'),
            $holdTotal,
            (int) $monthlySections->sum('join_total'),
        );
    }

    /**
     * @return Collection<int, array<int, int|string>>
     */
    protected function positionSnapshotRows(): Collection
    {
        return $this->openPositionSnapshots()
            ->map(fn (array $row): array => [
                $row['company'],
                $row['request_date'],
                $row['age_days'],
                $row['position'],
                $row['location'],
                $row['needed'],
                $row['hired'],
                $row['outstanding'],
                $row['in_progress'],
                $row['user'],
                $row['pic'],
                $row['need_type'],
                $row['request_status'],
                $row['priority'],
                $row['update_date'],
            ])
            ->sortBy(fn (array $row): string => sprintf(
                '%04d|%04d|%s|%s',
                9999 - (int) $row[2],
                9999 - (int) $row[7],
                mb_strtolower((string) $row[0]),
                mb_strtolower((string) $row[3]),
            ))
            ->values();
    }

    protected function positionSummaryLine(): string
    {
        $snapshotDate = $this->finalSnapshotDate();
        $openPositions = $this->openPositionSnapshots();
        $outstanding = (int) $openPositions->sum('outstanding');
        $urgent = $openPositions->where('priority', 'Perlu eskalasi segera')->count();
        $hold = (int) $openPositions
            ->where('is_on_hold', true)
            ->sum('outstanding');

        return sprintf(
            'Snapshot %s | Posisi open %d | Outstanding MPP %d | Outstanding hold %d | Perlu eskalasi %d',
            $this->formatDate($snapshotDate),
            $openPositions->count(),
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
        return $this->activities
            ->map(function (array $activity): array {
                $postingId = (int) ($activity['job_posting_id'] ?? 0);
                $position = $this->positionsByPostingId->get($postingId);
                $request = is_array($position) ? ($position['request'] ?? null) : null;
                $activityDate = $activity['activity_date'] instanceof Carbon
                    ? $activity['activity_date']
                    : null;

                return [
                    $this->formatDate($activityDate),
                    $activityDate?->format('M Y') ?? '-',
                    $request?->company?->name ?? '-',
                    $activity['job_posting']?->title ?? '-',
                    $activity['to_stage']?->name ?? '-',
                    $activity['activity_title'] ?? '-',
                    $activity['summary'] ?? '-',
                    (int) ($activity['total_candidates'] ?? 0),
                    (int) ($activity['passed_count'] ?? 0),
                    (int) ($activity['failed_count'] ?? 0),
                    (int) ($activity['pending_count'] ?? 0),
                    $activity['performer']?->name ?? '-',
                ];
            })
            ->values();
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
    protected function openRowsForMonth(Carbon $snapshotDate): Collection
    {
        return $this->positions
            ->map(function (array $position) use ($snapshotDate): ?array {
                $postingId = (int) ($position['posting']?->id ?? 0);
                $request = $position['request'] ?? null;
                $requestDate = $request?->tanggal_pengajuan;
                $isOnHold = $request?->status === RequestManPowerStatus::HOLD;

                if (! $requestDate instanceof Carbon || $requestDate->greaterThan($snapshotDate)) {
                    return null;
                }

                $needed = (int) ($position['needed'] ?? 0);
                $hired = $this->hiredCountForPostingUntil($postingId, $snapshotDate);
                $outstanding = max(0, $needed - $hired);

                if ($outstanding <= 0) {
                    return null;
                }

                $latestHistory = $this->latestHistoryForPostingUntil($postingId, $snapshotDate);
                $updateDate = $this->historyEventDate($latestHistory) ?? $requestDate;

                return [
                    'company'          => $this->uppercase($request?->company?->name),
                    'request_date'     => $this->formatDate($requestDate),
                    'snapshot_date'    => $this->formatDate($snapshotDate),
                    'days'             => $this->fulfillmentDays($requestDate, $snapshotDate),
                    'quantity'         => $outstanding,
                    'position'         => $this->uppercase($position['posting']?->title),
                    'location'         => $this->uppercase($position['posting']?->location),
                    'user'             => $this->uppercase($request?->nama_pengaju),
                    'pic'              => $this->uppercase($latestHistory?->performer?->name),
                    'need_type'        => $this->needTypeLabel($request),
                    'request_status'   => $this->requestStatusLabel($request),
                    'is_on_hold'       => $isOnHold,
                    'replacement_note' => $this->replacementNote($request),
                    'update_date'      => $this->formatDate($updateDate),
                ];
            })
            ->filter()
            ->sortBy(fn (array $row): string => sprintf(
                '%s|%s|%s',
                mb_strtolower($row['company']),
                mb_strtolower($row['position']),
                mb_strtolower($row['location']),
            ))
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function joinRowsForMonth(Carbon $monthStart, Carbon $snapshotDate): Collection
    {
        return $this->hiredHistories()
            ->filter(function (JobApplicationHistory $history) use ($monthStart, $snapshotDate): bool {
                $eventDate = $this->historyEventDate($history);

                return $eventDate instanceof Carbon
                    && $eventDate->greaterThanOrEqualTo($monthStart)
                    && $eventDate->lessThanOrEqualTo($snapshotDate);
            })
            ->map(function (JobApplicationHistory $history): ?array {
                $postingId = (int) ($history->jobApplication?->job_posting_id ?? 0);
                $position = $this->positionsByPostingId->get($postingId);

                if (! is_array($position)) {
                    return null;
                }

                $request = $position['request'] ?? null;
                $requestDate = $request?->tanggal_pengajuan;
                $joinDate = $this->historyEventDate($history);
                $isOnHold = $request?->status === RequestManPowerStatus::HOLD;

                if (! $requestDate instanceof Carbon || ! $joinDate instanceof Carbon) {
                    return null;
                }

                return [
                    'company'          => $this->uppercase($request?->company?->name),
                    'request_date'     => $this->formatDate($requestDate),
                    'join_date'        => $this->formatDate($joinDate),
                    'days'             => $this->fulfillmentDays($requestDate, $joinDate),
                    'quantity'         => 1,
                    'position'         => $this->uppercase($position['posting']?->title),
                    'location'         => $this->uppercase($position['posting']?->location),
                    'user'             => $this->uppercase($request?->nama_pengaju),
                    'pic'              => $this->uppercase($history->performer?->name),
                    'need_type'        => $this->needTypeLabel($request),
                    'request_status'   => $this->requestStatusLabel($request),
                    'is_on_hold'       => $isOnHold,
                    'replacement_note' => $this->replacementNote($request),
                    'update_date'      => $this->formatDate($joinDate),
                    'candidate_name'   => $this->uppercase($history->jobApplication?->full_name),
                ];
            })
            ->filter()
            ->sortBy(fn (array $row): string => sprintf(
                '%s|%s|%s',
                mb_strtolower($row['company']),
                mb_strtolower($row['position']),
                $row['join_date'],
            ))
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
            ->filter(fn (JobApplicationHistory $history): bool => $history->status === JobApplicationStatus::HIRED)
            ->sortBy(fn (JobApplicationHistory $history): string => sprintf(
                '%s|%s',
                $this->historyEventDate($history)?->format('Y-m-d H:i:s') ?? '',
                (string) $history->getKey(),
            ))
            ->unique('job_application_id')
            ->values();
    }

    protected function hiredCountForPostingUntil(int $postingId, Carbon $snapshotDate): int
    {
        return $this->hiredHistories()
            ->filter(function (JobApplicationHistory $history) use ($postingId, $snapshotDate): bool {
                $eventDate = $this->historyEventDate($history);

                return (int) ($history->jobApplication?->job_posting_id ?? 0) === $postingId
                    && $eventDate instanceof Carbon
                    && $eventDate->lessThanOrEqualTo($snapshotDate);
            })
            ->count();
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
            '%s | Perusahaan: %s | Posisi: %s | Tahap: %s',
            $this->filters['period_label'] ?? 'Semua Periode',
            $this->filters['company_label'] ?? 'Semua Perusahaan',
            $this->filters['position_label'] ?? 'Semua Posisi',
            $this->filters['stage_label'] ?? 'Semua Tahap',
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

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function openPositionSnapshots(): Collection
    {
        if ($this->openPositionSnapshots instanceof Collection) {
            return $this->openPositionSnapshots;
        }

        $snapshotDate = $this->finalSnapshotDate();

        $this->openPositionSnapshots = $this->positions
            ->map(function (array $position) use ($snapshotDate): ?array {
                $postingId = (int) ($position['posting']?->id ?? 0);
                $request = $position['request'] ?? null;
                $requestDate = $request?->tanggal_pengajuan;
                $isOnHold = $request?->status === RequestManPowerStatus::HOLD;

                if (! $requestDate instanceof Carbon || $requestDate->greaterThan($snapshotDate)) {
                    return null;
                }

                $latestHistory = $this->latestHistoryForPostingUntil($postingId, $snapshotDate);
                $needed = (int) ($position['needed'] ?? 0);
                $hired = $this->hiredCountForPostingUntil($postingId, $snapshotDate);
                $outstanding = max(0, $needed - $hired);

                if ($outstanding <= 0) {
                    return null;
                }

                $inProgress = (int) ($position['statistics']['in_progress'] ?? 0);

                return [
                    'company'        => $request?->company?->name ?? '-',
                    'request_date'   => $this->formatDate($requestDate),
                    'age_days'       => $this->fulfillmentDays($requestDate, $snapshotDate),
                    'position'       => $position['posting']?->title ?? '-',
                    'location'       => $position['posting']?->location ?? '-',
                    'needed'         => $needed,
                    'hired'          => $hired,
                    'outstanding'    => $outstanding,
                    'in_progress'    => $inProgress,
                    'user'           => $request?->nama_pengaju ?? '-',
                    'pic'            => $latestHistory?->performer?->name ?? '-',
                    'need_type'      => $this->needTypeLabel($request),
                    'request_status' => $this->requestStatusLabel($request),
                    'is_on_hold'     => $isOnHold,
                    'priority'       => $this->positionPriorityLabel($outstanding, $inProgress, $requestDate, $snapshotDate, $isOnHold),
                    'update_date'    => $this->formatDate($this->historyEventDate($latestHistory) ?? $requestDate),
                ];
            })
            ->filter()
            ->values();

        return $this->openPositionSnapshots;
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
            $this->titleRows[$currentRow] = 'M';
            $rows[] = ['MPP BULAN '.$section['month_label']];
            $currentRow++;

            $this->headerRows[$currentRow] = 'M';
            $rows[] = $this->mppHeaders();
            $currentRow++;

            $openRows = collect($section['open_rows']);

            if ($openRows->isEmpty()) {
                $rows[] = ['Tidak ada kebutuhan MPP terbuka pada bulan ini.', null, null, null, 0, null, null, null, null, null, null, null, null];
                $currentRow++;
            } else {
                foreach ($openRows as $row) {
                    $rows[] = [
                        $row['company'],
                        $row['request_date'],
                        $row['snapshot_date'],
                        $row['days'],
                        $row['quantity'],
                        $row['position'],
                        $row['location'],
                        $row['user'],
                        $row['pic'],
                        $row['need_type'],
                        $row['request_status'],
                        $row['replacement_note'],
                        $row['update_date'],
                    ];
                    $currentRow++;
                }
            }

            $this->totalRows[$currentRow] = 'M';
            $rows[] = ['TOTAL', null, null, null, $section['open_total'], null, null, null, null, null, null, null, null];
            $currentRow++;
            $rows[] = [null];
            $currentRow++;

            $this->titleRows[$currentRow] = 'N';
            $rows[] = ['KARYAWAN JOIN BULAN '.$section['month_label']];
            $currentRow++;

            $this->headerRows[$currentRow] = 'N';
            $rows[] = $this->joinHeaders();
            $currentRow++;

            $joinRows = collect($section['join_rows']);

            if ($joinRows->isEmpty()) {
                $rows[] = ['Belum ada karyawan join pada bulan ini.', null, null, null, 0, null, null, null, null, null, null, null, null, null];
                $currentRow++;
            } else {
                foreach ($joinRows as $row) {
                    $rows[] = [
                        $row['company'],
                        $row['request_date'],
                        $row['join_date'],
                        $row['days'],
                        $row['quantity'],
                        $row['position'],
                        $row['location'],
                        $row['user'],
                        $row['pic'],
                        $row['need_type'],
                        $row['request_status'],
                        $row['replacement_note'],
                        $row['update_date'],
                        $row['candidate_name'],
                    ];
                    $currentRow++;
                }
            }

            $this->totalRows[$currentRow] = 'N';
            $rows[] = ['TOTAL', null, null, null, $section['join_total'], null, null, null, null, null, null, null, null, null];
            $currentRow++;
            $rows[] = [null];
            $currentRow++;
        }

        return $rows;
    }

    /**
     * @return array<int, string>
     */
    protected function mppHeaders(): array
    {
        return $this->headers('TANGGAL SAAT INI');
    }

    /**
     * @return array<int, string>
     */
    protected function joinHeaders(): array
    {
        return [
            ...$this->headers('JOIN DATE'),
            'NAMA KARYAWAN JOIN',
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function headers(string $thirdColumnLabel): array
    {
        return [
            'BADAN USAHA',
            'TANGGAL REQ',
            $thirdColumnLabel,
            'LAMA PEMENUHAN/DAY',
            'JUMLAH',
            'POSISI',
            'PENEMPATAN',
            'USER',
            'PIC',
            'REPLACEMENT/NEW HIRING',
            'STATUS REQUEST',
            'KETERANGAN REPLACEMENT',
            'TANGGAL UPDATE PROGRES',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 28,
            'B' => 16,
            'C' => 16,
            'D' => 20,
            'E' => 10,
            'F' => 28,
            'G' => 22,
            'H' => 18,
            'I' => 18,
            'J' => 24,
            'K' => 18,
            'L' => 28,
            'M' => 18,
            'N' => 24,
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

        $sheet->getStyle("A1:N{$lastRow}")->applyFromArray([
            'alignment' => [
                'vertical' => Alignment::VERTICAL_TOP,
                'wrapText' => true,
            ],
        ]);

        $sheet->getStyle("A1:N{$lastRow}")
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        $sheet->getStyle("B1:E{$lastRow}")
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
