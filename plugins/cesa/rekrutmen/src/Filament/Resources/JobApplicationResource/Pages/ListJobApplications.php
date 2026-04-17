<?php

namespace Cesa\Rekrutmen\Filament\Resources\JobApplicationResource\Pages;

use Cesa\Rekrutmen\Filament\Resources\JobApplicationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListJobApplications extends ListRecords
{
    protected static string $resource = JobApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('kanban_board')
                ->label(__('rekrutmen::filament/resources/job-application.board.navigation_label'))
                ->icon('heroicon-o-view-columns')
                ->color('gray')
                ->url(JobApplicationResource::getUrl('board')),
            Actions\CreateAction::make()->icon('heroicon-o-plus-circle'),
        ];
    }
}
