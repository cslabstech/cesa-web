<div>
    @php
        $focusKeys = ['needs-action', 'data-risk', 'updated', 'hold', 'fulfilled', 'all'];
        $periodText = filled($dateFrom) && filled($dateTo)
            ? sprintf('%s - %s', \Illuminate\Support\Carbon::parse($dateFrom)->format('d M Y'), \Illuminate\Support\Carbon::parse($dateTo)->format('d M Y'))
            : __('rekrutmen::livewire/recruitment-progress-report.filters.period');
    @endphp

    <div class="space-y-5">
        {{-- Filters --}}
        <div class="space-y-2">
            {{ $this->form }}
            <p class="text-xs leading-5 text-gray-500 dark:text-gray-400">
                {{ __('rekrutmen::livewire/recruitment-progress-report.filters.period_hint') }}
            </p>
        </div>

        {{-- Summary metrics --}}
        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <dl>
                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">
                        {{ __('rekrutmen::livewire/recruitment-progress-report.workflow.metrics.remaining') }}
                    </dt>
                    <dd class="mt-1 text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">
                        {{ $workflowSummary['total_remaining'] }}
                    </dd>
                </dl>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <dl>
                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">
                        {{ __("rekrutmen::livewire/recruitment-progress-report.workflow.focus.needs-action.label") }}
                    </dt>
                    <dd class="mt-1 text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">
                        {{ $focusCounts['needs-action'] }}
                    </dd>
                </dl>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <dl>
                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">
                        {{ __('rekrutmen::livewire/recruitment-progress-report.workflow.metrics.fulfilled') }}
                    </dt>
                    <dd class="mt-1 text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">
                        {{ $focusCounts['fulfilled'] }}
                    </dd>
                </dl>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <dl>
                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">
                        {{ __('rekrutmen::livewire/recruitment-progress-report.workflow.metrics.updated') }}
                    </dt>
                    <dd class="mt-1 text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">
                        {{ $workflowSummary['total_updates'] }}
                    </dd>
                </dl>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <dl>
                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">
                        {{ __('rekrutmen::livewire/recruitment-progress-report.workflow.metrics.need') }}
                    </dt>
                    <dd class="mt-1 text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">
                        {{ $workflowSummary['total_hired'] }}/{{ $workflowSummary['total_needed'] }}
                    </dd>
                </dl>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900 sm:col-span-2 xl:col-span-1">
                <dl>
                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">
                        {{ __('rekrutmen::livewire/recruitment-progress-report.filters.period') }}
                    </dt>
                    <dd class="mt-1 text-sm font-semibold leading-5 text-gray-950 dark:text-white">
                        {{ $periodText }}
                    </dd>
                    <dd class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        {{ $focusCounts['all'] }} {{ __('rekrutmen::livewire/recruitment-progress-report.labels.positions') }}
                    </dd>
                </dl>
            </div>
        </section>

        {{-- Focus tabs --}}
        <section class="space-y-4">
            <div class="-mx-1 overflow-x-auto px-1 pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                <div class="flex min-w-max gap-1 rounded-lg bg-gray-100 p-1 dark:bg-gray-900">
                    @foreach($focusKeys as $focusKey)
                        @php $isActive = $focus === $focusKey; @endphp
                        <button
                            type="button"
                            wire:click="setFocus('{{ $focusKey }}')"
                            @class([
                                'inline-flex min-h-9 items-center gap-2 rounded-md px-3 py-1.5 text-sm font-medium transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary-500',
                                'bg-white text-gray-950 shadow-sm dark:bg-gray-800 dark:text-white' => $isActive,
                                'text-gray-600 hover:bg-white/70 hover:text-gray-950 dark:text-gray-400 dark:hover:bg-gray-800/70 dark:hover:text-white' => ! $isActive,
                            ])
                        >
                            {{ __("rekrutmen::livewire/recruitment-progress-report.workflow.focus.{$focusKey}.label") }}
                            <span @class([
                                'rounded-full px-1.5 py-0.5 text-xs font-semibold',
                                'bg-primary-100 text-primary-700 dark:bg-primary-900/40 dark:text-primary-300' => $isActive,
                                'bg-white text-gray-500 dark:bg-gray-800 dark:text-gray-400' => ! $isActive,
                            ])>
                                {{ $focusCounts[$focusKey] }}
                            </span>
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Queue heading --}}
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-950 dark:text-white">
                    {{ __('rekrutmen::livewire/recruitment-progress-report.workflow.queue_heading') }}
                </h3>
                <span class="shrink-0 text-sm font-medium text-gray-500 dark:text-gray-400">
                    {{ __('rekrutmen::livewire/recruitment-progress-report.workflow.queue_count', ['count' => $workflowPositions->count()]) }}
                </span>
            </div>

            {{-- Position cards --}}
            @forelse($workflowPositions as $position)
                @php
                    $posting = $position['posting'];
                    $workflow = $position['workflow'];
                    $activities = collect($position['activities']);
                    $hiredCandidates = collect($position['hired_candidates']);
                    $requestFulfillments = collect($position['request_fulfillments'] ?? []);
                    $cycleHealth = $workflow['cycle_health'] ?? ['status' => 'healthy', 'issues' => []];
                    $progress = $workflow['needed'] > 0
                        ? min(100, round(($workflow['hired'] / $workflow['needed']) * 100))
                        : 0;
                    $actionClasses = match ($workflow['action_key']) {
                        'source', 'recover' => 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
                        'monitor' => 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
                        'hold' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
                        'fulfilled' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
                        default => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
                    };
                    $healthClasses = match ($cycleHealth['status'] ?? 'healthy') {
                        'risk' => 'bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300',
                        'watch' => 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
                        default => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
                    };
                @endphp

                <article wire:key="workflow-position-{{ $posting->id }}" class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="p-4 sm:p-5">
                        <div class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-start">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
                                    <span class="inline-flex items-center gap-1.5 font-medium text-gray-700 dark:text-gray-300">
                                        <span @class([
                                            'h-1.5 w-1.5 rounded-full',
                                            'bg-gray-400' => $workflow['is_on_hold'],
                                            'bg-emerald-500' => $workflow['is_fulfilled'],
                                            'bg-amber-500' => ! $workflow['is_on_hold'] && ! $workflow['is_fulfilled'],
                                        ])></span>
                                        {{ $workflow['is_on_hold'] ? __('rekrutmen::livewire/recruitment-progress-report.labels.on_hold') : $position['request_status_label'] }}
                                    </span>
                                    @if($activities->isNotEmpty())
                                        <span class="text-gray-300 dark:text-gray-700">/</span>
                                        <span>
                                            {{ __('rekrutmen::livewire/recruitment-progress-report.workflow.updated_badge', ['count' => $activities->count()]) }}
                                        </span>
                                    @endif
                                    <span class="text-gray-300 dark:text-gray-700">/</span>
                                    <span>{{ $position['request']?->company?->name ?? '-' }}</span>
                                    <span class="text-gray-300 dark:text-gray-700">/</span>
                                    <span>{{ $posting->location ?? '-' }}</span>
                                </div>
                                <h4 class="mt-2 text-base font-semibold leading-6 text-gray-950 dark:text-white">
                                    {{ $posting->title }}
                                </h4>
                            </div>
                            <div class="flex shrink-0 flex-col items-start gap-1 lg:items-end">
                                <span class="inline-flex items-center rounded-md px-2.5 py-1 text-xs font-semibold {{ $actionClasses }}">
                                    {{ $workflow['action_label'] }}
                                </span>
                                @if($workflow['has_cycle_risk'])
                                    <span class="inline-flex max-w-56 items-center rounded-md px-2.5 py-1 text-xs font-semibold {{ $healthClasses }}">
                                        {{ $cycleHealth['summary'] }}
                                    </span>
                                @endif
                                @if($position['latest_activity'])
                                    <span class="text-xs text-gray-400 dark:text-gray-500">
                                        {{ $position['latest_activity']['activity_date']?->format('d M Y') ?? '-' }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="mt-4 grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(14rem,auto)] lg:items-end">
                            <div>
                                <div class="flex items-center gap-3">
                                    <div class="min-w-0 flex-1">
                                        <div class="h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                                            <div class="h-full rounded-full transition-all {{ $workflow['is_fulfilled'] ? 'bg-emerald-500' : 'bg-amber-500' }}" style="width: {{ $progress }}%"></div>
                                        </div>
                                    </div>
                                    <span class="shrink-0 text-xs font-semibold text-gray-700 dark:text-gray-300">{{ $progress }}%</span>
                                </div>
                            </div>

                            <dl class="grid grid-cols-3 gap-3">
                                <div class="border-t border-gray-100 pt-2 dark:border-gray-800">
                                    <dt class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ __('rekrutmen::livewire/recruitment-progress-report.labels.needed') }}
                                    </dt>
                                    <dd class="mt-0.5 text-sm font-semibold text-gray-950 dark:text-white">{{ $workflow['needed'] }}</dd>
                                </div>
                                <div class="border-t border-gray-100 pt-2 dark:border-gray-800">
                                    <dt class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ __('rekrutmen::livewire/recruitment-progress-report.labels.hired') }}
                                    </dt>
                                    <dd class="mt-0.5 text-sm font-semibold text-gray-950 dark:text-white">{{ $workflow['hired'] }}</dd>
                                </div>
                                <div class="border-t border-gray-100 pt-2 dark:border-gray-800">
                                    <dt class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ __('rekrutmen::livewire/recruitment-progress-report.labels.remaining') }}
                                    </dt>
                                    <dd class="mt-0.5 text-sm font-semibold text-gray-950 dark:text-white">{{ $workflow['remaining'] }}</dd>
                                </div>
                            </dl>
                        </div>

                        <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
                            <span>
                                {{ __('rekrutmen::livewire/recruitment-progress-report.table.applicants') }}
                                <span class="font-semibold text-gray-900 dark:text-white">{{ $workflow['total_applicants'] }}</span>
                            </span>
                            <span>
                                {{ __('rekrutmen::livewire/recruitment-progress-report.table.process') }}
                                <span class="font-semibold text-gray-900 dark:text-white">{{ $workflow['in_progress'] }}</span>
                            </span>
                            <span>
                                {{ __('rekrutmen::livewire/recruitment-progress-report.table.rejected') }}
                                <span class="font-semibold text-gray-900 dark:text-white">{{ $position['statistics']['rejected'] }}</span>
                            </span>
                        </div>
                    </div>

                    {{-- Expandable details --}}
                    <details class="border-t border-gray-100 dark:border-gray-800">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3 text-sm font-medium text-primary-600 hover:bg-gray-50 dark:text-primary-400 dark:hover:bg-gray-800/50">
                            <span>{{ __('rekrutmen::livewire/recruitment-progress-report.workflow.details') }}</span>
                            @if($activities->isNotEmpty())
                                <span class="text-xs font-normal text-gray-400 dark:text-gray-500">
                                    {{ __('rekrutmen::livewire/recruitment-progress-report.workflow.updated_badge', ['count' => $activities->count()]) }}
                                </span>
                            @endif
                        </summary>

                        <div class="space-y-4 px-4 pb-4">
                            @if($workflow['has_cycle_risk'])
                                <div class="rounded-md border border-amber-200 bg-amber-50 p-3 text-sm dark:border-amber-900/50 dark:bg-amber-900/20">
                                    <div class="font-semibold text-amber-900 dark:text-amber-200">
                                        {{ $cycleHealth['status_label'] }}
                                    </div>
                                    <p class="mt-1 text-amber-800 dark:text-amber-200/80">
                                        {{ $cycleHealth['description'] }}
                                    </p>
                                    @if(count($cycleHealth['issues'] ?? []) > 1)
                                        <ul class="mt-2 list-disc space-y-1 pl-4 text-xs text-amber-800 dark:text-amber-200/80">
                                            @foreach($cycleHealth['issues'] as $issue)
                                                <li>{{ $issue['label'] }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            @endif

                            @if($requestFulfillments->isNotEmpty())
                                <div>
                                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 pb-2 dark:border-gray-800">
                                        <h5 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                            {{ __('rekrutmen::livewire/recruitment-progress-report.workflow.mpp.heading') }}
                                        </h5>
                                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                            {{ __('rekrutmen::livewire/recruitment-progress-report.workflow.mpp.snapshot', ['date' => $requestFulfillments->first()['snapshot_label'] ?? '-']) }}
                                        </span>
                                    </div>

                                    <div class="mt-3 overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-100 text-sm dark:divide-gray-800">
                                            <thead class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                                <tr>
                                                    <th class="px-0 py-2 pr-4 text-left">{{ __('rekrutmen::livewire/recruitment-progress-report.workflow.mpp.request') }}</th>
                                                    <th class="px-4 py-2 text-left">{{ __('rekrutmen::livewire/recruitment-progress-report.workflow.mpp.request_date') }}</th>
                                                    <th class="px-4 py-2 text-left">{{ __('rekrutmen::livewire/recruitment-progress-report.workflow.mpp.estimated_join') }}</th>
                                                    <th class="px-4 py-2 text-left">{{ __('rekrutmen::livewire/recruitment-progress-report.workflow.mpp.age') }}</th>
                                                    <th class="px-4 py-2 text-center">{{ __('rekrutmen::livewire/recruitment-progress-report.workflow.mpp.fulfillment') }}</th>
                                                    <th class="px-4 py-2 text-left">{{ __('rekrutmen::livewire/recruitment-progress-report.workflow.mpp.status_heading') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                                @foreach($requestFulfillments as $requestFulfillment)
                                                    @php
                                                        $mppStatusClasses = match ($requestFulfillment['fulfillment_status'] ?? 'open') {
                                                            'fulfilled' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
                                                            'partial' => 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
                                                            'on_hold' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
                                                            'pending_approval' => 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
                                                            default => 'bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300',
                                                        };
                                                    @endphp
                                                    <tr @class([
                                                        'align-top',
                                                        'bg-rose-50/60 dark:bg-rose-950/20' => $requestFulfillment['estimate_missed'],
                                                    ])>
                                                        <td class="px-0 py-3 pr-4">
                                                            <div class="font-medium text-gray-950 dark:text-white">
                                                                #{{ $requestFulfillment['request_id'] }} / {{ $requestFulfillment['company'] }}
                                                            </div>
                                                            <div class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                                                {{ $requestFulfillment['position'] }} / {{ $requestFulfillment['location'] }}
                                                            </div>
                                                            <div class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                                                                {{ $requestFulfillment['is_counted_in_need']
                                                                    ? __('rekrutmen::livewire/recruitment-progress-report.workflow.mpp.counted')
                                                                    : __('rekrutmen::livewire/recruitment-progress-report.workflow.mpp.not_counted') }}
                                                            </div>
                                                        </td>
                                                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                                            {{ $requestFulfillment['request_date_label'] }}
                                                        </td>
                                                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                                            {{ $requestFulfillment['estimated_join_label'] }}
                                                        </td>
                                                        <td class="px-4 py-3 font-medium text-gray-950 dark:text-white">
                                                            {{ __('rekrutmen::livewire/recruitment-progress-report.workflow.mpp.age_days', ['count' => $requestFulfillment['age_days']]) }}
                                                        </td>
                                                        <td class="px-4 py-3 text-center font-semibold text-gray-950 dark:text-white">
                                                            {{ $requestFulfillment['needed'] }} / {{ $requestFulfillment['fulfilled'] }} / {{ $requestFulfillment['remaining'] }}
                                                        </td>
                                                        <td class="px-4 py-3">
                                                            <span class="inline-flex rounded-md px-2 py-0.5 text-xs font-semibold {{ $mppStatusClasses }}">
                                                                {{ $requestFulfillment['fulfillment_status_label'] }}
                                                            </span>
                                                            @if($requestFulfillment['estimate_missed'])
                                                                <div class="mt-2 text-xs font-medium text-rose-700 dark:text-rose-300">
                                                                    {{ __('rekrutmen::livewire/recruitment-progress-report.workflow.mpp.estimate_missed', ['count' => $requestFulfillment['remaining']]) }}
                                                                </div>
                                                            @endif
                                                            @if($requestFulfillment['is_fulfilled'])
                                                                <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                                                    {{ __('rekrutmen::livewire/recruitment-progress-report.workflow.mpp.fulfilled_at', [
                                                                        'date' => $requestFulfillment['fulfilled_at_label'],
                                                                        'candidate' => $requestFulfillment['closing_candidate'] ?? '-',
                                                                    ]) }}
                                                                </div>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endif

                            <div class="grid gap-5 lg:grid-cols-[minmax(12rem,0.8fr)_minmax(0,1.2fr)]">
                            <div>
                                <div class="flex items-center justify-between gap-3 border-b border-gray-100 pb-2 dark:border-gray-800">
                                    <h5 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                        {{ __('rekrutmen::livewire/recruitment-progress-report.labels.current_hired_candidates') }}
                                    </h5>
                                    <span class="text-sm font-semibold text-gray-950 dark:text-white">
                                        {{ $hiredCandidates->count() }} {{ __('rekrutmen::livewire/recruitment-progress-report.labels.people') }}
                                    </span>
                                </div>
                                @if($hiredCandidates->isNotEmpty())
                                    <div class="mt-2 space-y-2">
                                        @foreach($hiredCandidates as $candidate)
                                            <div class="rounded-md bg-gray-50 p-3 text-sm dark:bg-gray-800/60">
                                                <div class="font-medium text-gray-900 dark:text-white">{{ $candidate['full_name'] }}</div>
                                                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                    {{ __('rekrutmen::livewire/recruitment-progress-report.labels.hired_date', ['date' => $candidate['hired_at_label']]) }}
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="mt-3 rounded-md bg-gray-50 p-3 text-sm text-gray-500 dark:bg-gray-800/60 dark:text-gray-400">
                                        {{ __('rekrutmen::livewire/recruitment-progress-report.empty.no_hired_candidates') }}
                                    </p>
                                @endif
                            </div>

                            <div>
                                <div class="flex items-center justify-between gap-3 border-b border-gray-100 pb-2 dark:border-gray-800">
                                    <h5 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                        {{ __('rekrutmen::livewire/recruitment-progress-report.labels.period_activity_history') }}
                                    </h5>
                                    <span class="text-sm font-semibold text-gray-950 dark:text-white">
                                        {{ __('rekrutmen::livewire/recruitment-progress-report.workflow.updated_badge', ['count' => $activities->count()]) }}
                                    </span>
                                </div>
                                @if($activities->isNotEmpty())
                                    <div class="mt-3 space-y-3">
                                        @foreach($activities as $activity)
                                            <div class="rounded-md border border-gray-100 p-3 text-sm dark:border-gray-800">
                                                <div class="flex flex-wrap items-start justify-between gap-2">
                                                    <div class="font-medium text-gray-950 dark:text-white">{{ $activity['activity_label'] }}</div>
                                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $activity['activity_date']?->format('d M Y') ?? '-' }}</div>
                                                </div>
                                                <dl class="mt-3 grid gap-3 sm:grid-cols-3">
                                                    <div>
                                                        <dt class="text-xs text-gray-500 dark:text-gray-400">
                                                            {{ __('rekrutmen::livewire/recruitment-progress-report.labels.stage') }}
                                                        </dt>
                                                        <dd class="mt-0.5 font-medium text-gray-900 dark:text-white">
                                                            {{ $activity['to_stage']?->name ?? '-' }}
                                                        </dd>
                                                    </div>
                                                    <div>
                                                        <dt class="text-xs text-gray-500 dark:text-gray-400">
                                                            {{ __('rekrutmen::livewire/recruitment-progress-report.table.result') }}
                                                        </dt>
                                                        <dd class="mt-0.5 font-medium text-gray-900 dark:text-white">
                                                            {{ $activity['summary'] }}
                                                        </dd>
                                                    </div>
                                                    <div>
                                                        <dt class="text-xs text-gray-500 dark:text-gray-400">
                                                            PIC
                                                        </dt>
                                                        <dd class="mt-0.5 font-medium text-gray-900 dark:text-white">
                                                            {{ $activity['performer_label'] ?? $activity['performer']?->name ?? '-' }}
                                                        </dd>
                                                    </div>
                                                </dl>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="mt-3 rounded-md bg-gray-50 p-3 text-sm text-gray-500 dark:bg-gray-800/60 dark:text-gray-400">
                                        {{ __('rekrutmen::livewire/recruitment-progress-report.empty.no_activities') }}
                                    </p>
                                @endif
                            </div>
                            </div>
                        </div>
                    </details>
                </article>
            @empty
                <div class="rounded-lg border border-gray-200 bg-white p-8 text-center text-sm text-gray-500 shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400">
                    {{ __('rekrutmen::livewire/recruitment-progress-report.empty.no_focus_positions') }}
                </div>
            @endforelse
        </section>
    </div>
</div>
