<div class="min-h-screen bg-[#EFF6FF] py-8 px-4 sm:px-6 lg:px-8 font-sans antialiased">
    <div class="mx-auto max-w-2xl">
        @if (blank($requestManPower))
            <form wire:submit="lookup">
                <div class="mb-4 rounded-lg border-t-[10px] cesa-primary-border bg-white shadow-sm">
                    <div class="px-6 pt-5 pb-6">
                        <h1 class="text-[32px] font-normal text-gray-900 leading-tight">
                            {{ __('rekrutmen::livewire/public-request-man-power-progress-page.lookup.heading') }}
                        </h1>
                        <p class="mt-2 text-sm text-gray-600">
                            {{ __('rekrutmen::livewire/public-request-man-power-progress-page.lookup.description') }}
                        </p>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                        {{ $this->form }}
                    </div>
                </div>

                <div class="mt-4 flex items-center justify-between gap-3 px-1">
                    <a
                        href="{{ route('rekrutmen.public.request-man-power.form') }}"
                        class="text-sm font-medium text-gray-600 hover:text-gray-900 hover:underline"
                    >
                        &larr; {{ __('rekrutmen::livewire/public-request-man-power-progress-page.page_title') }}
                    </a>

                    <x-filament::button
                        type="submit"
                        wire:loading.attr="disabled"
                        wire:target="lookup"
                        class="!bg-primary-700 text-white shadow-sm hover:!bg-primary-800 focus-visible:!ring-primary-300"
                    >
                        {{ __('rekrutmen::livewire/public-request-man-power-progress-page.lookup.submit') }}
                    </x-filament::button>
                </div>

                @if ($lookupSearched)
                    <div class="mt-8 space-y-3">
                        <h2 class="px-1 text-sm font-semibold uppercase tracking-wider text-gray-600">
                            {{ __('rekrutmen::livewire/public-request-man-power-progress-page.lookup.results_heading') }}
                        </h2>

                        @forelse ($lookupResults as $result)
                            <a
                                href="{{ $result['url'] }}"
                                class="group block rounded-lg border border-gray-200 bg-white p-5 shadow-sm transition hover:border-primary-600 hover:shadow-md"
                            >
                                <div class="flex items-start justify-between gap-4">
                                    <div class="min-w-0">
                                        <p class="font-mono text-xs font-semibold uppercase tracking-wider text-gray-500">
                                            {{ $result['status_response_id'] }}
                                        </p>
                                        <h3 class="mt-1 text-base font-semibold text-gray-900 group-hover:text-primary-600">
                                            {{ $result['posisi_dibutuhkan'] }}
                                        </h3>
                                        <p class="mt-1 text-sm text-gray-600">
                                            {{ $result['nama_pengaju'] }}
                                        </p>
                                    </div>

                                    <span class="{{ $result['status_classes'] }} shrink-0 inline-flex items-center rounded-full px-3 py-1 text-xs font-medium">
                                        {{ $result['status_label'] }}
                                    </span>
                                </div>

                                <div class="mt-4 grid gap-3 border-t border-gray-100 pt-4 text-sm text-gray-600 sm:grid-cols-3">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                                            {{ __('rekrutmen::livewire/public-request-man-power-progress-page.lookup.submitted_at') }}
                                        </p>
                                        <p class="mt-1 text-gray-900">{{ $result['tanggal_pengajuan'] }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                                            {{ __('rekrutmen::livewire/public-request-man-power-progress-page.lookup.needed_count') }}
                                        </p>
                                        <p class="mt-1 text-gray-900">{{ $result['jumlah_karyawan'] }}</p>
                                    </div>
                                    <div class="flex items-end sm:justify-end">
                                        <span class="inline-flex items-center gap-1.5 text-sm font-medium text-primary-600">
                                            {{ __('rekrutmen::livewire/public-request-man-power-progress-page.lookup.view_progress') }}
                                            <x-filament::icon icon="heroicon-m-arrow-right" class="h-4 w-4" />
                                        </span>
                                    </div>
                                </div>
                            </a>
                        @empty
                            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                                <p class="text-sm text-gray-600">
                                    {{ __('rekrutmen::livewire/public-request-man-power-progress-page.lookup.empty_state') }}
                                </p>
                            </div>
                        @endforelse
                    </div>
                @endif
            </form>
        @else
            @php
                $status = $requestManPower->status;
                $statusLabel = $status?->getLabel() ?? '-';
                $statusClasses = match ($status?->value) {
                    'approved' => 'bg-green-50 text-green-700 ring-green-600/20',
                    'rejected' => 'bg-red-50 text-red-700 ring-red-600/10',
                    'hold' => 'bg-gray-50 text-gray-600 ring-gray-500/10',
                    default => 'bg-yellow-50 text-yellow-800 ring-yellow-600/20',
                };
            @endphp

        <div class="mb-4 rounded-lg border-t-[10px] cesa-primary-border bg-white shadow-sm">
            <div class="px-6 pt-5 pb-6">
                <h1 class="text-[32px] font-normal text-gray-900 leading-tight">
                    {{ __('rekrutmen::livewire/public-request-man-power-progress-page.page_title') }}
                </h1>
                <p class="mt-2 text-sm text-gray-600">
                    {{ __('rekrutmen::livewire/public-request-man-power-progress-page.submitted_by') }}
                    <span class="font-medium text-gray-900">{{ $requestManPower->nama_pengaju }}</span>
                    @if (filled($requestManPower->division_name))
                        ({{ $requestManPower->division_name }})
                    @endif
                </p>
            </div>
            <div class="border-t border-gray-200 px-6 py-3">
                <div class="flex flex-wrap items-center gap-2 text-xs text-gray-600">
                    <span>{{ __('rekrutmen::livewire/public-request-man-power-progress-page.current_status') }}</span>
                    <span class="{{ $statusClasses }} inline-flex items-center rounded-full px-3 py-1 text-xs font-medium ring-1 ring-inset">
                        {{ $statusLabel }}
                    </span>
                </div>
            </div>
        </div>

        @if ($status?->value === 'hold' && filled($requestManPower->hold_reason))
            <div class="mb-4 rounded-lg border border-gray-200 bg-white px-6 py-5 shadow-sm">
                <p class="text-sm font-semibold text-gray-900">
                    {{ __('rekrutmen::livewire/public-request-man-power-progress-page.hold_notice.title') }}
                </p>
                <p class="mt-2 whitespace-pre-line text-sm leading-6 text-gray-700">{{ $requestManPower->hold_reason }}</p>
                @if ($requestManPower->held_at)
                    <p class="mt-3 text-xs text-gray-500">
                        {{ __('rekrutmen::livewire/public-request-man-power-progress-page.hold_notice.held_at', [
                            'date' => $requestManPower->held_at->translatedFormat('d F Y H:i'),
                        ]) }}
                    </p>
                @endif
            </div>
        @endif

        <div class="space-y-4">
            <div class="rounded-lg bg-white p-6 shadow-sm border border-gray-200">
                <div class="-mx-6 -mt-6 mb-6 rounded-t-lg cesa-primary-bg px-6 py-3 text-white">
                    <h2 class="text-lg font-medium">{{ __('rekrutmen::livewire/public-request-man-power-progress-page.submission_summary') }}</h2>
                </div>

                <div class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                    <div class="space-y-1">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                            {{ __('rekrutmen::livewire/public-request-man-power-progress-page.fields.tanggal_pengajuan') }}
                        </p>
                        <div class="break-words text-base font-medium text-gray-900">{{ $requestManPower->tanggal_pengajuan?->translatedFormat('d F Y') ?? '-' }}</div>
                    </div>

                    <div class="space-y-1">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                            {{ __('rekrutmen::livewire/public-request-man-power-progress-page.fields.posisi_dibutuhkan') }}
                        </p>
                        <div class="break-words text-base font-medium text-gray-900">{{ $requestManPower->posisi_dibutuhkan ?? '-' }}</div>
                    </div>

                    <div class="space-y-1">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                            {{ __('rekrutmen::livewire/public-request-man-power-progress-page.fields.status_kebutuhan') }}
                        </p>
                        <div class="break-words text-base font-medium text-gray-900">{{ $requestManPower->status_kebutuhan?->getLabel() ?? '-' }}</div>
                    </div>

                    <div class="space-y-1">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                            {{ __('rekrutmen::livewire/public-request-man-power-progress-page.fields.level_pekerjaan') }}
                        </p>
                        <div class="break-words text-base font-medium text-gray-900">{{ $requestManPower->level_pekerjaan ?? '-' }}</div>
                    </div>

                    <div class="space-y-1">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                            {{ __('rekrutmen::livewire/public-request-man-power-progress-page.fields.jumlah_karyawan_dibutuhkan') }}
                        </p>
                        <div class="break-words text-base font-medium text-gray-900">{{ $requestManPower->jumlah_karyawan_dibutuhkan ?? '-' }}</div>
                    </div>

                    <div class="space-y-1">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                            {{ __('rekrutmen::livewire/public-request-man-power-progress-page.fields.lokasi_penempatan') }}
                        </p>
                        <div class="break-words text-base font-medium text-gray-900">{{ $requestManPower->lokasi_penempatan ?? '-' }}</div>
                    </div>

                    <div class="space-y-1">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                            {{ __('rekrutmen::livewire/public-request-man-power-progress-page.fields.estimasi_tanggal_join') }}
                        </p>
                        <div class="break-words text-base font-medium text-gray-900">{{ $requestManPower->estimasi_tanggal_join?->translatedFormat('d F Y') ?? '-' }}</div>
                    </div>

                    @if (filled($requestManPower->nama_karyawan_replacement))
                        <div class="space-y-1">
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                                {{ __('rekrutmen::livewire/public-request-man-power-progress-page.fields.nama_karyawan_replacement') }}
                            </p>
                            <div class="break-words text-base font-medium text-gray-900">{{ $requestManPower->nama_karyawan_replacement }}</div>
                        </div>
                    @endif

                    <div class="space-y-1 sm:col-span-2">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                            {{ __('rekrutmen::livewire/public-request-man-power-progress-page.fields.requirements_kualifikasi') }}
                        </p>
                        <div class="prose prose-sm max-w-none break-words text-base font-medium text-gray-900">{!! $requestManPower->requirements_kualifikasi ?? '-' !!}</div>
                    </div>

                    <div class="space-y-1 sm:col-span-2">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                            {{ __('rekrutmen::livewire/public-request-man-power-progress-page.fields.job_description') }}
                        </p>
                        <div class="prose prose-sm max-w-none break-words text-base font-medium text-gray-900">{!! $requestManPower->job_description ?? '-' !!}</div>
                    </div>

                    @if (filled($requestManPower->keterangan))
                        <div class="space-y-1 sm:col-span-2">
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                                {{ __('rekrutmen::livewire/public-request-man-power-progress-page.fields.keterangan') }}
                            </p>
                            <div class="whitespace-pre-line break-words text-base font-medium text-gray-900">{{ $requestManPower->keterangan }}</div>
                        </div>
                    @endif
                </div>
            </div>

            @if ($requestManPower->statusHistories->isNotEmpty())
                <div class="rounded-lg bg-white p-6 shadow-sm border border-gray-200">
                    <div class="-mx-6 -mt-6 mb-6 rounded-t-lg cesa-primary-bg px-6 py-3 text-white">
                        <h2 class="text-lg font-medium">{{ __('rekrutmen::livewire/public-request-man-power-progress-page.status_history_heading') }}</h2>
                    </div>
                    <ol class="space-y-3">
                        @foreach ($requestManPower->statusHistories as $history)
                            @php
                                $historyStatus = $history->to_status;
                            @endphp
                            <li class="rounded-lg border border-gray-200 p-4">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900">
                                            {{ $historyStatus?->getLabel() ?? '-' }}
                                        </p>
                                        <p class="mt-1 text-xs text-gray-500">
                                            {{ $history->created_at?->translatedFormat('d M Y H:i') ?? '-' }}
                                        </p>
                                    </div>
                                </div>
                                @if (filled($history->reason))
                                    <p class="mt-3 text-sm leading-relaxed text-gray-700">
                                        {{ $history->reason }}
                                    </p>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </div>
            @endif

            @if ($requestManPower->approvals->isNotEmpty())
                <div class="rounded-lg bg-white p-6 shadow-sm border border-gray-200">
                    <div class="-mx-6 -mt-6 mb-6 rounded-t-lg cesa-primary-bg px-6 py-3 text-white">
                        <h2 class="text-lg font-medium">{{ __('rekrutmen::livewire/public-request-man-power-progress-page.approval_flow_heading') }}</h2>
                    </div>
                    <ol class="space-y-4">
                        @foreach ($requestManPower->approvals->sortBy('step_order') as $approval)
                            @php
                                $approvalStatusValue = strtolower($approval->status?->value ?? 'pending');
                                $approvalStatusColor = match ($approvalStatusValue) {
                                    'approved' => 'success',
                                    'rejected' => 'danger',
                                    'pending' => 'warning',
                                    default => 'gray',
                                };
                            @endphp
                            <li class="rounded-lg border border-gray-200 p-4">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900">
                                            {{ __('rekrutmen::livewire/public-request-man-power-progress-page.step_label', ['step' => $approval->step_order]) }}
                                            - {{ $approval->approver_name }}
                                        </p>
                                        <p class="text-sm text-gray-600">
                                            {{ $approval->approver_title ?: '-' }}
                                        </p>
                                    </div>
                                    <div class="flex flex-col items-end">
                                        @php
                                            $badgeColorClass = match($approvalStatusColor ?? 'gray') {
                                                'success' => 'bg-green-50 text-green-700 ring-green-600/20',
                                                'danger' => 'bg-red-50 text-red-700 ring-red-600/10',
                                                'warning' => 'bg-yellow-50 text-yellow-800 ring-yellow-600/20',
                                                'primary' => 'bg-blue-50 text-blue-700 ring-blue-700/10',
                                                default => 'bg-gray-50 text-gray-600 ring-gray-500/10',
                                            };
                                        @endphp
                                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium ring-1 ring-inset {{ $badgeColorClass }}">
                                            {{ $approval->status?->getLabel() ?? '-' }}
                                        </span>
                                        @if (!empty($approval->acted_at))
                                            <time class="mt-1 text-[11px] text-gray-400">
                                                {{ $approval->acted_at?->translatedFormat('d M Y H:i') }}
                                            </time>
                                        @endif
                                    </div>
                                </div>

                                @if (filled($approval->notes))
                                    <div class="mt-3 rounded-xl border border-gray-200 bg-gray-50 p-4">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                            {{ __('form-transfer::public.form.actions.comments') }}
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
            @endif
        </div>
        @endif
    </div>
</div>
