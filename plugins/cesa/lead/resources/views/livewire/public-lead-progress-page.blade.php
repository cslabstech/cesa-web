<div class="min-h-screen bg-[#EFF6FF] px-4 py-8 font-sans antialiased sm:px-6 lg:px-8">
    <div class="mx-auto max-w-2xl">
        <div class="mb-4 rounded-lg border-t-[10px] cesa-primary-border bg-white shadow-sm">
            <div class="px-6 pt-5 pb-6">
                <h1 class="text-[32px] font-normal leading-tight text-gray-900">
                    {{ __('lead::views/public-lead-form.summary.title') }}
                </h1>
                <p class="mt-2 text-sm text-gray-600">
                    {{ __('lead::views/public-lead-form.summary.submitted_by') }}
                    <span class="font-medium text-gray-900">{{ $lead->name }}</span>
                </p>
            </div>
            <div class="border-t border-gray-200 px-6 py-3">
                <div class="flex flex-wrap items-center gap-2 text-xs text-gray-600">
                    <span>{{ __('lead::views/public-lead-form.summary.current_status') }}</span>
                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-medium text-emerald-700">
                        {{ __('lead::views/public-lead-form.summary.status_submitted') }}
                    </span>
                    <span class="text-gray-300">|</span>
                    <span class="font-mono text-gray-400">{{ $lead->public_response_id ?? '-' }}</span>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="cesa-primary-bg px-6 py-4 text-white">
                <h2 class="text-lg font-medium">{{ __('lead::views/public-lead-form.summary.submission_summary') }}</h2>
            </div>

            <div class="border-t border-blue-100 px-6 py-5">
                <div class="space-y-5">
                    <div class="space-y-1">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                            {{ __('lead::views/public-lead-form.summary.fields.response_id') }}
                        </p>
                        <div class="break-words font-mono text-base font-medium text-gray-900">
                            {{ $lead->public_response_id ?? '—' }}
                        </div>
                    </div>

                    <div class="space-y-1">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                            {{ __('lead::views/public-lead-form.summary.fields.form') }}
                        </p>
                        <div class="break-words text-base font-medium text-gray-900">
                            {{ __('lead::views/public-lead-form.title') }}
                        </div>
                    </div>

                    <div class="space-y-1">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                            {{ __('lead::views/public-lead-form.summary.fields.submitted_at') }}
                        </p>
                        <div class="break-words text-base font-medium text-gray-900">
                            {{ $lead->created_at?->translatedFormat('d F Y H:i') ?? '—' }}
                        </div>
                    </div>

                    <div class="space-y-1">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                            {{ __('lead::filament/resources/lead.fields.name') }}
                        </p>
                        <div class="break-words text-base font-medium text-gray-900">
                            {{ $lead->name ?? '—' }}
                        </div>
                    </div>

                    <div class="space-y-1">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                            {{ __('lead::filament/resources/lead.fields.phone') }}
                        </p>
                        <div class="break-words font-mono text-base font-medium text-gray-900">
                            {{ $lead->phone ?? '—' }}
                        </div>
                    </div>

                    <div class="space-y-1">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                            {{ __('lead::filament/resources/lead.fields.sales_person') }}
                        </p>
                        <div class="break-words text-base font-medium text-gray-900">
                            {{ $lead->sales_person ?? '—' }}
                        </div>
                    </div>

                    <div class="space-y-1">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                            {{ __('lead::filament/resources/lead.fields.address') }}
                        </p>
                        <div class="break-words text-base font-medium text-gray-900">
                            {{ $lead->address ?? '—' }}
                        </div>
                    </div>

                    <div class="space-y-1">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                            {{ __('lead::filament/resources/lead.fields.store_branch') }}
                        </p>
                        <div class="break-words text-base font-medium text-gray-900">
                            {{ $lead->store_branch ?? '—' }}
                        </div>
                    </div>

                    <div class="space-y-1">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                            {{ __('lead::filament/resources/lead.fields.store_team_position') }}
                        </p>
                        <div class="break-words text-base font-medium text-gray-900">
                            {{ $lead->store_team_position?->label() ?? '—' }}
                        </div>
                    </div>

                    @if ($lead->phone_transaction_range)
                        <div class="space-y-1">
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                                {{ __('lead::filament/resources/lead.fields.phone_transaction_range') }}
                            </p>
                            <div class="break-words text-base font-medium text-gray-900">
                                {{ $lead->phone_transaction_range->label() }}
                            </div>
                        </div>
                    @endif
                </div>

                <div class="mt-6">
                    <a href="{{ url('lead') }}" class="text-sm font-medium text-blue-600 hover:text-blue-500 hover:underline">
                        {{ __('lead::views/public-lead-form.actions.submit_another') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
