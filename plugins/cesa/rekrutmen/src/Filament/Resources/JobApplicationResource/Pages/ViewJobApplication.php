<?php

namespace Cesa\Rekrutmen\Filament\Resources\JobApplicationResource\Pages;

use Cesa\Rekrutmen\Enums\JobApplicationStatus;
use Cesa\Rekrutmen\Filament\Resources\JobApplicationResource;
use Cesa\Rekrutmen\Models\JobApplication;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use InvalidArgumentException;

class ViewJobApplication extends ViewRecord
{
    protected static string $resource = JobApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('pass_current_stage')
                ->label(fn (JobApplication $record): string => __('rekrutmen::filament/resources/job-application.table.actions.pass_current_stage', [
                    'stage' => $record->currentStage?->name ?? __('rekrutmen::filament/resources/job-application.board.card.current_stage_fallback'),
                ]))
                ->icon('heroicon-o-arrow-right-circle')
                ->color('success')
                ->visible(fn (JobApplication $record): bool => $record->canPassCurrentStage())
                ->modalHeading(fn (JobApplication $record): string => __('rekrutmen::filament/resources/job-application.table.actions.pass_current_stage_heading', [
                    'stage' => $record->currentStage?->name ?? __('rekrutmen::filament/resources/job-application.board.card.current_stage_fallback'),
                ]))
                ->form([
                    Placeholder::make('current_stage')
                        ->label(__('rekrutmen::filament/resources/job-application.form.fields.current_stage_id'))
                        ->content(fn (JobApplication $record): string => $record->currentStage?->name ?? '-'),
                    Placeholder::make('next_stage')
                        ->label(__('rekrutmen::filament/resources/job-application.table.actions.next_stage'))
                        ->content(fn (JobApplication $record): string => $record->nextStageAfterCurrentStage()?->name ?? '-'),
                    DatePicker::make('activity_date')
                        ->label(__('rekrutmen::filament/resources/activity-log.form.fields.activity_date'))
                        ->required()
                        ->default(now()->toDateString())
                        ->maxDate(today()),
                    Textarea::make('notes')
                        ->label(__('rekrutmen::filament/resources/activity-log.form.fields.notes'))
                        ->maxLength(65535),
                ])
                ->action(function (JobApplication $record, array $data): void {
                    try {
                        $nextStage = $record->passCurrentStage(
                            (string) $data['activity_date'],
                            $data['notes'] ?? null,
                            auth()->id(),
                        );
                    } catch (InvalidArgumentException $exception) {
                        Notification::make()
                            ->title($exception->getMessage())
                            ->danger()
                            ->send();

                        return;
                    }

                    $notification = Notification::make()
                        ->success();

                    if ($nextStage->isLockedFinalStage()) {
                        $notification
                            ->title(__('rekrutmen::filament/resources/job-application.notifications.hired_stage_reached'))
                            ->body(__('rekrutmen::filament/resources/job-application.notifications.hired_stage_reached_help'));
                    } else {
                        $notification
                            ->title(__('rekrutmen::filament/resources/job-application.notifications.stage_passed', [
                                'stage' => $nextStage->name,
                            ]));
                    }

                    $notification->send();

                    $this->record->refresh();
                }),
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
