<div class="min-h-screen bg-[#EFF6FF] py-8 px-4 sm:px-6 lg:px-8 font-sans antialiased">
    <div class="mx-auto max-w-2xl">
        @php
            $status = $requestManPower->status;
            $statusLabel = $status?->getLabel() ?? '-';
            $statusClasses = match ($status?->value) {
                'approved' => 'bg-emerald-100 text-emerald-700',
                'rejected' => 'bg-red-100 text-red-700',
                default => 'bg-amber-100 text-amber-700',
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
                    <span class="{{ $statusClasses }} inline-flex items-center rounded-full px-3 py-1 text-xs font-medium">
                        {{ $statusLabel }}
                    </span>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="cesa-primary-bg px-6 py-4 text-white">
                <h2 class="text-lg font-medium">{{ __('rekrutmen::livewire/public-request-man-power-progress-page.submission_summary') }}</h2>
            </div>

            <div class="border-t border-blue-100 px-6 py-5">
                <div class="grid grid-cols-1 gap-y-6">
                    <div class="space-y-1">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                            {{ __('rekrutmen::livewire/public-request-man-power-progress-page.fields.tanggal_pengajuan') }}
                        </p>
                        <div class="text-base font-medium text-gray-900 break-words">{{ $requestManPower->tanggal_pengajuan?->translatedFormat('d F Y') ?? '-' }}</div>
                    </div>

                    <div class="space-y-1">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                            {{ __('rekrutmen::livewire/public-request-man-power-progress-page.fields.posisi_dibutuhkan') }}
                        </p>
                        <div class="text-base font-medium text-gray-900 break-words">{{ $requestManPower->posisi_dibutuhkan ?? '-' }}</div>
                    </div>

                    <div class="space-y-1">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                            {{ __('rekrutmen::livewire/public-request-man-power-progress-page.fields.status_kebutuhan') }}
                        </p>
                        <div class="text-base font-medium text-gray-900 break-words">{{ $requestManPower->status_kebutuhan?->getLabel() ?? '-' }}</div>
                    </div>

                    <div class="space-y-1">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                            {{ __('rekrutmen::livewire/public-request-man-power-progress-page.fields.level_pekerjaan') }}
                        </p>
                        <div class="text-base font-medium text-gray-900 break-words">{{ $requestManPower->level_pekerjaan ?? '-' }}</div>
                    </div>

                    <div class="space-y-1">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                            {{ __('rekrutmen::livewire/public-request-man-power-progress-page.fields.jumlah_karyawan_dibutuhkan') }}
                        </p>
                        <div class="text-base font-medium text-gray-900 break-words">{{ $requestManPower->jumlah_karyawan_dibutuhkan ?? '-' }}</div>
                    </div>

                    <div class="space-y-1">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                            {{ __('rekrutmen::livewire/public-request-man-power-progress-page.fields.lokasi_penempatan') }}
                        </p>
                        <div class="text-base font-medium text-gray-900 break-words">{{ $requestManPower->lokasi_penempatan ?? '-' }}</div>
                    </div>

                    <div class="space-y-1">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                            {{ __('rekrutmen::livewire/public-request-man-power-progress-page.fields.estimasi_tanggal_join') }}
                        </p>
                        <div class="text-base font-medium text-gray-900 break-words">{{ $requestManPower->estimasi_tanggal_join?->translatedFormat('d F Y') ?? '-' }}</div>
                    </div>

                    @if (filled($requestManPower->nama_karyawan_replacement))
                        <div class="space-y-1">
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                                {{ __('rekrutmen::livewire/public-request-man-power-progress-page.fields.nama_karyawan_replacement') }}
                            </p>
                            <div class="text-base font-medium text-gray-900 break-words">{{ $requestManPower->nama_karyawan_replacement }}</div>
                        </div>
                    @endif

                    <div class="space-y-1">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                            {{ __('rekrutmen::livewire/public-request-man-power-progress-page.fields.requirements_kualifikasi') }}
                        </p>
                        <div class="text-base font-medium text-gray-900 break-words prose prose-sm max-w-none">{!! $requestManPower->requirements_kualifikasi ?? '-' !!}</div>
                    </div>

                    <div class="space-y-1">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                            {{ __('rekrutmen::livewire/public-request-man-power-progress-page.fields.job_description') }}
                        </p>
                        <div class="text-base font-medium text-gray-900 break-words prose prose-sm max-w-none">{!! $requestManPower->job_description ?? '-' !!}</div>
                    </div>

                    @if (filled($requestManPower->keterangan))
                        <div class="space-y-1">
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                                {{ __('rekrutmen::livewire/public-request-man-power-progress-page.fields.keterangan') }}
                            </p>
                            <div class="text-base font-medium text-gray-900 break-words whitespace-pre-line">{{ $requestManPower->keterangan }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        @if ($requestManPower->approvals->isNotEmpty())
            <div class="mt-4 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h2 class="text-lg font-medium text-gray-900">
                        {{ __('rekrutmen::livewire/public-request-man-power-progress-page.approval_flow_heading') }}
                    </h2>
                </div>

                <div class="space-y-3 px-6 py-5">
                    @foreach ($requestManPower->approvals->sortBy('step_order') as $approval)
                        <div class="rounded-lg border border-gray-200 px-4 py-3">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">
                                        {{ __('rekrutmen::livewire/public-request-man-power-progress-page.step_label', ['step' => $approval->step_order]) }}
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
        @endif
    </div>
</div>
