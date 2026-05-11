<?php

namespace Cesa\Rekrutmen\Filament\Pages;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Cesa\Rekrutmen\Models\JobApplicationHistory;
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

    public static function canAccess(): bool
    {
        return auth()->user()?->can('viewAny', JobApplicationHistory::class) ?? false;
    }

    public function getHeading(): string
    {
        return __('rekrutmen::livewire/recruitment-progress-report.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('guide')
                ->label(__('rekrutmen::filament/pages/recruitment-progress-report.guide.label'))
                ->icon('heroicon-o-question-mark-circle')
                ->color('gray')
                ->modalHeading(__('rekrutmen::filament/pages/recruitment-progress-report.guide.modal_heading'))
                ->modalDescription(str(__('rekrutmen::filament/pages/recruitment-progress-report.guide.modal_content'))->toHtmlString())
                ->modalSubmitAction(false)
                ->modalCancelActionLabel(__('rekrutmen::filament/pages/recruitment-progress-report.guide.close')),
            Action::make('export')
                ->label('Export Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(fn () => $this->dispatch('export-report-excel')),
        ];
    }
}
