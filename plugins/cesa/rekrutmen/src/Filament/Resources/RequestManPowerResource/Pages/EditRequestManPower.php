<?php

namespace Cesa\Rekrutmen\Filament\Resources\RequestManPowerResource\Pages;

use Cesa\Rekrutmen\Filament\Resources\RequestManPowerResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditRequestManPower extends EditRecord
{
    protected static string $resource = RequestManPowerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view_progress')
                ->label(__('rekrutmen::filament/resources/request-man-power.table.actions.view_progress'))
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->url(fn (): string => $this->record->getPublicProgressUrl())
                ->openUrlInNewTab(),
            Action::make('approve')
                ->label(__('rekrutmen::filament/resources/request-man-power.table.actions.approve'))
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (): bool => RequestManPowerResource::canManualApproveOrReject($this->record))
                ->action(function (): void {
                    $this->record->approveBy(Auth::id());

                    Notification::make()
                        ->title(__('rekrutmen::filament/resources/request-man-power.notifications.approved'))
                        ->success()
                        ->send();

                    $this->redirect(static::getResource()::getUrl('edit', ['record' => $this->record]));
                }),
            Action::make('reject')
                ->label(__('rekrutmen::filament/resources/request-man-power.table.actions.reject'))
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (): bool => RequestManPowerResource::canManualApproveOrReject($this->record))
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->record->rejectBy(Auth::id());

                    Notification::make()
                        ->title(__('rekrutmen::filament/resources/request-man-power.notifications.rejected'))
                        ->success()
                        ->send();

                    $this->redirect(static::getResource()::getUrl('edit', ['record' => $this->record]));
                }),
            Action::make('hold')
                ->label(__('rekrutmen::filament/resources/request-man-power.table.actions.hold'))
                ->icon('heroicon-o-pause-circle')
                ->color('gray')
                ->visible(fn (): bool => RequestManPowerResource::canHold($this->record))
                ->requiresConfirmation()
                ->modalHeading(__('rekrutmen::filament/resources/request-man-power.table.actions.hold_modal_heading'))
                ->modalDescription(__('rekrutmen::filament/resources/request-man-power.table.actions.hold_modal_description'))
                ->modalSubmitActionLabel(__('rekrutmen::filament/resources/request-man-power.table.actions.hold_modal_submit'))
                ->schema([
                    Textarea::make('hold_reason')
                        ->label(__('rekrutmen::filament/resources/request-man-power.form.fields.hold_reason'))
                        ->required()
                        ->minLength(5)
                        ->maxLength(2000)
                        ->rows(4),
                ])
                ->action(function (array $data): void {
                    $this->record->markOnHold(Auth::id(), (string) ($data['hold_reason'] ?? ''));

                    Notification::make()
                        ->title(__('rekrutmen::filament/resources/request-man-power.notifications.hold'))
                        ->success()
                        ->send();

                    $this->redirect(static::getResource()::getUrl('edit', ['record' => $this->record]));
                }),
            Action::make('resume_hold')
                ->label(__('rekrutmen::filament/resources/request-man-power.table.actions.resume_hold'))
                ->icon('heroicon-o-play-circle')
                ->color('success')
                ->visible(fn (): bool => RequestManPowerResource::canResumeHold($this->record))
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->record->resumeFromHold(Auth::id());

                    Notification::make()
                        ->title(__('rekrutmen::filament/resources/request-man-power.notifications.resume_hold'))
                        ->success()
                        ->send();

                    $this->redirect(static::getResource()::getUrl('edit', ['record' => $this->record]));
                }),
            Action::make('resend_pending_approval')
                ->label(__('rekrutmen::filament/resources/request-man-power.table.actions.resend_pending_approval'))
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->visible(fn (): bool => RequestManPowerResource::canResendPendingApproval($this->record))
                ->action(function (): void {
                    $this->record->notifyCurrentPendingApproval(true);

                    Notification::make()
                        ->title(__('rekrutmen::filament/resources/request-man-power.notifications.pending_approval_resent'))
                        ->success()
                        ->send();
                }),
            Action::make('set_pending')
                ->label(__('rekrutmen::filament/resources/request-man-power.table.actions.set_pending'))
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn (): bool => RequestManPowerResource::canSetPending($this->record))
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->record->markPending(Auth::id());

                    Notification::make()
                        ->title(__('rekrutmen::filament/resources/request-man-power.notifications.set_pending'))
                        ->success()
                        ->send();

                    $this->redirect(static::getResource()::getUrl('edit', ['record' => $this->record]));
                }),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['status'] = $this->record->status;
        $data['approved_by'] = $this->record->approved_by;

        return $data;
    }
}
