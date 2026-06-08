<div class="min-h-screen bg-[#EFF6FF] px-4 py-8 font-sans antialiased sm:px-6 lg:px-8">
    <div class="mx-auto max-w-4xl">
        <div class="mb-6 rounded-lg border-t-[10px] cesa-primary-border bg-white shadow-sm">
            <div class="px-6 pb-6 pt-5">
                <h1 class="text-[32px] font-normal leading-tight text-gray-900">
                    {{ $heading }}
                </h1>
                <p class="mt-2 text-sm text-gray-600">
                    {{ $description }}
                </p>
            </div>
        </div>

        @if ($categories->isNotEmpty())
            <div class="grid gap-4 sm:grid-cols-2">
                @foreach ($categories as $category)
                    <a
                        href="{{ route('form-transfer.public.dynamic-index', ['publicIndexSlug' => $category->slug]) }}"
                        class="group rounded-lg border border-gray-200 bg-white p-6 shadow-sm transition hover:border-primary-600 hover:shadow-md"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    {{ '/form/'.$category->slug }}
                                </p>
                                <h2 class="mt-2 text-lg font-semibold text-gray-900 group-hover:text-primary-600">
                                    {{ $category->name }}
                                </h2>
                                <p class="mt-2 text-sm text-gray-600">
                                    {{ filled($category->description) ? $category->description : $defaultDescription }}
                                </p>
                            </div>
                            <x-filament::icon
                                icon="heroicon-m-arrow-right"
                                class="h-5 w-5 text-gray-400 group-hover:text-primary-600"
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
