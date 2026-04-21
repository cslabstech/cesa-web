<?php

namespace Cesa\FormTransfer\Filament\Resources\TransferRequestResource\Pages;

use Cesa\FormTransfer\Enums\ApprovalStatus;
use Cesa\FormTransfer\Enums\TransferRequestApprovalStatus;
use Cesa\FormTransfer\Enums\TransferRequestRealizationStatus;
use Cesa\FormTransfer\Filament\Resources\TransferRequestResource;
use Cesa\FormTransfer\Models\TransferRequest;
use Cesa\FormTransfer\Services\ApprovalWorkflowService;
use Cesa\FormTransfer\Services\TransferApprovalNotificationService;
use Cesa\FormTransfer\Services\TransferRequestPdfService;
use Cesa\FormTransfer\Support\TransferRequestAttachmentField;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ViewTransferRequest extends ViewRecord
{
    protected static string $resource = TransferRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->slideOver(),
            Actions\RestoreAction::make(),
            Actions\ForceDeleteAction::make(),
            Action::make('download-pdf')
                ->label(__('form-transfer::filament/resources/transfer-request/view.transfer_request.actions.download_pdf'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn (TransferRequest $record) => app(TransferRequestPdfService::class)->download($record)),
            Action::make('resend-pending-approver')
                ->label(__('form-transfer::filament/resources/transfer-request/view.transfer_request.actions.resend_pending_approver'))
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading(__('form-transfer::filament/resources/transfer-request/view.transfer_request.actions.resend_notification_heading'))
                ->modalDescription(__('form-transfer::filament/resources/transfer-request/view.transfer_request.actions.resend_notification_description'))
                ->visible(fn (TransferRequest $record): bool => $this->hasPendingApprover($record) && Gate::allows('update', $record))
                ->action(function (TransferRequest $record): void {
                    Gate::authorize('update', $record);

                    if ($record->approval_status !== TransferRequestApprovalStatus::PENDING) {
                        Notification::make()
                            ->title(__('form-transfer::filament/resources/transfer-request/view.transfer_request.notifications.approval_completed_title'))
                            ->body(__('form-transfer::filament/resources/transfer-request/view.transfer_request.notifications.approval_completed_body'))
                            ->warning()
                            ->send();

                        return;
                    }

                    $pending = $this->getCurrentPendingApproval($record);

                    if (! $pending) {
                        Notification::make()
                            ->title(__('form-transfer::filament/resources/transfer-request/view.transfer_request.notifications.no_pending_approver_title'))
                            ->body(__('form-transfer::filament/resources/transfer-request/view.transfer_request.notifications.no_pending_approver_body'))
                            ->warning()
                            ->send();

                        return;
                    }

                    $approval = $pending['approval'] ?? [];
                    $approverEmail = $approval['email'] ?? null;
                    $approverName = $approval['name'] ?? __('form-transfer::filament/resources/transfer-request/view.transfer_request.defaults.approver_name');

                    if (! $approverEmail) {
                        Notification::make()
                            ->title(__('form-transfer::filament/resources/transfer-request/view.transfer_request.notifications.empty_approver_email_title'))
                            ->body(__('form-transfer::filament/resources/transfer-request/view.transfer_request.notifications.empty_approver_email_body'))
                            ->warning()
                            ->send();

                        return;
                    }

                    $approvals = $record->approvals ?? [];
                    app(TransferApprovalNotificationService::class)->notifyApprover($record, $approval, $approvals);

                    if (isset($pending['index'], $approvals[$pending['index']])) {
                        $approvals[$pending['index']]['notified_at'] = now()->toISOString();
                        $record->approvals = $approvals;
                        $record->save();
                    }

                    Notification::make()
                        ->title(__('form-transfer::filament/resources/transfer-request/view.transfer_request.notifications.notification_resent_title'))
                        ->body(__('form-transfer::filament/resources/transfer-request/view.transfer_request.notifications.notification_resent_body', [
                            'approver' => $approverName,
                        ]))
                        ->success()
                        ->send();
                }),
            Action::make('realize-transfer')
                ->label(fn (TransferRequest $record): string => $record->realization_status === TransferRequestRealizationStatus::DONE
                    ? __('form-transfer::filament/resources/transfer-request/actions.edit_realization')
                    : __('form-transfer::filament/resources/transfer-request/actions.realize_transfer'))
                ->icon('heroicon-m-banknotes')
                ->color('success')
                ->slideOver()
                ->modalWidth('md')
                ->visible(fn (TransferRequest $record): bool => Gate::allows('update', $record) && in_array($record->realization_status, [
                    TransferRequestRealizationStatus::PENDING,
                    TransferRequestRealizationStatus::DONE,
                    TransferRequestRealizationStatus::CANCELLED,
                ], true))
                ->form([
                    Select::make('realization_status')
                        ->label(__('form-transfer::filament/resources/transfer-request/fields.realization_status'))
                        ->options([
                            TransferRequestRealizationStatus::DONE->value      => TransferRequestRealizationStatus::DONE->getLabel(),
                            TransferRequestRealizationStatus::CANCELLED->value => TransferRequestRealizationStatus::CANCELLED->getLabel(),
                        ])
                        ->default(TransferRequestRealizationStatus::DONE->value)
                        ->required()
                        ->live(),
                    DatePicker::make('realized_at')
                        ->label(__('form-transfer::filament/resources/transfer-request/fields.realized_at'))
                        ->native(false)
                        ->required(fn (Get $get): bool => $get('realization_status') === TransferRequestRealizationStatus::DONE->value)
                        ->visible(fn (Get $get): bool => $get('realization_status') === TransferRequestRealizationStatus::DONE->value),
                    Textarea::make('realization_notes')
                        ->label(__('form-transfer::filament/resources/transfer-request/fields.realization_notes'))
                        ->rows(3)
                        ->required(fn (Get $get): bool => $get('realization_status') === TransferRequestRealizationStatus::CANCELLED->value),
                    TransferRequestAttachmentField::makeRealizationProof()
                        ->visible(fn (Get $get): bool => $get('realization_status') === TransferRequestRealizationStatus::DONE->value),
                ])
                ->fillForm(fn (TransferRequest $record): array => [
                    'realization_status'      => $record->realization_status === TransferRequestRealizationStatus::CANCELLED
                        ? TransferRequestRealizationStatus::CANCELLED->value
                        : TransferRequestRealizationStatus::DONE->value,
                    'realized_at'             => $record->realized_at,
                    'realization_proof_path'  => $record->realization_proof_path,
                    'realization_notes'       => $record->realization_notes,
                ])
                ->action(function (TransferRequest $record, array $data): void {
                    Gate::authorize('update', $record);

                    $targetStatus = TransferRequestRealizationStatus::tryFrom((string) ($data['realization_status'] ?? ''));

                    if ($targetStatus === TransferRequestRealizationStatus::CANCELLED) {
                        $record->fill([
                            'realization_notes'  => $data['realization_notes'] ?? $record->realization_notes,
                            'realization_status' => TransferRequestRealizationStatus::CANCELLED,
                        ]);

                        $record->save();

                        return;
                    }

                    $wasDone = $record->realization_status === TransferRequestRealizationStatus::DONE;

                    $record->fill([
                        'realized_at'            => $data['realized_at'],
                        'realization_proof_path' => $data['realization_proof_path'] ?? $record->realization_proof_path,
                        'realization_notes'      => $data['realization_notes'] ?? $record->realization_notes,
                        'realization_status'     => TransferRequestRealizationStatus::DONE,
                    ]);

                    if (! $wasDone && Auth::id()) {
                        $record->user_id = Auth::id();
                    }

                    $record->save();
                })
                ->modalHeading(fn (TransferRequest $record): string => $record->realization_status === TransferRequestRealizationStatus::DONE
                    ? __('form-transfer::filament/resources/transfer-request/actions.edit_realization')
                    : __('form-transfer::filament/resources/transfer-request/actions.realize_transfer')),
        ];
    }

    protected function hasPendingApprover(TransferRequest $record): bool
    {
        if ($record->approval_status !== TransferRequestApprovalStatus::PENDING) {
            return false;
        }

        return $this->getCurrentPendingApproval($record) !== null;
    }

    /**
     * @return array{index: int, approval: array}|null
     */
    protected function getCurrentPendingApproval(TransferRequest $record): ?array
    {
        $approvals = $record->approvals ?? [];

        if ($approvals === []) {
            return null;
        }

        $pending = app(ApprovalWorkflowService::class)->getCurrentPendingApproval($approvals);

        if (! $pending || ! isset($pending['approval'])) {
            return null;
        }

        $status = $pending['approval']['status'] ?? null;

        if ($status !== ApprovalStatus::PENDING->value) {
            return null;
        }

        return $pending;
    }
}
