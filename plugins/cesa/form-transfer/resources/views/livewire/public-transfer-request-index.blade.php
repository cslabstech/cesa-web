<div class="min-h-screen {{ $affiliateMode ? 'bg-[#F0EBF8]' : 'bg-[#EFF6FF]' }} px-4 py-8 font-sans antialiased sm:px-6 lg:px-8">
    <div class="mx-auto max-w-4xl">
        <div class="mb-6 rounded-lg border-t-[10px] {{ $affiliateMode ? 'border-[#673AB7]' : 'cesa-primary-border' }} bg-white shadow-sm">
            <div class="px-6 pb-6 pt-5">
                <h1 class="text-[32px] font-normal leading-tight text-gray-900">
                    {{ $heading }}
                </h1>
                <p class="mt-2 text-sm text-gray-600">
                    {{ $description }}
                </p>
            </div>
        </div>

        @if ($formTransfers->isNotEmpty())
            <div class="grid gap-4 sm:grid-cols-2">
                @foreach ($formTransfers as $formTransfer)
                    <a
                        href="{{ $formTransfer->public_destination_url }}"
                        @if ($formTransfer->usesExternalPublicEntry()) target="_blank" rel="noopener noreferrer" @endif
                        class="group rounded-lg border border-gray-200 bg-white p-6 shadow-sm transition {{ $affiliateMode ? 'hover:border-[#673AB7]' : 'hover:border-primary-600' }} hover:shadow-md"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    {{ $formTransfer->public_badge_label ?: ($formTransfer->usesExternalPublicEntry() ? 'Google Form' : 'Form Transfer') }}
                                </p>
                                <h2 class="mt-2 text-lg font-semibold text-gray-900 {{ $affiliateMode ? 'group-hover:text-[#673AB7]' : 'group-hover:text-primary-600' }}">
                                    {{ $formTransfer->name }}
                                </h2>
                                <p class="mt-2 text-sm text-gray-600">
                                    {{ filled($formTransfer->description) ? $formTransfer->description : $defaultDescription }}
                                </p>
                            </div>
                            <x-filament::icon
                                icon="heroicon-m-arrow-right"
                                class="h-5 w-5 text-gray-400 {{ $affiliateMode ? 'group-hover:text-[#673AB7]' : 'group-hover:text-primary-600' }}"
                            />
                        </div>
                    </a>
                @endforeach
            </div>
        @else
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <p class="text-sm text-gray-600">
                    {{ $emptyState }}
                </p>
            </div>
        @endif
    </div>
</div>
