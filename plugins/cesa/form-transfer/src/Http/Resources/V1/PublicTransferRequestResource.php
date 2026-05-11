<?php

namespace Cesa\FormTransfer\Http\Resources\V1;

use Cesa\FormTransfer\Enums\TransferRequestApprovalStatus;
use Cesa\FormTransfer\Enums\TransferRequestRealizationStatus;
use Cesa\FormTransfer\Enums\TransferRequestSubmissionStatus;
use Cesa\FormTransfer\Models\TransferRequest;
use Cesa\FormTransfer\Services\TransferApprovalNotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Route;

class PublicTransferRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var TransferRequest $transferRequest */
        $transferRequest = $this->resource;
        $summary = app(TransferApprovalNotificationService::class)->getRequestSummary($transferRequest);

        return [
            'id'                 => $transferRequest->id,
            'uid'                => $transferRequest->uid,
            'status_response_id' => $transferRequest->status_response_id,
            'form_transfer'      => [
                'id'   => $transferRequest->formTransfer?->id,
                'code' => $transferRequest->formTransfer?->code,
                'name' => $transferRequest->formTransfer?->name,
            ],
            'requester' => [
                'name'  => $transferRequest->requester_name,
                'email' => $transferRequest->email,
            ],
            'division' => [
                'id'   => $transferRequest->division_id,
                'name' => $transferRequest->division_name,
            ],
            'bank' => [
                'id'           => $transferRequest->bank_id,
                'code'         => $transferRequest->bank?->code,
                'name'         => $transferRequest->bank?->name,
                'display_name' => $transferRequest->bank_display_name,
            ],
            'account' => [
                'number' => $transferRequest->account_number,
                'name'   => $transferRequest->account_name,
            ],
            'amounts' => [
                'transfer'  => $transferRequest->transfer_amount,
                'realized'  => $transferRequest->realized_amount,
                'remaining' => $transferRequest->remaining_realization_amount,
            ],
            'purpose'        => $transferRequest->purpose,
            'reference_note' => $transferRequest->reference_note,
            'statuses'       => [
                'submission'  => $this->statusPayload($transferRequest->submission_status, TransferRequestSubmissionStatus::class),
                'approval'    => $this->statusPayload($transferRequest->approval_status, TransferRequestApprovalStatus::class),
                'realization' => $this->statusPayload($transferRequest->realization_status, TransferRequestRealizationStatus::class),
                'current'     => [
                    'label' => $summary['status'] ?? null,
                    'color' => $summary['status_color'] ?? null,
                ],
            ],
            'approvals'   => $this->approvalPayloads($transferRequest->approvals ?? []),
            'attachments' => [
                'invoices'            => $this->attachmentPayloads($transferRequest->invoice_path, $summary['invoice_links'] ?? []),
                'account_attachments' => $this->attachmentPayloads($transferRequest->account_attachment_path, $summary['account_attachment_links'] ?? []),
            ],
            'realizations' => $transferRequest->realizations
                ->map(fn ($realization): array => [
                    'amount'      => $realization->amount,
                    'realized_at' => $realization->realized_at?->toDateString(),
                    'notes'       => $realization->notes,
                ])
                ->values(),
            'links' => [
                'progress_web' => $this->routeIfAvailable('form-transfer.public.progress', [
                    'response' => $transferRequest->status_response_id,
                ]),
                'progress_api' => $this->routeIfAvailable('form-transfer.api.transfer-requests.progress.show', [
                    'response' => $transferRequest->status_response_id,
                ]),
            ],
            'created_at' => $transferRequest->created_at?->toIso8601String(),
            'updated_at' => $transferRequest->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @param  class-string  $enumClass
     */
    protected function statusPayload(mixed $value, string $enumClass): array
    {
        $status = $value instanceof $enumClass
            ? $value
            : $enumClass::tryFrom((string) $value);

        return [
            'value' => $status?->value,
            'label' => $status?->getLabel(),
            'color' => $status?->getColor(),
        ];
    }

    /**
     * @param  array<int, string>  $paths
     * @param  array<int, string>  $urls
     * @return array<int, array{name: string, url: string|null}>
     */
    protected function attachmentPayloads(array $paths, array $urls): array
    {
        return collect($paths)
            ->values()
            ->map(fn (string $path, int $index): array => [
                'name' => basename($path),
                'url'  => $urls[$index] ?? null,
            ])
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $approvals
     * @return array<int, array{label: string|null, name: string|null, title: string|null, status: string|null, noted_at: string|null}>
     */
    protected function approvalPayloads(array $approvals): array
    {
        return collect($approvals)
            ->values()
            ->map(fn (array $approval): array => [
                'label'    => is_string($approval['label'] ?? null) ? $approval['label'] : null,
                'name'     => is_string($approval['name'] ?? null) ? $approval['name'] : null,
                'title'    => is_string($approval['title'] ?? null) ? $approval['title'] : null,
                'status'   => is_string($approval['status'] ?? null) ? $approval['status'] : null,
                'noted_at' => is_string($approval['noted_at'] ?? null) ? $approval['noted_at'] : null,
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    protected function routeIfAvailable(string $name, array $parameters = []): ?string
    {
        if (! Route::has($name)) {
            return null;
        }

        return route($name, $parameters);
    }
}
