<?php

namespace Cesa\Rekrutmen\Filament\Pages;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Pages\Page;

class RecruitmentProgressReportPage extends Page
{
    use HasPageShield;

    protected static string|\BackedEnum|null $navigationIcon = null;

    protected string $view = 'rekrutmen::filament.pages.recruitment-progress-report';

    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): string
    {
        return __('admin.navigation.rekrutmen');
    }

    public static function getNavigationLabel(): string
    {
        return __('rekrutmen::filament/pages/recruitment-progress-report.navigation.label');
    }

    public function getHeading(): string
    {
        return __('rekrutmen::livewire/recruitment-progress-report.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Export Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(fn () => $this->dispatch('export-report-excel')),
        ];
    }
}
