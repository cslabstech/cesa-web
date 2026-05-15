<div class="min-h-screen bg-[#EFF6FF] px-4 py-8 font-sans antialiased sm:px-6 lg:px-8">
    <div class="mx-auto max-w-2xl">
        <div class="mb-4 rounded-lg border-t-[10px] cesa-primary-border bg-white shadow-sm">
            <div class="px-6 pt-5 pb-6">
                <h1 class="text-[32px] font-normal leading-tight text-gray-900">
                    {{ __('lead::views/public-lead-form.title') }}
                </h1>
                <p class="mt-4 text-sm text-gray-800">
                    {{ __('lead::views/public-lead-form.summary.title') }}
                </p>
                <div class="mt-6">
                    <a href="{{ url('lead') }}" class="text-sm text-blue-600 hover:text-blue-700 hover:underline">
                        {{ __('lead::views/public-lead-form.actions.submit_another') }}
                    </a>
                </div>
            </div>
        </div>

        <div class="space-y-4">
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <dl class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:gap-8">
                    <!-- Name -->
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium leading-6 text-gray-950">
                            {{ __('lead::filament/resources/lead.fields.name') }}
                        </dt>
                        <dd class="mt-1 text-sm leading-6 text-gray-900">
                            {{ $lead->name ?? '—' }}
                        </dd>
                    </div>

                    <!-- Phone -->
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium leading-6 text-gray-950">
                            {{ __('lead::filament/resources/lead.fields.phone') }}
                        </dt>
                        <dd class="mt-1 font-mono text-sm leading-6 text-gray-900">
                            {{ $lead->phone ?? '—' }}
                        </dd>
                    </div>

                    <!-- Address -->
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium leading-6 text-gray-950">
                            {{ __('lead::filament/resources/lead.fields.address') }}
                        </dt>
                        <dd class="mt-1 text-sm leading-6 text-gray-900 whitespace-pre-line">{{ $lead->address ?? '—' }}</dd>
                    </div>

                    <!-- Sales Person -->
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium leading-6 text-gray-950">
                            {{ __('lead::filament/resources/lead.fields.sales_person') }}
                        </dt>
                        <dd class="mt-1 text-sm leading-6 text-gray-900">
                            {{ $lead->sales_person ?? '—' }}
                        </dd>
                    </div>

                    <!-- Store Team Position -->
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium leading-6 text-gray-950">
                            {{ __('lead::filament/resources/lead.fields.store_team_position') }}
                        </dt>
                        <dd class="mt-1 text-sm leading-6 text-gray-900">
                            {{ $lead->store_team_position?->label() ?? '—' }}
                        </dd>
                    </div>

                    <!-- Store Branch -->
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium leading-6 text-gray-950">
                            {{ __('lead::filament/resources/lead.fields.store_branch') }}
                        </dt>
                        <dd class="mt-1 text-sm leading-6 text-gray-900">
                            {{ $lead->store_branch ?? '—' }}
                        </dd>
                    </div>

                    <!-- Phone Transaction Range -->
                    @if ($lead->phone_transaction_range)
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium leading-6 text-gray-950">
                                {{ __('lead::filament/resources/lead.fields.phone_transaction_range') }}
                            </dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-900">
                                {{ $lead->phone_transaction_range->label() }}
                            </dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>
    </div>
</div>
