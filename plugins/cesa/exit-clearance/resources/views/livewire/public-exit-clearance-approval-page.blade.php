<div class="min-h-screen bg-[#EFF6FF] py-8 px-4 sm:px-6 lg:px-8 font-sans antialiased">
    <div class="mx-auto max-w-2xl">
        @php
            $formStatusValue = strtolower($statusLabel);
            $formStatusColor = match ($formStatusValue) {
                'approved' => 'success',
                'rejected' => 'danger',
                default => 'gray',
            };
            $currentStatusValue = strtolower($currentApproval['status'] ?? 'pending');
            $currentStatusEnum = \Cesa\ExitClearance\Enums\ApprovalStatus::tryFrom($currentStatusValue);
            $currentStatusLabel = $currentStatusEnum ? $currentStatusEnum->getLabel() : ucfirst($currentStatusValue);
            $currentStatusColor = $currentStatusEnum?->getColor() ?? 'gray';
        @endphp

        <div class="mb-4 rounded-lg border-t-[10px] cesa-primary-border bg-white shadow-sm">
            <div class="px-6 pt-5 pb-6">
                <h1 class="text-[32px] font-normal leading-tight text-gray-900">{{ __('exit-clearance::livewire/public-exit-clearance-approval-page.page_title') }}</h1>
                <p class="mt-2 text-sm text-gray-600">
                    {{ __('exit-clearance::livewire/public-exit-clearance-approval-page.please_review') }} <span class="font-medium text-gray-900">{{ $requestRecord->name ?? 'User' }}</span>.
                </p>
            </div>
            <div class="border-t border-gray-200 px-6 py-3">
                <div class="flex flex-wrap items-center gap-2 text-xs text-gray-600">
                    <span>{{ __('exit-clearance::livewire/public-exit-clearance-approval-page.submission_status') }}</span>
                    <x-filament::badge :color="$formStatusColor" class="rounded-full px-3 py-1 text-xs font-medium">
                        {{ $statusLabel }}
                    </x-filament::badge>
                    <span class="text-gray-300">|</span>
                    <span>{{ __('exit-clearance::livewire/public-exit-clearance-approval-page.your_approval_status') }}</span>
                    <x-filament::badge :color="$currentStatusColor" class="rounded-full px-3 py-1 text-xs font-medium">
                        {{ $currentStatusLabel }}
                    </x-filament::badge>
                    @if(!empty($requestRecord->form_uid))
                        <span class="text-gray-300">|</span>
                        <span class="font-mono text-gray-400">{{ $requestRecord->form_uid }}</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="space-y-4">
            <div x-data="{ expanded: true, activeTab: 'data_diri' }" class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <div
                    @click="expanded = !expanded"
                    class="cesa-primary-bg flex cursor-pointer items-center justify-between px-6 py-4 text-white cesa-primary-bg-hover transition-colors"
                >
                    <h2 class="text-lg font-medium">{{ __('exit-clearance::livewire/public-exit-clearance-approval-page.submission_summary') }}</h2>
                    <button type="button" class="text-white hover:text-gray-200 focus:outline-none">
                        <svg
                            class="h-5 w-5 transform transition-transform duration-200"
                            :class="{'rotate-180': expanded}"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                </div>

                <div x-show="expanded" x-collapse class="border-t border-blue-100 px-6 py-5">
                    <div class="border-b border-gray-200 pb-4">
                        <nav class="-mb-px flex space-x-0" aria-label="Tabs">
                            <button
                                @click="activeTab = 'data_diri'"
                                :class="activeTab === 'data_diri'
                                    ? 'border-primary-600 text-primary-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="flex-1 justify-center border-b-2 px-1 py-3 text-center text-sm font-medium whitespace-nowrap transition-colors duration-200"
                            >
                                {{ __('exit-clearance::livewire/public-exit-clearance-approval-page.personal_data') }}
                            </button>

                            <button
                                @click="activeTab = 'kuesioner'"
                                :class="activeTab === 'kuesioner'
                                    ? 'border-primary-600 text-primary-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="flex-1 justify-center border-b-2 px-1 py-3 text-center text-sm font-medium whitespace-nowrap transition-colors duration-200"
                            >
                                {{ __('exit-clearance::livewire/public-exit-clearance-approval-page.questionnaire') }}
                            </button>

                            <button
                                @click="activeTab = 'clearance'"
                                :class="activeTab === 'clearance'
                                    ? 'border-primary-600 text-primary-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="flex-1 justify-center border-b-2 px-1 py-3 text-center text-sm font-medium whitespace-nowrap transition-colors duration-200"
                            >
                                {{ __('exit-clearance::livewire/public-exit-clearance-approval-page.clearance') }}
                            </button>
                        </nav>
                    </div>

                    <div class="pt-5">
                        <div x-show="activeTab === 'data_diri'" class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                            @foreach ($summary['data_diri'] ?? [] as $item)
                                <div class="space-y-1">
                                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        {{ $item['label'] }}
                                    </p>
                                    <div class="text-base font-medium text-gray-900 break-words">
                                        @if (($item['type'] ?? null) === 'link')
                                            @if (!empty($item['value']))
                                                <a class="inline-flex items-center gap-1.5 text-blue-600 hover:text-blue-500 hover:underline" href="{{ $item['value'] }}" target="_blank" rel="noopener">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
                                                    </svg>
                                                    {{ __('exit-clearance::livewire/public-exit-clearance-approval-page.view_attachment') }}
                                                </a>
                                            @else
                                                -
                                            @endif
                                        @else
                                            {{ $item['value'] ?? '-' }}
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div x-show="activeTab === 'kuesioner'" style="display: none;" class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                            @foreach ($summary['kuesioner'] ?? [] as $item)
                                <div class="space-y-1">
                                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        {{ $item['label'] }}
                                    </p>
                                    <div class="text-base font-medium text-gray-900 break-words">
                                        {{ $item['value'] ?? '-' }}
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div x-show="activeTab === 'clearance'" style="display: none;" class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                            @foreach ($summary['clearance'] ?? [] as $item)
                                <div class="space-y-1">
                                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        {{ $item['label'] }}
                                    </p>
                                    <div class="text-base font-medium text-gray-900 break-words">
                                        {{ $item['value'] ?? '-' }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div x-data="{ expanded: true }" class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <div
                    @click="expanded = !expanded"
                    class="cesa-primary-bg flex cursor-pointer items-center justify-between px-6 py-4 text-white cesa-primary-bg-hover transition-colors"
                >
                    <h2 class="text-lg font-medium">{{ __('exit-clearance::livewire/public-exit-clearance-approval-page.approval_flow') }}</h2>
                    <button type="button" class="text-white hover:text-gray-200 focus:outline-none">
                        <svg
                            class="h-5 w-5 transform transition-transform duration-200"
                            :class="{'rotate-180': expanded}"
                            fill="none"
                             viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                </div>

                <div x-show="expanded" x-collapse class="border-t border-blue-100 px-6 py-5">
                    <ol class="space-y-4">
                        @foreach ($approvals as $approval)
                            @php
                                $approvalStatusValue = strtolower($approval['status'] ?? 'pending');
                                if ($approvalStatusValue === 'pending' && $statusLabel === 'Rejected') {
                                    $approvalStatusValue = 'waiting';
                                }
                                $statusEnum = \Cesa\ExitClearance\Enums\ApprovalStatus::tryFrom($approvalStatusValue);
                                $approvalStatusLabel = $statusEnum ? $statusEnum->getLabel() : ucfirst($approvalStatusValue);
                                $approvalStatusColor = $statusEnum?->getColor() ?? 'gray';
                            @endphp
                            <li class="rounded-lg border border-gray-200 p-4">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900">
                                            {{ $approval['name'] ?? '-' }}
                                        </p>
                                        <p class="text-sm text-gray-600">
                                            @if (!empty($approval['title'])) {{ $approval['title'] }} @endif
                                        </p>
                                    </div>
                                    <x-filament::badge :color="$approvalStatusColor" class="rounded-full px-3 py-1 text-xs font-medium">
                                        {{ $approvalStatusLabel }}
                                    </x-filament::badge>
                                </div>

                                @if (!empty($approval['notes']))
                                    <div class="mt-3 rounded-xl border border-gray-200 bg-gray-50 p-4">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                            {{ __('exit-clearance::livewire/public-exit-clearance-approval-page.notes') }}
                                        </p>
                                        <p class="mt-1 text-sm leading-relaxed text-gray-700">
                                            {{ $approval['notes'] }}
                                        </p>
                                    </div>
                                @endif

                                @if (!empty($approval['approved_at']))
                                    <p class="mt-3 text-xs text-gray-500">
                                        {{ __('exit-clearance::livewire/public-exit-clearance-approval-page.process_time') }} {{ $approval['approved_at'] }}
                                    </p>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </div>
            </div>

            @if ($this->isPendingApproval() && ! $actionTaken)
                <div class="rounded-lg bg-white p-6 shadow-sm border border-gray-200">
	                    <div class="-mx-6 -mt-6 mb-6 rounded-t-lg cesa-primary-bg px-6 py-3 text-white">
                        <h2 class="text-lg font-medium">{{ __('exit-clearance::livewire/public-exit-clearance-approval-page.action') }}</h2>
                    </div>

                    <form
                        wire:submit="approve"
                        class="space-y-6"
                    >
                        {{ $this->form }}

                        <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
                            <x-filament::button
                                type="button"
                                color="danger"
                                wire:click="reject"
                                wire:loading.attr="disabled"
                                wire:target="reject"
                                icon="heroicon-m-x-circle"
                                class="w-full sm:w-auto"
                            >
                                {{ __('exit-clearance::livewire/public-exit-clearance-approval-page.reject') }}
                            </x-filament::button>

                            <x-filament::button
                                type="submit"
                                color="success"
                                wire:loading.attr="disabled"
                                wire:target="approve"
                                icon="heroicon-m-check-circle"
                                class="w-full sm:w-auto"
                            >
                                {{ __('exit-clearance::livewire/public-exit-clearance-approval-page.approve') }}
                            </x-filament::button>
                        </div>
                    </form>
                </div>
            @else
                <div class="rounded-lg bg-white p-6 shadow-sm border border-gray-200">
	                    <div class="-mx-6 -mt-6 mb-6 rounded-t-lg cesa-primary-bg px-6 py-3 text-white">
                        <h2 class="text-lg font-medium">{{ __('exit-clearance::livewire/public-exit-clearance-approval-page.information') }}</h2>
                    </div>

                    <p class="text-sm text-gray-600">
                        {{ __('exit-clearance::livewire/public-exit-clearance-approval-page.already_processed') }}
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>
