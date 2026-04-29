<?php

namespace Cesa\ExitClearance\Filament\Resources\RequestResource\Pages;

use Cesa\ExitClearance\Filament\Resources\RequestResource;
use Cesa\ExitClearance\Models\Request;
use Cesa\ExitClearance\Services\ExitClearanceNotificationService;
use Cesa\ExitClearance\Services\ExitClearanceRequestPdfService;
use Cesa\ExitClearance\Services\ExitClearanceRequestService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewRequest extends ViewRecord
{
    protected static string $resource = RequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view_progress')
                ->label(__('exit-clearance::filament/resources/request.actions.view_progress'))
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->url(fn (Request $record): string => $record->getPublicProgressUrl())
                ->openUrlInNewTab(),
            EditAction::make()
                ->visible(fn (Request $record): bool => $this->canModify($record)),
            Action::make('download-pdf')
                ->label(__('exit-clearance::filament/resources/request.actions.download_pdf'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn (Request $record) => app(ExitClearanceRequestPdfService::class)->download($record)),
            Action::make('resend-pending-approvers')
                ->label(__('exit-clearance::filament/resources/request/pages/view-request.actions.resend_pending_approvers.label'))
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading(__('exit-clearance::filament/resources/request/pages/view-request.actions.resend_pending_approvers.modal_heading'))
                ->modalDescription(__('exit-clearance::filament/resources/request/pages/view-request.actions.resend_pending_approvers.modal_description'))
                ->visible(fn (Request $record): bool => $this->hasPendingApprovers($record))
                ->action(function (Request $record): void {
                    $requestService = app(ExitClearanceRequestService::class);

                    if ($requestService->normalizeFormStatus($record->form_status) !== 'pending') {
                        Notification::make()
                            ->title(__('exit-clearance::filament/resources/request/pages/view-request.notifications.form_completed.title'))
                            ->body(__('exit-clearance::filament/resources/request/pages/view-request.notifications.form_completed.body'))
                            ->warning()
                            ->send();

                        return;
                    }

                    $sentCount = app(ExitClearanceNotificationService::class)->notifyPendingApprovers($record);

                    if ($sentCount < 1) {
                        Notification::make()
                            ->title(__('exit-clearance::filament/resources/request/pages/view-request.notifications.no_pending_approvers.title'))
                            ->body(__('exit-clearance::filament/resources/request/pages/view-request.notifications.no_pending_approvers.body'))
                            ->warning()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title(__('exit-clearance::filament/resources/request/pages/view-request.notifications.notifications_resent.title'))
                        ->body(__('exit-clearance::filament/resources/request/pages/view-request.notifications.notifications_resent.body', ['count' => $sentCount]))
                        ->success()
                        ->send();
                }),
            DeleteAction::make()
                ->visible(fn (Request $record): bool => $this->canModify($record)),
        ];
    }

    protected function canModify(Request $record): bool
    {
        $requestService = app(ExitClearanceRequestService::class);

        return $requestService->normalizeFormStatus($record->form_status) !== 'approved';
    }

    protected function hasPendingApprovers(Request $record): bool
    {
        $record->loadMissing('approvers');

        $requestService = app(ExitClearanceRequestService::class);

        if ($requestService->normalizeFormStatus($record->form_status) !== 'pending') {
            return false;
        }

        foreach ($record->approvers as $approver) {
            $status = $requestService->normalizeApprovalStatus($approver->pivot?->status);

            if ($status === ExitClearanceRequestService::APPROVAL_PENDING) {
                return true;
            }
        }

        return false;
    }
}
