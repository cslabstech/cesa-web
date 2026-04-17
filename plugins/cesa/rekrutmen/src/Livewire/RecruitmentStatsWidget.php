<?php

namespace Cesa\Rekrutmen\Livewire;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\Reactive;

class RecruitmentStatsWidget extends BaseWidget
{
    #[Reactive]
    public array $summary;

    protected function getColumns(): int
    {
        return 5;
    }

    protected function getStats(): array
    {
        return [
            Stat::make(__('rekrutmen::livewire/recruitment-progress-report.summary.active_positions'), $this->summary['total_positions_active'] ?? 0)
                ->description(__('rekrutmen::livewire/recruitment-progress-report.summary.openings_label'))
                ->color('primary'),

            Stat::make(__('rekrutmen::livewire/recruitment-progress-report.summary.total_applicants'), $this->summary['total_candidates_in_process'] ?? 0)
                ->description(__('rekrutmen::livewire/recruitment-progress-report.summary.applicants_label'))
                ->color('info'),

            Stat::make(__('rekrutmen::livewire/recruitment-progress-report.summary.activities'), $this->summary['total_activities_this_period'] ?? 0)
                ->description(__('rekrutmen::livewire/recruitment-progress-report.summary.activities_label'))
                ->color('warning'),

            Stat::make(__('rekrutmen::livewire/recruitment-progress-report.summary.hired'), $this->summary['total_hired_this_period'] ?? 0)
                ->description(__('rekrutmen::livewire/recruitment-progress-report.summary.hired_label'))
                ->color('success'),

            Stat::make(__('rekrutmen::livewire/recruitment-progress-report.summary.rejected'), $this->summary['total_rejected_this_period'] ?? 0)
                ->description(__('rekrutmen::livewire/recruitment-progress-report.summary.rejected_label'))
                ->color('danger'),
        ];
    }
}
