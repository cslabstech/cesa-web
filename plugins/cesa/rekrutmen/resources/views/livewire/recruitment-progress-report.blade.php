<div>
    @php
        $colorMap = [
            'gray'    => ['#6b7280', '#f3f4f6'],
            'success' => ['#059669', '#ecfdf5'],
            'danger'  => ['#dc2626', '#fef2f2'],
            'warning' => ['#d97706', '#fffbeb'],
            'primary' => ['#2563eb', '#eff6ff'],
            'info'    => ['#0891b2', '#ecfeff'],
            'purple'  => ['#7c3aed', '#f5f3ff'],
        ];
    @endphp

    <div class="space-y-6">

        {{-- FILTER BAR --}}
        <div>
            {{ $this->form }}
        </div>

        {{-- SUMMARY CARDS --}}
        <div>
            @livewire(\Cesa\Rekrutmen\Livewire\RecruitmentStatsWidget::class, ['summary' => $summary])
        </div>

        {{-- TAB NAVIGATION --}}
        <x-filament::tabs>
            <x-filament::tabs.item wire:click="$set('activeTab', 'timeline')" :active="$activeTab === 'timeline'">
                {{ __('rekrutmen::livewire/recruitment-progress-report.tabs.timeline') }}
            </x-filament::tabs.item>
            <x-filament::tabs.item wire:click="$set('activeTab', 'per-position')" :active="$activeTab === 'per-position'">
                {{ __('rekrutmen::livewire/recruitment-progress-report.tabs.per_position') }}
            </x-filament::tabs.item>
            <x-filament::tabs.item wire:click="$set('activeTab', 'overview')" :active="$activeTab === 'overview'">
                {{ __('rekrutmen::livewire/recruitment-progress-report.tabs.overview') }}
            </x-filament::tabs.item>
        </x-filament::tabs>

        {{-- ============================= --}}
        {{-- TAB: TIMELINE                --}}
        {{-- ============================= --}}
        @if($activeTab === 'timeline')
            <div class="space-y-6">
                @forelse($timelineData as $timelineItem)
                    <div>
                        {{-- Date Header --}}
                        <div class="flex items-center gap-3 mb-4">
                            <div class="px-3 py-1 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded-full text-xs font-semibold">
                                {{ $timelineItem['date_label'] }}
                            </div>
                            <div class="flex-1 h-px bg-gray-200 dark:bg-gray-800"></div>
                            <div class="text-xs text-gray-400">
                                {{ __('rekrutmen::livewire/recruitment-progress-report.labels.activities_count', ['count' => $timelineItem['count']]) }}
                            </div>
                        </div>

                        {{-- Activity Cards --}}
                        @foreach($timelineItem['activities'] as $act)
                            <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm hover:shadow-md transition-shadow mb-4">
                                <div class="p-5">
                                    {{-- Activity Header --}}
                                    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-3 mb-3">
                                        <div class="flex items-start gap-3">
                                            @php $logColor = $colorMap[$act['activity_color'] ?? 'gray'] ?? $colorMap['gray']; @endphp
                                            <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5"
                                                 style="background-color: {{ $logColor[1] }}">
                                                <svg class="w-5 h-5" style="color: {{ $logColor[0] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                </svg>
                                            </div>
                                            <div>
                                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $act['activity_title'] }}</h3>
                                                <div class="flex flex-wrap items-center gap-2 mt-1">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                                          style="background-color: {{ $logColor[1] }}; color: {{ $logColor[0] }}">
                                                        {{ $act['activity_label'] }}
                                                    </span>
                                                    @if($act['to_stage'])
                                                        <span class="text-xs text-gray-400">
                                                            {{ __('rekrutmen::livewire/recruitment-progress-report.labels.stage') }}: {{ $act['to_stage']->name }}
                                                        </span>
                                                    @endif
                                                    @if($act['performer'])
                                                        <span class="text-xs text-gray-400">
                                                            {{ __('rekrutmen::livewire/recruitment-progress-report.labels.by') }} {{ $act['performer']->name }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Result Badges --}}
                                        <div class="flex items-center gap-2 flex-shrink-0">
                                            @if($act['passed_count'] > 0)
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                    {{ __('rekrutmen::livewire/recruitment-progress-report.summary_text.passed', ['count' => $act['passed_count']]) }}
                                                </span>
                                            @endif
                                            @if($act['failed_count'] > 0)
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-red-50 text-red-700">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                                    {{ $act['failed_count'] }} {{ __('rekrutmen::livewire/recruitment-progress-report.labels.failed') }}
                                                </span>
                                            @endif
                                            @if($act['pending_count'] > 0)
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-amber-50 text-amber-700">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                                    {{ $act['pending_count'] }} {{ __('rekrutmen::livewire/recruitment-progress-report.labels.pending') }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Summary Line --}}
                                    <div class="text-sm text-gray-600 lg:pl-[52px]">
                                        <span class="font-semibold text-gray-900 dark:text-white">{{ $act['job_posting']?->title }}</span>
                                        <span class="mx-1.5 text-gray-300 dark:text-gray-600">|</span>
                                        <span>{{ $act['summary'] }}</span>
                                    </div>

                                    {{-- Expandable Candidate Detail --}}
                                    @if($act['entries']->count() > 0)
                                        <details class="lg:pl-[52px] mt-3">
                                            <summary class="text-xs font-medium text-blue-600 dark:text-blue-400 cursor-pointer hover:text-blue-700 dark:hover:text-blue-300 select-none">
                                                {{ __('rekrutmen::livewire/recruitment-progress-report.labels.view_candidates', ['count' => $act['entries']->count()]) }}
                                            </summary>
                                            <div class="mt-3 border border-gray-200 dark:border-gray-800 rounded-lg overflow-hidden">
                                                <table class="w-full text-sm">
                                                    <thead>
                                                        <tr class="bg-gray-50 dark:bg-gray-800/50">
                                                            <th class="text-left px-4 py-2 text-xs font-medium text-gray-500">
                                                                {{ __('rekrutmen::livewire/recruitment-progress-report.table.candidate') }}
                                                            </th>
                                                            <th class="text-left px-4 py-2 text-xs font-medium text-gray-500">
                                                                {{ __('rekrutmen::livewire/recruitment-progress-report.table.result') }}
                                                            </th>
                                                            <th class="text-left px-4 py-2 text-xs font-medium text-gray-500">
                                                                {{ __('rekrutmen::livewire/recruitment-progress-report.table.notes') }}
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                                                        @foreach($act['entries'] as $entry)
                                                            <tr @class([
                                                                'bg-red-50/50 dark:bg-red-900/20' => $entry->result?->value === 'failed',
                                                                'bg-amber-50/50 dark:bg-amber-900/20' => $entry->result?->value === 'pending',
                                                            ])>
                                                                <td class="px-4 py-2.5">
                                                                    <span @class([
                                                                        'font-medium text-gray-400 dark:text-gray-500 line-through' => $entry->result?->value === 'failed',
                                                                        'font-medium text-gray-900 dark:text-white' => $entry->result?->value !== 'failed',
                                                                    ])>
                                                                        {{ $entry->jobApplication?->full_name ?? '-' }}
                                                                    </span>
                                                                </td>
                                                                <td class="px-4 py-2.5">
                                                                    @php $entryColor = $colorMap[$entry->result?->getColor() ?? 'gray'] ?? $colorMap['gray']; @endphp
                                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                                                          style="background-color: {{ $entryColor[1] }}; color: {{ $entryColor[0] }}">
                                                                        {{ $entry->result?->getLabel() ?? '-' }}
                                                                    </span>
                                                                </td>
                                                                <td class="px-4 py-2.5 text-gray-500 max-w-xs truncate">
                                                                    {{ $entry->notes ?? '-' }}
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </details>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @empty
                    <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-12 text-center">
                        <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        <p class="text-sm text-gray-500">
                            {{ __('rekrutmen::livewire/recruitment-progress-report.empty.no_activities') }}
                        </p>
                    </div>
                @endforelse
            </div>
        @endif

        {{-- ============================= --}}
        {{-- TAB: PER POSITION            --}}
        {{-- ============================= --}}
        @if($activeTab === 'per-position')
            <div class="space-y-6">
                @forelse($perPositionData as $positionData)
                    <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
                        {{-- Position Header --}}
                        <div class="p-5 border-b border-gray-200 dark:border-gray-800 bg-gradient-to-r from-blue-50 dark:from-blue-900/20 to-white dark:to-gray-900">
                            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-3">
                                <div>
                                    <div class="flex items-center gap-2 mb-1">
                                        <h2 class="text-base font-bold text-gray-900 dark:text-white">{{ $positionData['posting']->title }}</h2>
                                        @if($positionData['posting']->is_published)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-700">
                                                {{ __('rekrutmen::livewire/recruitment-progress-report.labels.open') }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">
                                                {{ __('rekrutmen::livewire/recruitment-progress-report.labels.closed') }}
                                            </span>
                                        @endif
                                    </div>
                                    @if($positionData['request'])
                                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-500">
                                            <span>{{ __('rekrutmen::livewire/recruitment-progress-report.labels.company') }}: {{ $positionData['request']->company?->name ?? '-' }}</span>
                                            <span>{{ __('rekrutmen::livewire/recruitment-progress-report.labels.location') }}: {{ $positionData['posting']->location }}</span>
                                            @if($positionData['request']->business_entity_name)
                                                <span>{{ $positionData['request']->business_entity_name }}</span>
                                            @endif
                                            <span>{{ __('rekrutmen::livewire/recruitment-progress-report.labels.needed') }}: <strong class="text-gray-700">{{ $positionData['needed'] }} orang</strong></span>
                                            @if($positionData['request']->estimasi_tanggal_join)
                                                <span>{{ __('rekrutmen::livewire/recruitment-progress-report.labels.est_join') }}: {{ $positionData['request']->estimasi_tanggal_join->format('d M Y') }}</span>
                                            @endif
                                            @if($positionData['request']->status_kebutuhan)
                                                <span>{{ $positionData['request']->status_kebutuhan->getLabel() }}</span>
                                            @endif
                                        </div>
                                    @endif
                                    @if($positionData['hired_candidates']->isNotEmpty())
                                        <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
                                            <span class="font-semibold text-emerald-700 dark:text-emerald-400">
                                                {{ __('rekrutmen::livewire/recruitment-progress-report.labels.hired_candidates') }}:
                                            </span>
                                            @foreach($positionData['hired_candidates'] as $candidate)
                                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                                                    {{ $candidate['full_name'] }}
                                                    @if($candidate['hired_at_label'] !== '-')
                                                        <span class="text-emerald-500 dark:text-emerald-400">
                                                            ({{ $candidate['hired_at_label'] }})
                                                        </span>
                                                    @endif
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                                {{-- Stats Mini --}}
                                <div class="flex items-center gap-3">
                                    <div class="text-center px-3">
                                        <div class="text-lg font-bold text-gray-900 dark:text-white">{{ $positionData['statistics']['total_applicants'] }}</div>
                                        <div class="text-xs text-gray-400">Pelamar</div>
                                    </div>
                                    <div class="text-center px-3 border-l border-gray-200 dark:border-gray-800">
                                        <div class="text-lg font-bold text-blue-600">{{ $positionData['statistics']['in_progress'] }}</div>
                                        <div class="text-xs text-gray-400">Proses</div>
                                    </div>
                                    <div class="text-center px-3 border-l border-gray-200 dark:border-gray-800">
                                        <div class="text-lg font-bold text-emerald-600">{{ $positionData['statistics']['hired'] }}</div>
                                        <div class="text-xs text-gray-400">Diterima</div>
                                    </div>
                                    <div class="text-center px-3 border-l border-gray-200 dark:border-gray-800">
                                        <div class="text-lg font-bold text-red-500">{{ $positionData['statistics']['rejected'] }}</div>
                                        <div class="text-xs text-gray-400">Ditolak</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Pipeline Funnel --}}
                        @if(!empty($positionData['pipeline_stages']))
                            <div class="p-5 border-b border-gray-100 dark:border-gray-800">
                                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">
                                    {{ __('rekrutmen::livewire/recruitment-progress-report.labels.pipeline_funnel') }}
                                </h3>
                                <div class="flex items-center gap-2 flex-wrap">
                                    @foreach($positionData['pipeline_stages'] as $idx => $stageInfo)
                                        @if($idx > 0)
                                            <svg class="w-4 h-4 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                            </svg>
                                        @endif
                                        <div class="flex-1 min-w-[120px]">
                                            <div class="flex items-center justify-between mb-1">
                                                <span class="text-xs font-medium text-gray-600 dark:text-gray-400">{{ $stageInfo['name'] }}</span>
                                                <span class="text-xs text-gray-400">
                                                    {{ $stageInfo['current_count'] }} aktif / {{ $stageInfo['total_passed'] }} lolos
                                                </span>
                                            </div>
                                            <div class="h-3 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                                                @php
                                                    $maxApplicants = max($positionData['statistics']['total_applicants'], 1);
                                                    $barWidth = min(100, round(($stageInfo['total_passed'] / $maxApplicants) * 100));
                                                    $stageColors = ['bg-blue-500', 'bg-amber-500', 'bg-indigo-500', 'bg-purple-500', 'bg-pink-500', 'bg-teal-500', 'bg-orange-500', 'bg-cyan-500'];
                                                    $barColor = $stageColors[$idx % count($stageColors)];
                                                @endphp
                                                <div class="h-full {{ $barColor }} rounded-full" style="width: {{ $barWidth }}%"></div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Activity Log --}}
                        @if($positionData['activities']->count() > 0)
                            <div class="p-5">
                                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">
                                    {{ __('rekrutmen::livewire/recruitment-progress-report.labels.activity_history') }}
                                </h3>
                                <div class="relative pl-8 space-y-3">
                                    {{-- Timeline line --}}
                                    <div class="absolute left-[11px] top-3 bottom-3 w-0.5 bg-gray-200 dark:bg-gray-800"></div>

                                    @foreach($positionData['activities'] as $aIdx => $activity)
                                        <div class="relative">
                                            {{-- Timeline dot --}}
                                            @php $actColor = $colorMap[$activity['activity_color'] ?? 'gray'] ?? $colorMap['gray']; @endphp
                                            <div class="absolute -left-[21px] top-1.5 w-3 h-3 rounded-full border-2 bg-white dark:bg-gray-900"
                                                 style="border-color: {{ $actColor[0] }};"></div>
                                            <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-3">
                                                <div class="flex items-center justify-between mb-1">
                                                    <span class="text-xs font-semibold text-gray-900 dark:text-white">{{ $activity['activity_title'] }}</span>
                                                    <span class="text-xs text-gray-400">{{ $activity['activity_date']?->format('d M Y') }}</span>
                                                </div>
                                                <div class="flex flex-wrap items-center gap-2 mb-2">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                                          style="background-color: {{ $actColor[1] }}; color: {{ $actColor[0] }}">
                                                        {{ $activity['activity_label'] ?? '-' }}
                                                    </span>
                                                    @if($activity['performer'])
                                                        <span class="text-xs text-gray-400">
                                                            {{ __('rekrutmen::livewire/recruitment-progress-report.labels.by') }} {{ $activity['performer']->name }}
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="flex items-center gap-3 text-xs">
                                                    @if($activity['passed_count'] > 0)
                                                        <span class="text-emerald-600 font-medium">{{ $activity['passed_count'] }} Lolos</span>
                                                    @endif
                                                    @if($activity['failed_count'] > 0)
                                                        <span class="text-red-500 font-medium">{{ $activity['failed_count'] }} Tidak Lolos</span>
                                                    @endif
                                                    @if($activity['pending_count'] > 0)
                                                        <span class="text-amber-600 font-medium">{{ $activity['pending_count'] }} Menunggu</span>
                                                    @endif
                                                    <span class="text-gray-400">{{ __('rekrutmen::livewire/recruitment-progress-report.labels.total') }}: {{ $activity['total_candidates'] }} orang</span>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="p-8 text-center text-sm text-gray-400">
                                {{ __('rekrutmen::livewire/recruitment-progress-report.empty.no_activities') }}
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-12 text-center">
                        <p class="text-sm text-gray-500">
                            {{ __('rekrutmen::livewire/recruitment-progress-report.empty.no_positions') }}
                        </p>
                    </div>
                @endforelse
            </div>
        @endif

        {{-- ============================= --}}
        {{-- TAB: OVERVIEW                --}}
        {{-- ============================= --}}
        @if($activeTab === 'overview')
            <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-800">
                                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    {{ __('rekrutmen::livewire/recruitment-progress-report.table.position') }}
                                </th>
                                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    {{ __('rekrutmen::livewire/recruitment-progress-report.table.company') }}
                                </th>
                                <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    {{ __('rekrutmen::livewire/recruitment-progress-report.table.needed') }}
                                </th>
                                <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    {{ __('rekrutmen::livewire/recruitment-progress-report.table.applicants') }}
                                </th>
                                <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    {{ __('rekrutmen::livewire/recruitment-progress-report.table.process') }}
                                </th>
                                <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    {{ __('rekrutmen::livewire/recruitment-progress-report.table.accepted') }}
                                </th>
                                <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    {{ __('rekrutmen::livewire/recruitment-progress-report.table.rejected') }}
                                </th>
                                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    {{ __('rekrutmen::livewire/recruitment-progress-report.table.last_activity') }}
                                </th>
                                <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    {{ __('rekrutmen::livewire/recruitment-progress-report.table.fulfillment') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                            @foreach($overviewData as $item)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="px-5 py-4">
                                        <div class="font-semibold text-gray-900 dark:text-white">{{ $item['position'] }}</div>
                                        <div class="text-xs text-gray-400">{{ $item['location'] }}</div>
                                    </td>
                                    <td class="px-4 py-4 text-gray-600 dark:text-gray-400">
                                        {{ $item['company'] ?? '-' }}
                                    </td>
                                    <td class="px-4 py-4 text-center font-semibold text-gray-900 dark:text-white">
                                        {{ $item['needed'] }}
                                    </td>
                                    <td class="px-4 py-4 text-center text-gray-600">
                                        {{ $item['total_applicants'] }}
                                    </td>
                                    <td class="px-4 py-4 text-center">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700">
                                            {{ $item['in_progress'] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 text-center">
                                        @php $hiredCandidates = collect($item['hired_candidates'] ?? []); @endphp
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $item['hired'] > 0 ? 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400' : 'bg-gray-100 dark:bg-gray-800 text-gray-500' }}">
                                            {{ $item['hired'] }}
                                        </span>
                                        @if($hiredCandidates->isNotEmpty())
                                            <div class="mt-1 space-y-0.5 text-[11px] font-medium leading-tight text-emerald-700 dark:text-emerald-400">
                                                @foreach($hiredCandidates as $candidate)
                                                    <div title="{{ $candidate['hired_at_label'] }}">
                                                        {{ $candidate['full_name'] }}
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 text-center">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-400">
                                            {{ $item['rejected'] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4">
                                        @if($item['latest_activity'])
                                            <div class="text-xs font-medium text-gray-900 dark:text-white">{{ $item['latest_activity']['activity_label'] ?? '-' }}</div>
                                            <div class="text-xs text-gray-400">
                                                {{ $item['latest_activity']['activity_date']?->format('d M Y') ?? '-' }} - {{ $item['latest_activity']['summary'] }}
                                            </div>
                                        @else
                                            <span class="text-xs text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 text-center">
                                        <div class="inline-flex items-center gap-1">
                                            <div class="w-16 h-2 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                                                <div class="h-full rounded-full {{ $item['fulfillment_percentage'] >= 100 ? 'bg-emerald-500' : 'bg-amber-500' }}"
                                                     style="width: {{ $item['fulfillment_percentage'] }}%"></div>
                                            </div>
                                            <span class="text-xs font-semibold {{ $item['fulfillment_percentage'] >= 100 ? 'text-emerald-600 dark:text-emerald-500' : 'text-amber-600 dark:text-amber-500' }}">
                                                {{ $item['fulfillment_percentage'] }}%
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="bg-gray-50 dark:bg-gray-800/50 font-semibold">
                                <td class="px-5 py-3 text-gray-900 dark:text-white" colspan="2">{{ __('rekrutmen::livewire/recruitment-progress-report.table.total') }}</td>
                                <td class="px-4 py-3 text-center text-gray-900 dark:text-white">
                                    {{ $overviewData->sum('needed') }}
                                </td>
                                <td class="px-4 py-3 text-center text-gray-900 dark:text-white">
                                    {{ $overviewData->sum('total_applicants') }}
                                </td>
                                <td class="px-4 py-3 text-center text-blue-600">
                                    {{ $overviewData->sum('in_progress') }}
                                </td>
                                <td class="px-4 py-3 text-center text-emerald-600">
                                    {{ $overviewData->sum('hired') }}
                                </td>
                                <td class="px-4 py-3 text-center text-red-500">
                                    {{ $overviewData->sum('rejected') }}
                                </td>
                                <td class="px-4 py-3" colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        @endif

    </div>
</div>
