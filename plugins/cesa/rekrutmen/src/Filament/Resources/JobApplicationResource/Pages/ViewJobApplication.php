<?php

namespace Cesa\Rekrutmen\Filament\Resources\JobApplicationResource\Pages;

use Cesa\Rekrutmen\Enums\JobApplicationStatus;
use Cesa\Rekrutmen\Filament\Resources\JobApplicationResource;
use Cesa\Rekrutmen\Models\JobApplication;
use Filament\Actions;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewJobApplication extends ViewRecord
{
    protected static string $resource = JobApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('mark_hired')
                ->label(__('rekrutmen::filament/resources/job-application.table.actions.mark_hired'))
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (JobApplication $record): bool => $record->status === JobApplicationStatus::IN_PROGRESS)
                ->form([
                    Textarea::make('notes')
                        ->label(__('rekrutmen::filament/resources/job-application.table.actions.notes'))
                        ->required()
                        ->maxLength(65535),
                ])
                ->action(function (JobApplication $record, array $data): void {
                    $record->markAsHired(
                        $data['notes'] ?? null,
                        auth()->id(),
                    );

                    Notification::make()
                        ->title(__('rekrutmen::filament/resources/job-application.notifications.marked_hired'))
                        ->success()
                        ->send();
                }),
            Actions\Action::make('mark_rejected')
                ->label(__('rekrutmen::filament/resources/job-application.table.actions.mark_rejected'))
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (JobApplication $record): bool => $record->status === JobApplicationStatus::IN_PROGRESS)
                ->form([
                    Textarea::make('notes')
                        ->label(__('rekrutmen::filament/resources/job-application.table.actions.notes'))
                        ->required()
                        ->maxLength(65535),
                ])
                ->action(function (JobApplication $record, array $data): void {
                    $record->markAsRejected(
                        $data['notes'] ?? null,
                        auth()->id(),
                    );

                    Notification::make()
                        ->title(__('rekrutmen::filament/resources/job-application.notifications.marked_rejected'))
                        ->success()
                        ->send();
                }),
            Actions\Action::make('download_resume')
                ->label(__('rekrutmen::filament/resources/job-application.table.actions.download_resume'))
                ->icon('heroicon-o-document-arrow-down')
                ->url(fn (JobApplication $record) => JobApplicationResource::resolveAttachmentDownloadUrl($record, 'resume'))
                ->openUrlInNewTab()
                ->visible(fn (JobApplication $record) => filled($record->resume_path)),
            Actions\Action::make('download_photo')
                ->label(__('rekrutmen::filament/resources/job-application.table.actions.download_photo'))
                ->icon('heroicon-o-photo')
                ->url(fn (JobApplication $record) => JobApplicationResource::resolveAttachmentDownloadUrl($record, 'photo'))
                ->openUrlInNewTab()
                ->visible(fn (JobApplication $record) => filled($record->photo_path)),
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
