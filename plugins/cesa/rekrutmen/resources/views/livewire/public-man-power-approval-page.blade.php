@php
    $requestManPower = $approvalRecord->requestManPower;
    $requestStatus = $requestManPower->status;
    $requestStatusLabel = $requestStatus?->getLabel() ?? '-';
    $requestStatusClasses = match ($requestStatus?->value) {
        'approved' => 'bg-emerald-100 text-emerald-700',
        'rejected' => 'bg-red-100 text-red-700',
        default => 'bg-amber-100 text-amber-700',
    };
@endphp

<div class="min-h-screen bg-[#EFF6FF] py-8 px-4 sm:px-6 lg:px-8 font-sans antialiased">
    <div class="mx-auto max-w-3xl space-y-4">
        <div class="rounded-lg border-t-[10px] cesa-primary-border bg-white shadow-sm">
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
                    <span class="{{ $requestStatusClasses }} inline-flex items-center rounded-full px-3 py-1 text-xs font-medium">
                        {{ $requestStatusLabel }}
                    </span>
                    <span class="text-gray-300">|</span>
                    <span class="font-mono text-gray-400">{{ $requestManPower->status_response_id }}</span>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="cesa-primary-bg px-6 py-4 text-white">
                <h2 class="text-lg font-medium">{{ __('rekrutmen::livewire/public-request-man-power-approval-page.summary_heading') }}</h2>
            </div>
            <div class="grid grid-cols-1 gap-6 px-6 py-5 sm:grid-cols-2">
                <div class="space-y-1">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                        {{ __('rekrutmen::livewire/public-request-man-power-approval-page.fields.position') }}
                    </p>
                    <div class="text-base font-medium text-gray-900 break-words">
                        {{ $requestManPower->posisi_dibutuhkan ?? '-' }}
                    </div>
                </div>
                <div class="space-y-1">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                        {{ __('rekrutmen::livewire/public-request-man-power-approval-page.fields.division') }}
                    </p>
                    <div class="text-base font-medium text-gray-900 break-words">
                        {{ $requestManPower->division_name ?? '-' }}
                    </div>
                </div>
                <div class="space-y-1">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                        {{ __('rekrutmen::livewire/public-request-man-power-approval-page.fields.business_entity') }}
                    </p>
                    <div class="text-base font-medium text-gray-900 break-words">
                        {{ $requestManPower->business_entity_name ?? '-' }}
                    </div>
                </div>
                <div class="space-y-1">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                        {{ __('rekrutmen::livewire/public-request-man-power-approval-page.fields.estimated_join') }}
                    </p>
                    <div class="text-base font-medium text-gray-900 break-words">
                        {{ $requestManPower->estimasi_tanggal_join?->translatedFormat('d F Y') ?? '-' }}
                    </div>
                </div>
                <div class="space-y-1 sm:col-span-2">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                        {{ __('rekrutmen::livewire/public-request-man-power-approval-page.fields.requirements') }}
                    </p>
                    <div class="text-base font-medium text-gray-900 break-words whitespace-pre-line">
                        {{ $requestManPower->requirements_kualifikasi ?? '-' }}
                    </div>
                </div>
                <div class="space-y-1 sm:col-span-2">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                        {{ __('rekrutmen::livewire/public-request-man-power-approval-page.fields.job_description') }}
                    </p>
                    <div class="text-base font-medium text-gray-900 break-words whitespace-pre-line">
                        {{ $requestManPower->job_description ?? '-' }}
                    </div>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-lg font-medium text-gray-900">{{ __('rekrutmen::livewire/public-request-man-power-approval-page.approval_flow_heading') }}</h2>
            </div>
            <div class="space-y-3 px-6 py-5">
                @foreach ($requestManPower->approvals->sortBy('step_order') as $approval)
                    <div class="rounded-lg border border-gray-200 px-4 py-3">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <p class="text-sm font-semibold text-gray-900">
                                    {{ __('rekrutmen::livewire/public-request-man-power-approval-page.step_label', ['step' => $approval->step_order]) }}
                                    - {{ $approval->approver_name }}
                                </p>
                                <p class="text-sm text-gray-600">{{ $approval->approver_title ?: '-' }}</p>
                            </div>
                            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium {{ match ($approval->status?->value) {
                                'approved' => 'bg-emerald-100 text-emerald-700',
                                'rejected' => 'bg-red-100 text-red-700',
                                'pending' => 'bg-amber-100 text-amber-700',
                                default => 'bg-gray-100 text-gray-700',
                            } }}">
                                {{ $approval->status?->getLabel() ?? '-' }}
                            </span>
                        </div>
                        @if (filled($approval->notes))
                            <p class="mt-3 whitespace-pre-line text-sm text-gray-600">{{ $approval->notes }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-lg font-medium text-gray-900">{{ __('rekrutmen::livewire/public-request-man-power-approval-page.action_heading') }}</h2>
                <p class="mt-1 text-sm text-gray-600">{{ __('rekrutmen::livewire/public-request-man-power-approval-page.action_subheading') }}</p>
            </div>

            <div class="px-6 py-5">
                {{ $this->form }}

                <div class="mt-6 flex flex-wrap gap-3">
                    <x-filament::button
                        color="success"
                        wire:click="approve"
                        :disabled="! $this->isPendingApproval()"
                    >
                        {{ __('rekrutmen::livewire/public-request-man-power-approval-page.actions.approve') }}
                    </x-filament::button>

                    <x-filament::button
                        color="danger"
                        wire:click="reject"
                        :disabled="! $this->isPendingApproval()"
                    >
                        {{ __('rekrutmen::livewire/public-request-man-power-approval-page.actions.reject') }}
                    </x-filament::button>

                    <x-filament::button
                        color="gray"
                        tag="a"
                        :href="$requestManPower->getPublicProgressUrl()"
                    >
                        {{ __('rekrutmen::livewire/public-request-man-power-approval-page.actions.view_progress') }}
                    </x-filament::button>
                </div>
            </div>
        </div>
    </div>
</div>
