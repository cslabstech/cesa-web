<?php

namespace Cesa\FormTransfer\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Route;

class PublicFormTransferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $identifier = $this->code ?: $this->getKey();
        $isExternal = $this->usesExternalPublicEntry();

        return [
            'id'                             => $this->id,
            'code'                           => $this->code,
            'name'                           => $this->name,
            'description'                    => $this->description,
            'entry_type'                     => $this->public_entry_type,
            'is_internal'                    => ! $isExternal,
            'external_url'                   => $isExternal ? $this->public_external_url : null,
            'badge_label'                    => $this->public_badge_label,
            'sort_order'                     => $this->public_sort_order,
            'show_on_transfer_request_index' => $this->show_on_transfer_request_index,
            'show_on_affiliate_index'        => $this->show_on_affiliate_index,
            'references'                     => $this->when(
                $this->relationLoaded('divisions') || $this->relationLoaded('referenceNotes') || $this->relationLoaded('approvalWorkflows'),
                fn (): array => [
                    'divisions' => $this->whenLoaded('divisions', fn () => $this->divisions
                        ->map(fn ($division): array => [
                            'id'          => $division->id,
                            'name'        => $division->name,
                            'description' => $division->description,
                        ])
                        ->values()),
                    'reference_notes' => $this->whenLoaded('referenceNotes', fn () => $this->referenceNotes
                        ->map(fn ($referenceNote): array => [
                            'id'          => $referenceNote->id,
                            'value'       => $referenceNote->label,
                            'label'       => $referenceNote->label,
                            'description' => $referenceNote->description,
                        ])
                        ->values()),
                    'approval_workflows' => $this->whenLoaded('approvalWorkflows', fn () => $this->approvalWorkflows
                        ->map(fn ($workflow): array => [
                            'id'          => $workflow->id,
                            'division_id' => $workflow->division_id,
                            'name'        => $workflow->name,
                            'code'        => $workflow->code,
                            'step_count'  => $workflow->step_count,
                        ])
                        ->values()),
                ]
            ),
            'links' => [
                'web'        => $isExternal
                    ? $this->public_external_url
                    : $this->routeIfAvailable('form-transfer.public.form', ['formTransfer' => $identifier]),
                'api_detail' => $isExternal
                    ? null
                    : $this->routeIfAvailable('form-transfer.api.transfer-requests.show', ['formTransfer' => $identifier]),
                'api_submit' => $isExternal
                    ? null
                    : $this->routeIfAvailable('form-transfer.api.transfer-requests.store', ['formTransfer' => $identifier]),
            ],
        ];
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
