<?php

namespace Cesa\Rekrutmen\Livewire;

use Cesa\Rekrutmen\Models\RekrutmenStage;
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

    #[Url(as: 'from')]
    public ?string $dateFrom = null;

    #[Url(as: 'to')]
    public ?string $dateTo = null;

    #[Url(as: 'position')]
    public ?int $jobPostingId = null;

    #[Url(as: 'stage')]
    public ?int $stageId = null;

    #[Url(as: 'company')]
    public ?int $companyId = null;

    #[Url(as: 'tab')]
    public string $activeTab = 'timeline';

    public function mount(): void
    {
        if (! $this->dateFrom) {
            $this->dateFrom = now()->startOfMonth()->toDateString();
        }

        if (! $this->dateTo) {
            $this->dateTo = now()->toDateString();
        }
    }

    public function updating(string $property, mixed $value): void
    {
        if (in_array($property, ['dateFrom', 'dateTo', 'jobPostingId', 'stageId', 'companyId'])) {
            $this->activeTab = 'timeline';
        }
    }

    public function getJobPostingsProperty(): Collection
    {
        return $this->reportData['postings'];
    }

    public function getCompaniesProperty(): array
    {
        return RequestManPower::query()
            ->whereNotNull('company_id')
            ->with('company')
            ->get()
            ->pluck('company.name', 'company_id')
            ->filter()
            ->sort()
            ->toArray();
    }

    public function getStagesProperty(): array
    {
        return RekrutmenStage::query()
            ->when(
                $this->jobPostingId,
                fn ($query, int $jobPostingId) => $query->whereHas(
                    'pipeline.jobPostings',
                    fn ($jobPostingQuery) => $jobPostingQuery->whereKey($jobPostingId)
                )
            )
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
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

    public function getReportDataProperty(): array
    {
        return app(RecruitmentProgressReportService::class)->build([
            'date_from'      => $this->dateFrom,
            'date_to'        => $this->dateTo,
            'job_posting_id' => $this->jobPostingId,
            'stage_id'       => $this->stageId,
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
                Grid::make(5)
                    ->schema([
                        DatePicker::make('dateFrom')
                            ->label(__('rekrutmen::livewire/recruitment-progress-report.filters.period_from'))
                            ->live(),
                        DatePicker::make('dateTo')
                            ->label(__('rekrutmen::livewire/recruitment-progress-report.filters.period_to'))
                            ->live(),
                        Select::make('jobPostingId')
                            ->label(__('rekrutmen::livewire/recruitment-progress-report.filters.position'))
                            ->options($this->jobPostings->pluck('title', 'id'))
                            ->live(),
                        Select::make('stageId')
                            ->label(__('rekrutmen::livewire/recruitment-progress-report.filters.stage'))
                            ->options($this->stages)
                            ->live(),
                        Select::make('companyId')
                            ->label(__('rekrutmen::livewire/recruitment-progress-report.filters.company'))
                            ->options($this->companies)
                            ->live(),
                    ]),
            ])
            ->statePath('');
    }

    public function render(): View
    {
        return view('rekrutmen::livewire.recruitment-progress-report', [
            'jobPostings'     => $this->jobPostings,
            'stages'          => $this->stages,
            'companies'       => $this->companies,
            'summary'         => $this->summary,
            'timelineData'    => $this->timelineData,
            'perPositionData' => $this->perPositionData,
            'overviewData'    => $this->overviewData,
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
                    'stage_label'    => $this->stageLabel(),
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

    protected function stageLabel(): string
    {
        if (! $this->stageId) {
            return __('rekrutmen::livewire/recruitment-progress-report.filters.all_stages');
        }

        return RekrutmenStage::query()->find($this->stageId)?->name ?? '-';
    }

    protected function companyLabel(): string
    {
        if (! $this->companyId) {
            return __('rekrutmen::livewire/recruitment-progress-report.filters.all_companies');
        }

        return $this->companies[$this->companyId] ?? '-';
    }
}
