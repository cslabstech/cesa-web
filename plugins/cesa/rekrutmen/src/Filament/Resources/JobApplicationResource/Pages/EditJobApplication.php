<?php

namespace Cesa\Rekrutmen\Filament\Resources\JobApplicationResource\Pages;

use Cesa\Rekrutmen\Enums\JobApplicationStatus;
use Cesa\Rekrutmen\Filament\Resources\JobApplicationResource;
use Cesa\Rekrutmen\Models\JobApplication;
use Filament\Actions;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditJobApplication extends EditRecord
{
    protected static string $resource = JobApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('mark_hired')
                ->label(__('rekrutmen::filament/resources/job-application.table.actions.mark_hired'))
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (): bool => $this->record instanceof JobApplication && $this->record->status === JobApplicationStatus::IN_PROGRESS)
                ->form([
                    Textarea::make('notes')
                        ->label(__('rekrutmen::filament/resources/job-application.table.actions.notes'))
                        ->required()
                        ->maxLength(65535),
                ])
                ->action(function (array $data): void {
                    $this->record->markAsHired($data['notes'] ?? null, auth()->id());

                    Notification::make()
                        ->title(__('rekrutmen::filament/resources/job-application.notifications.marked_hired'))
                        ->success()
                        ->send();

                    $this->redirect(static::getResource()::getUrl('edit', ['record' => $this->record]));
                }),
            Actions\Action::make('mark_rejected')
                ->label(__('rekrutmen::filament/resources/job-application.table.actions.mark_rejected'))
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (): bool => $this->record instanceof JobApplication && $this->record->status === JobApplicationStatus::IN_PROGRESS)
                ->form([
                    Textarea::make('notes')
                        ->label(__('rekrutmen::filament/resources/job-application.table.actions.notes'))
                        ->required()
                        ->maxLength(65535),
                ])
                ->action(function (array $data): void {
                    $this->record->markAsRejected($data['notes'] ?? null, auth()->id());

                    Notification::make()
                        ->title(__('rekrutmen::filament/resources/job-application.notifications.marked_rejected'))
                        ->success()
                        ->send();

                    $this->redirect(static::getResource()::getUrl('edit', ['record' => $this->record]));
                }),
            Actions\DeleteAction::make(),
        ];
    }
}
