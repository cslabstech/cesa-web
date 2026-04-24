@php
    $requestManPower = $approvalRecord->requestManPower;
    $requestStatus = $requestManPower->status;
    $requestStatusLabel = $requestStatus?->getLabel() ?? '-';
    $requestStatusColor = match ($requestStatus?->value) {
        'approved' => 'success',
        'rejected' => 'danger',
        'hold' => 'gray',
        default => 'warning',
    };
    
    $currentStatusValue = strtolower($approvalRecord->status?->value ?? 'pending');
    $currentStatusLabel = $approvalRecord->status?->getLabel() ?? ucfirst($currentStatusValue);
    $currentStatusColor = match ($currentStatusValue) {
        'approved' => 'success',
        'rejected' => 'danger',
        'pending'  => 'warning',
        default    => 'gray',
    };

    $summaryItems = [
        [
            'label' => __('rekrutmen::livewire/public-request-man-power-approval-page.fields.position'),
            'value' => $requestManPower->posisi_dibutuhkan ?? '-',
        ],
        [
            'label' => __('rekrutmen::livewire/public-request-man-power-approval-page.fields.division'),
            'value' => $requestManPower->division_name ?? '-',
        ],
        [
            'label' => __('rekrutmen::livewire/public-request-man-power-approval-page.fields.business_entity'),
            'value' => $requestManPower->business_entity_name ?? '-',
        ],
        [
            'label' => __('rekrutmen::livewire/public-request-man-power-approval-page.fields.estimated_join'),
            'value' => $requestManPower->estimasi_tanggal_join?->translatedFormat('d F Y') ?? '-',
        ],
        [
            'label' => __('rekrutmen::livewire/public-request-man-power-approval-page.fields.requirements'),
            'value' => $requestManPower->requirements_kualifikasi ?? '-',
            'full_width' => true,
            'is_html' => true,
        ],
        [
            'label' => __('rekrutmen::livewire/public-request-man-power-approval-page.fields.job_description'),
            'value' => $requestManPower->job_description ?? '-',
            'full_width' => true,
            'is_html' => true,
        ],
    ];
@endphp

<div class="min-h-screen bg-[#EFF6FF] py-8 px-4 sm:px-6 lg:px-8 font-sans antialiased">
    <div class="mx-auto max-w-2xl">
        <div class="mb-4 rounded-lg border-t-[10px] cesa-primary-border bg-white shadow-sm">
            <div class="px-6 pt-5 pb-6">
                <h1 class="text-[32px] font-normal leading-tight text-gray-900">
                    {{ __('rekrutmen::livewire/public-request-man-power-approval-page.page_title') }}
                </h1>
                <p class="mt-2 text-sm text-gray-600">
                    {{ __('rekrutmen::livewire/public-request-man-power-approval-page.requester_label') }}
                    <span class="font-medium text-gray-900">{{ $requestManPower->nama_pengaju }}</span>
                </p>
            </div>
            <div class="border-t border-gray-200 px-6 py-3">
                <div class="flex flex-wrap items-center gap-2 text-xs text-gray-600">
                    <span>{{ __('rekrutmen::livewire/public-request-man-power-approval-page.current_status') }}</span>
                    <x-filament::badge :color="$requestStatusColor" class="rounded-full px-3 py-1 text-xs font-medium">
                        {{ $requestStatusLabel }}
                    </x-filament::badge>
                    <span class="text-gray-300">|</span>
                    <span>Status Persetujuan Anda</span>
                    <x-filament::badge :color="$currentStatusColor" class="rounded-full px-3 py-1 text-xs font-medium">
                        {{ $currentStatusLabel }}
                    </x-filament::badge>
                </div>
            </div>
        </div>

        <div class="space-y-4">
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <div class="-mx-6 -mt-6 mb-6 rounded-t-lg px-6 py-3 text-white cesa-primary-bg">
                    <h2 class="text-lg font-medium">{{ __('rekrutmen::livewire/public-request-man-power-approval-page.summary_heading') }}</h2>
                </div>
                <div class="grid grid-cols-1 gap-y-6">
                    @foreach ($summaryItems as $item)
                        <div class="space-y-1">
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                                {{ $item['label'] }}
                            </p>
                            <div class="text-base font-medium text-gray-900 break-words {{ empty($item['is_html']) ? 'whitespace-pre-line' : 'prose prose-sm max-w-none' }}">@if (!empty($item['is_html'])){!! $item['value'] ?? '-' !!}@else{{ $item['value'] ?? '-' }}@endif</div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <div class="-mx-6 -mt-6 mb-6 rounded-t-lg px-6 py-3 text-white cesa-primary-bg">
                    <h2 class="text-lg font-medium">{{ __('rekrutmen::livewire/public-request-man-power-approval-page.approval_flow_heading') }}</h2>
                </div>
                <ol class="space-y-4">
                    @php
                        $currentApprovalStep = $approvalRecord->step_order ?? null;
                    @endphp
                    @foreach ($requestManPower->approvals->sortBy('step_order') as $approval)
                        @php
                            $loopStatusValue = strtolower($approval->status?->value ?? 'pending');
                            $loopStatusLabel = $approval->status?->getLabel() ?? ucfirst($loopStatusValue);
                            $loopStatusColor = match ($loopStatusValue) {
                                'approved' => 'success',
                                'rejected' => 'danger',
                                'pending'  => 'warning',
                                default    => 'gray',
                            };
                            $isCurrent = $approval->step_order === $currentApprovalStep;
                        @endphp
                        <li @class([
                            'rounded-lg border p-4',
                            'cesa-primary-border cesa-primary-soft' => $isCurrent,
                            'border-gray-200' => !$isCurrent,
                        ])>
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">
                                        {{ __('rekrutmen::livewire/public-request-man-power-approval-page.step_label', ['step' => $approval->step_order]) }}
                                        - {{ $approval->approver_name }}
                                    </p>
                                    <p class="text-sm text-gray-600">
                                        {{ $approval->approver_title ?: '-' }}
                                    </p>
                                </div>
                                <x-filament::badge :color="$loopStatusColor" class="rounded-full px-3 py-1 text-xs font-medium">
                                    {{ $loopStatusLabel }}
                                </x-filament::badge>
                            </div>

                            @if (filled($approval->notes))
                                <div class="mt-3 rounded-xl border border-gray-200 bg-gray-50 p-4">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        {{ __('form-transfer::public.form.actions.comments') ?? 'Catatan' }}
                                    </p>
                                    <p class="mt-1 text-sm leading-relaxed text-gray-700">
                                        {{ $approval->notes }}
                                    </p>
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </div>

            @if ($this->isPendingApproval() && ! $actionTaken)
                <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                    <div class="-mx-6 -mt-6 mb-6 rounded-t-lg px-6 py-3 text-white cesa-primary-bg">
                        <h2 class="text-lg font-medium">{{ __('rekrutmen::livewire/public-request-man-power-approval-page.action_heading') }}</h2>
                    </div>

                    <form class="space-y-6">
                        {{ $this->form }}

                        <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
                            <x-filament::button
                                color="danger"
                                wire:click="reject"
                                :disabled="! $this->isPendingApproval()"
                                wire:loading.attr="disabled"
                                wire:target="reject"
                                icon="heroicon-m-x-circle"
                                class="w-full sm:w-auto"
                            >
                                {{ __('rekrutmen::livewire/public-request-man-power-approval-page.actions.reject') }}
                            </x-filament::button>

                            <x-filament::button
                                color="success"
                                wire:click="approve"
                                :disabled="! $this->isPendingApproval()"
                                wire:loading.attr="disabled"
                                wire:target="approve"
                                icon="heroicon-m-check-circle"
                                class="w-full sm:w-auto"
                            >
                                {{ __('rekrutmen::livewire/public-request-man-power-approval-page.actions.approve') }}
                            </x-filament::button>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    </div>
</div>
