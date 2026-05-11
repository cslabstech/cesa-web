<?php

namespace Cesa\Rekrutmen\Livewire;

use Cesa\Rekrutmen\Enums\RequestManPowerStatus;
use Cesa\Rekrutmen\Models\JobPosting;
use Cesa\Rekrutmen\Models\RequestManPower;
use Cesa\Rekrutmen\Services\RecruitmentProgressReportExport;
use Cesa\Rekrutmen\Services\RecruitmentProgressReportService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RecruitmentProgressReport extends Component implements HasForms
{
    use InteractsWithForms;

    private const array FOCUS_KEYS = [
        'needs-action',
        'data-risk',
        'updated',
        'hold',
        'fulfilled',
        'all',
    ];

    #[Url(as: 'from')]
    public ?string $dateFrom = null;

    #[Url(as: 'to')]
    public ?string $dateTo = null;

    #[Url(as: 'position')]
    public ?int $jobPostingId = null;

    #[Url(as: 'company')]
    public ?int $companyId = null;

    #[Url(as: 'tab')]
    public string $activeTab = 'overview';

    #[Url(as: 'focus')]
    public string $focus = 'needs-action';

    public function mount(): void
    {
        $this->normalizeWorkflowState();

        if (! $this->dateFrom) {
            $this->dateFrom = now()->startOfMonth()->toDateString();
        }

        if (! $this->dateTo) {
            $this->dateTo = now()->toDateString();
        }
    }

    public function updating(string $property, mixed $value): void
    {
        if (in_array($property, ['dateFrom', 'dateTo', 'jobPostingId', 'companyId'])) {
            $this->activeTab = 'overview';
            $this->focus = 'needs-action';
        }
    }

    public function setFocus(string $focus): void
    {
        $this->focus = in_array($focus, self::FOCUS_KEYS, true) ? $focus : 'needs-action';
        $this->activeTab = 'overview';
    }

    public function getJobPostingsProperty(): Collection
    {
        return $this->reportData['postings'];
    }

    public function getCompaniesProperty(): array
    {
        $snapshotDate = $this->snapshotDate();

        return RequestManPower::query()
            ->whereNotNull('company_id')
            ->whereNotNull('tanggal_pengajuan')
            ->whereDate('tanggal_pengajuan', '<=', $snapshotDate->toDateString())
            ->whereIn('status', [
                RequestManPowerStatus::PENDING->value,
                RequestManPowerStatus::APPROVED->value,
                RequestManPowerStatus::HOLD->value,
            ])
            ->with('company')
            ->get()
            ->pluck('company.name', 'company_id')
            ->filter()
            ->sort()
            ->toArray();
    }

    public function getJobPostingOptionsProperty(): array
    {
        return JobPosting::query()
            ->where(function (Builder $query): void {
                $query->whereHas(
                    'requestManPowers',
                    fn (Builder $requestManPowerQuery) => $this->applyReportRequestScope($requestManPowerQuery)
                )->orWhereHas(
                    'requestManPower',
                    fn (Builder $requestManPowerQuery) => $this->applyReportRequestScope($requestManPowerQuery)
                );
            })
            ->when(
                $this->companyId,
                fn (Builder $query, int $companyId) => $query->where(function (Builder $query) use ($companyId): void {
                    $query->whereHas(
                        'requestManPowers',
                        fn (Builder $requestManPowerQuery) => $this->applyReportRequestScope($requestManPowerQuery)
                            ->where('company_id', $companyId)
                    )->orWhereHas(
                        'requestManPower',
                        fn (Builder $requestManPowerQuery) => $this->applyReportRequestScope($requestManPowerQuery)
                            ->where('company_id', $companyId)
                    );
                })
            )
            ->orderBy('title')
            ->pluck('title', 'id')
            ->all();
    }

    public function updatedCompanyId(): void
    {
        $this->jobPostingId = null;
    }

    public function getSummaryProperty(): array
    {
        return $this->reportData['summary'];
    }

    public function getTimelineDataProperty(): Collection
    {
        return $this->reportData['timeline'];
    }

    public function getPerPositionDataProperty(): Collection
    {
        return $this->reportData['positions'];
    }

    public function getOverviewDataProperty(): Collection
    {
        return $this->reportData['overview'];
    }

    public function getHrKpisProperty(): Collection
    {
        return $this->reportData['hr_kpis'];
    }

    public function getWorkflowPositionsProperty(): Collection
    {
        return $this->allWorkflowPositions()
            ->filter(fn (array $position): bool => match ($this->focus) {
                'needs-action' => $position['workflow']['remaining'] > 0 && ! $position['workflow']['is_on_hold'],
                'data-risk'    => $position['workflow']['has_cycle_risk'],
                'updated'      => $position['workflow']['has_updates'],
                'hold'         => $position['workflow']['is_on_hold'],
                'fulfilled'    => $position['workflow']['is_fulfilled'],
                default        => true,
            })
            ->sortByDesc(fn (array $position): int => $position['workflow']['priority_score'])
            ->values();
    }

    public function getFocusCountsProperty(): array
    {
        $positions = $this->allWorkflowPositions();

        return [
            'needs-action' => $positions
                ->filter(fn (array $position): bool => $position['workflow']['remaining'] > 0 && ! $position['workflow']['is_on_hold'])
                ->count(),
            'data-risk' => $positions
                ->filter(fn (array $position): bool => $position['workflow']['has_cycle_risk'])
                ->count(),
            'updated' => $positions
                ->filter(fn (array $position): bool => $position['workflow']['has_updates'])
                ->count(),
            'hold' => $positions
                ->filter(fn (array $position): bool => $position['workflow']['is_on_hold'])
                ->count(),
            'fulfilled' => $positions
                ->filter(fn (array $position): bool => $position['workflow']['is_fulfilled'])
                ->count(),
            'all' => $positions->count(),
        ];
    }

    public function getWorkflowSummaryProperty(): array
    {
        $positions = $this->allWorkflowPositions();

        return [
            'total_needed'      => $positions->sum(fn (array $position): int => $position['workflow']['needed']),
            'total_hired'       => $positions->sum(fn (array $position): int => $position['workflow']['hired']),
            'total_remaining'   => $positions->sum(fn (array $position): int => $position['workflow']['remaining']),
            'total_updates'     => $positions->sum(fn (array $position): int => $position['workflow']['activity_count']),
            'candidate_updates' => $this->timelineData->sum(fn (array $day): int => (int) ($day['candidate_count'] ?? 0)),
            'kpi_people'        => $this->summary['total_hr_kpi_people'] ?? 0,
            'kpi_hired'         => $this->summary['total_hr_kpi_hired_headcount'] ?? 0,
            'kpi_fulfilled_mpp' => $this->summary['total_hr_kpi_fulfilled_mpp'] ?? 0,
            'cycle_risks'       => $positions
                ->filter(fn (array $position): bool => $position['workflow']['has_cycle_risk'])
                ->count(),
        ];
    }

    public function getReportDataProperty(): array
    {
        return app(RecruitmentProgressReportService::class)->build([
            'date_from'      => $this->dateFrom,
            'date_to'        => $this->dateTo,
            'job_posting_id' => $this->jobPostingId,
            'company_id'     => $this->companyId,
        ]);
    }

    public function formatDateLabel(string $date): string
    {
        $carbon = Carbon::parse($date);

        return $carbon->translatedFormat('l, d F Y');
    }

    public static function activitySummaryText(int $total, int $passed, int $failed, int $pending): string
    {
        return RecruitmentProgressReportService::activitySummaryText($total, $passed, $failed, $pending);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Grid::make([
                    'default' => 1,
                    'md'      => 2,
                    'xl'      => 4,
                ])
                    ->schema([
                        DatePicker::make('dateFrom')
                            ->label(__('rekrutmen::livewire/recruitment-progress-report.filters.period_from'))
                            ->placeholder(__('rekrutmen::livewire/recruitment-progress-report.filters.placeholders.period_from'))
                            ->live(),
                        DatePicker::make('dateTo')
                            ->label(__('rekrutmen::livewire/recruitment-progress-report.filters.period_to'))
                            ->placeholder(__('rekrutmen::livewire/recruitment-progress-report.filters.placeholders.period_to'))
                            ->live(),
                        Select::make('companyId')
                            ->label(__('rekrutmen::livewire/recruitment-progress-report.filters.company'))
                            ->options($this->companies)
                            ->placeholder(__('rekrutmen::livewire/recruitment-progress-report.filters.placeholders.company'))
                            ->searchable()
                            ->preload()
                            ->live(),
                        Select::make('jobPostingId')
                            ->label(__('rekrutmen::livewire/recruitment-progress-report.filters.position'))
                            ->options($this->jobPostingOptions)
                            ->placeholder(__('rekrutmen::livewire/recruitment-progress-report.filters.placeholders.position'))
                            ->searchable()
                            ->preload()
                            ->live(),
                    ]),
            ])
            ->statePath('');
    }

    public function render(): View
    {
        return view('rekrutmen::livewire.recruitment-progress-report', [
            'jobPostings'       => $this->jobPostings,
            'companies'         => $this->companies,
            'summary'           => $this->summary,
            'timelineData'      => $this->timelineData,
            'perPositionData'   => $this->perPositionData,
            'overviewData'      => $this->overviewData,
            'hrKpis'            => $this->hrKpis,
            'workflowPositions' => $this->workflowPositions,
            'focusCounts'       => $this->focusCounts,
            'workflowSummary'   => $this->workflowSummary,
        ]);
    }

    #[On('export-report-excel')]
    public function exportExcel(): BinaryFileResponse
    {
        return Excel::download(
            new RecruitmentProgressReportExport(
                $this->reportData,
                [
                    'date_from'      => $this->dateFrom,
                    'date_to'        => $this->dateTo,
                    'period_label'   => $this->periodLabel(),
                    'position_label' => $this->jobPostingLabel(),
                    'company_label'  => $this->companyLabel(),
                ],
            ),
            $this->exportFilename(),
        );
    }

    protected function exportFilename(): string
    {
        $from = filled($this->dateFrom)
            ? Carbon::parse($this->dateFrom)->format('Ymd')
            : 'all';
        $to = filled($this->dateTo)
            ? Carbon::parse($this->dateTo)->format('Ymd')
            : 'all';

        return "recruitment-progress-mpp-{$from}-to-{$to}.xlsx";
    }

    protected function periodLabel(): string
    {
        if (filled($this->dateFrom) && filled($this->dateTo)) {
            return sprintf(
                'Periode %s s/d %s',
                Carbon::parse($this->dateFrom)->format('d M Y'),
                Carbon::parse($this->dateTo)->format('d M Y'),
            );
        }

        if (filled($this->dateFrom)) {
            return 'Mulai '.$this->formatDateLabel($this->dateFrom);
        }

        if (filled($this->dateTo)) {
            return 'Sampai '.$this->formatDateLabel($this->dateTo);
        }

        return 'Semua Periode';
    }

    protected function jobPostingLabel(): string
    {
        if (! $this->jobPostingId) {
            return __('rekrutmen::livewire/recruitment-progress-report.filters.all_positions');
        }

        return $this->jobPostings->firstWhere('id', $this->jobPostingId)?->title ?? '-';
    }

    protected function companyLabel(): string
    {
        if (! $this->companyId) {
            return __('rekrutmen::livewire/recruitment-progress-report.filters.all_companies');
        }

        return $this->companies[$this->companyId] ?? '-';
    }

    protected function normalizeWorkflowState(): void
    {
        if ($this->activeTab === 'activity' && $this->focus === 'needs-action') {
            $this->focus = 'updated';
        }

        $this->activeTab = 'overview';

        if (! in_array($this->focus, self::FOCUS_KEYS, true)) {
            $this->focus = 'needs-action';
        }
    }

    protected function allWorkflowPositions(): Collection
    {
        return $this->perPositionData
            ->map(fn (array $position): array => $this->enrichWorkflowPosition($position))
            ->values();
    }

    protected function applyReportRequestScope(Builder $query): Builder
    {
        $snapshotDate = $this->snapshotDate();

        return $query
            ->whereNull((new RequestManPower)->qualifyColumn('deleted_at'))
            ->whereIn('status', [
                RequestManPowerStatus::PENDING->value,
                RequestManPowerStatus::APPROVED->value,
                RequestManPowerStatus::HOLD->value,
            ])
            ->whereNotNull((new RequestManPower)->qualifyColumn('tanggal_pengajuan'))
            ->whereDate((new RequestManPower)->qualifyColumn('tanggal_pengajuan'), '<=', $snapshotDate->toDateString());
    }

    protected function snapshotDate(): Carbon
    {
        if (filled($this->dateTo)) {
            return Carbon::parse($this->dateTo)->endOfDay();
        }

        if (filled($this->dateFrom)) {
            return Carbon::parse($this->dateFrom)->endOfDay();
        }

        return now()->endOfDay();
    }

    /**
     * @param  array<string, mixed>  $position
     * @return array<string, mixed>
     */
    protected function enrichWorkflowPosition(array $position): array
    {
        $statistics = $position['statistics'];
        $needed = (int) $position['needed'];
        $hired = (int) ($statistics['hired'] ?? 0);
        $remaining = max($needed - $hired, 0);
        $inProgress = (int) ($statistics['in_progress'] ?? 0);
        $totalApplicants = (int) ($statistics['total_applicants'] ?? 0);
        $activityCount = $position['activities']->count();
        $isOnHold = (bool) $position['is_on_hold'];
        $isFulfilled = $needed > 0 && $remaining === 0;
        $cycleHealth = $position['cycle_health'] ?? [
            'status'       => 'healthy',
            'status_label' => __('rekrutmen::livewire/recruitment-progress-report.workflow.cycle_health.status.healthy'),
            'summary'      => __('rekrutmen::livewire/recruitment-progress-report.workflow.cycle_health.healthy.summary'),
            'description'  => __('rekrutmen::livewire/recruitment-progress-report.workflow.cycle_health.healthy.description'),
            'issues'       => [],
        ];
        $hasCycleRisk = ($cycleHealth['status'] ?? 'healthy') !== 'healthy';
        $actionKey = $this->resolveActionKey($remaining, $inProgress, $totalApplicants, $isOnHold, $isFulfilled);

        $position['workflow'] = [
            'needed'             => $needed,
            'hired'              => $hired,
            'remaining'          => $remaining,
            'in_progress'        => $inProgress,
            'total_applicants'   => $totalApplicants,
            'activity_count'     => $activityCount,
            'has_updates'        => $activityCount > 0,
            'cycle_health'       => $cycleHealth,
            'has_cycle_risk'     => $hasCycleRisk,
            'is_on_hold'         => $isOnHold,
            'is_fulfilled'       => $isFulfilled,
            'action_key'         => $actionKey,
            'action_label'       => __("rekrutmen::livewire/recruitment-progress-report.workflow.actions.{$actionKey}.label"),
            'action_description' => __("rekrutmen::livewire/recruitment-progress-report.workflow.actions.{$actionKey}.description"),
            'priority_score'     => $this->priorityScore($remaining, $inProgress, $activityCount, $isOnHold, $isFulfilled, $hasCycleRisk),
        ];

        return $position;
    }

    protected function resolveActionKey(
        int $remaining,
        int $inProgress,
        int $totalApplicants,
        bool $isOnHold,
        bool $isFulfilled,
    ): string {
        return match (true) {
            $isOnHold                              => 'hold',
            $isFulfilled                           => 'fulfilled',
            $remaining > 0 && $inProgress > 0      => 'monitor',
            $remaining > 0 && $totalApplicants > 0 => 'recover',
            $remaining > 0                         => 'source',
            default                                => 'review',
        };
    }

    protected function priorityScore(
        int $remaining,
        int $inProgress,
        int $activityCount,
        bool $isOnHold,
        bool $isFulfilled,
        bool $hasCycleRisk,
    ): int {
        $riskScore = $hasCycleRisk ? 100_000 : 0;

        if ($isFulfilled) {
            return $riskScore + $activityCount;
        }

        if ($isOnHold) {
            return $riskScore + (1000 + $remaining * 100) + $activityCount;
        }

        return $riskScore + (10_000 + $remaining * 1000) + ($inProgress === 0 ? 500 : 0) + ($activityCount * 10);
    }
}
