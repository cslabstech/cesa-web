<?php

namespace Cesa\ExitClearance\Filament\Resources\RequestResource\Pages;

use Cesa\ExitClearance\Filament\Resources\RequestResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditRequest extends EditRecord
{
    protected static string $resource = RequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view_progress')
                ->label(__('exit-clearance::filament/resources/request.actions.view_progress'))
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->url(fn (): string => $this->record->getPublicProgressUrl())
                ->openUrlInNewTab(),
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
