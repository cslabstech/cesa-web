<x-filament-panels::page>
    <div class="mb-4 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">
                    {{ __('rekrutmen::filament/resources/activity-log.table.filters.job_posting_id') }}
                </label>
                <select
                    wire:model.live="jobPostingId"
                    class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                >
                    <option value="">{{ __('rekrutmen::filament/resources/activity-log.table.filters.all_job_postings') }}</option>
                    @foreach($this->jobPostingOptions as $jobPostingId => $jobPostingTitle)
                        <option value="{{ $jobPostingId }}">{{ $jobPostingTitle }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">
                    {{ __('rekrutmen::filament/resources/activity-log.table.filters.stage_id') }}
                </label>
                <select
                    wire:model.live="stageId"
                    class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                >
                    <option value="">{{ __('rekrutmen::filament/resources/activity-log.table.filters.all_stages') }}</option>
                    @foreach($this->stageOptions as $stageId => $stageName)
                        <option value="{{ $stageId }}">{{ $stageName }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">
                    {{ __('rekrutmen::filament/resources/activity-log.table.filters.date_from') }}
                </label>
                <input
                    type="date"
                    wire:model.live="dateFrom"
                    class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                >
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">
                    {{ __('rekrutmen::filament/resources/activity-log.table.filters.date_until') }}
                </label>
                <input
                    type="date"
                    wire:model.live="dateTo"
                    class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                >
            </div>

            <div class="flex items-end">
                <x-filament::button color="gray" wire:click="resetFilters" class="w-full">
                    {{ __('rekrutmen::filament/resources/activity-log.table.actions.reset_filters') }}
                </x-filament::button>
            </div>
        </div>
    </div>

    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="fi-section-header flex items-center gap-x-3 px-4 py-3">
            <h2 class="fi-section-header-heading text-sm font-medium text-gray-950 dark:text-white">
                {{ __('rekrutmen::filament/resources/activity-log.table.columns.recent_activities') }}
            </h2>
        </div>

        @if($this->activities->count() > 0)
            <div class="divide-y divide-gray-100 dark:divide-gray-700/50">
                @foreach($this->activities as $activity)
                    @php
                        $groupId = $activity->activity_group_id;
                        $group = $groupId ? ($this->activityEntries[$groupId] ?? ['total' => 0, 'passed' => 0, 'failed' => 0, 'pending' => 0]) : ['total' => 0, 'passed' => 0, 'failed' => 0, 'pending' => 0];
                    @endphp
                    <div class="flex items-center justify-between px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-800/50">
                        <div class="flex items-center gap-3 min-w-0 flex-1">
                            <div class="flex-shrink-0">
                                <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                    {{ $activity->activity_date?->format('d M Y') }}
                                </span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                    {{ $activity->activity_title }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                    {{ $activity->activityLabel() }}
                                    @if($activity->jobApplication?->jobPosting?->title)
                                        &middot; {{ $activity->jobApplication->jobPosting->title }}
                                    @endif
                                    @if($activity->performer)
                                        &middot; {{ $activity->performer->name }}
                                    @endif
                                </p>
                            </div>
                            @if(auth()->user()?->can('delete', $activity))
                                <div class="mr-4 flex-shrink-0">
                                    <button
                                        type="button"
                                        wire:click="deleteActivity('{{ $groupId }}')"
                                        wire:confirm="{{ __('rekrutmen::filament/resources/activity-log.table.actions.delete_confirmation') }}"
                                        class="inline-flex items-center rounded-lg border border-red-200 px-2.5 py-1 text-xs font-medium text-red-600 transition hover:bg-red-50 dark:border-red-900/50 dark:text-red-300 dark:hover:bg-red-950/40"
                                    >
                                        {{ __('rekrutmen::filament/resources/activity-log.table.actions.delete') }}
                                    </button>
                                </div>
                            @endif
                            <div class="flex items-center gap-2 flex-shrink-0 ml-4">
                                @if($group['passed'] > 0)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-700">
                                        {{ __('rekrutmen::livewire/recruitment-progress-report.summary_text.passed', ['count' => $group['passed']]) }}
                                    </span>
                                @endif
                                @if($group['failed'] > 0)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-50 text-red-700">
                                        {{ __('rekrutmen::livewire/recruitment-progress-report.summary_text.failed', ['count' => $group['failed']]) }}
                                    </span>
                                @endif
                                @if($group['pending'] > 0)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-50 text-amber-700">
                                        {{ __('rekrutmen::livewire/recruitment-progress-report.summary_text.pending', ['count' => $group['pending']]) }}
                                    </span>
                                @endif
                                <span class="text-xs text-gray-400">
                                    {{ __('rekrutmen::livewire/recruitment-progress-report.summary_text.total_candidates', ['count' => $group['total']]) }}
                                </span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="border-t border-gray-100 px-4 py-3 dark:border-gray-700/50">
                {{ $this->activities->links() }}
            </div>
        @else
            <div class="flex flex-col items-center justify-center py-12 text-center">
                <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <p class="text-sm text-gray-500">{{ __('rekrutmen::livewire/recruitment-progress-report.empty.no_activities') }}</p>
            </div>
        @endif
    </div>
</x-filament-panels::page>
