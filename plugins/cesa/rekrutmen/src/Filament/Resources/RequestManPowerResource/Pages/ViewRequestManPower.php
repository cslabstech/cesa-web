<?php

namespace Cesa\Rekrutmen\Filament\Resources\RequestManPowerResource\Pages;

use Cesa\Rekrutmen\Filament\Resources\RequestManPowerResource;
use Cesa\Rekrutmen\Models\RequestManPower;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ViewRequestManPower extends ViewRecord
{
    protected static string $resource = RequestManPowerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('view_progress')
                ->label(__('rekrutmen::filament/resources/request-man-power.table.actions.view_progress'))
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->url(fn (RequestManPower $record): string => $record->getPublicProgressUrl())
                ->openUrlInNewTab(),
            Actions\Action::make('approve')
                ->label(__('rekrutmen::filament/resources/request-man-power.table.actions.approve'))
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (RequestManPower $record) => RequestManPowerResource::canManualApproveOrReject($record))
                ->action(function (RequestManPower $record): void {
                    try {
                        $record->approveBy(Auth::id());

                        Notification::make()
                            ->title(__('rekrutmen::filament/resources/request-man-power.notifications.approved'))
                            ->success()
                            ->send();
                    } catch (\Throwable $exception) {
                        Log::error('Failed to approve manpower request.', [
                            'request_man_power_id' => $record->getKey(),
                            'exception'            => $exception,
                        ]);

                        Notification::make()
                            ->title(__('rekrutmen::filament/resources/request-man-power.errors.approval_failed'))
                            ->danger()
                            ->send();
                    }
                }),
            Actions\Action::make('reject')
                ->label(__('rekrutmen::filament/resources/request-man-power.table.actions.reject'))
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (RequestManPower $record) => RequestManPowerResource::canManualApproveOrReject($record))
                ->requiresConfirmation()
                ->action(function (RequestManPower $record): void {
                    $record->rejectBy(Auth::id());

                    Notification::make()
                        ->title(__('rekrutmen::filament/resources/request-man-power.notifications.rejected'))
                        ->success()
                        ->send();
                }),
            Actions\Action::make('resend_pending_approval')
                ->label(__('rekrutmen::filament/resources/request-man-power.table.actions.resend_pending_approval'))
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->visible(fn (RequestManPower $record): bool => RequestManPowerResource::canResendPendingApproval($record))
                ->action(function (RequestManPower $record): void {
                    $record->notifyCurrentPendingApproval(true);

                    Notification::make()
                        ->title(__('rekrutmen::filament/resources/request-man-power.notifications.pending_approval_resent'))
                        ->success()
                        ->send();
                }),
            Actions\Action::make('set_pending')
                ->label(__('rekrutmen::filament/resources/request-man-power.table.actions.set_pending'))
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn (RequestManPower $record) => RequestManPowerResource::canSetPending($record))
                ->requiresConfirmation()
                ->action(function (RequestManPower $record): void {
                    $record->markPending();

                    Notification::make()
                        ->title(__('rekrutmen::filament/resources/request-man-power.notifications.set_pending'))
                        ->success()
                        ->send();
                }),
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
