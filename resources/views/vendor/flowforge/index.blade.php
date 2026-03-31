@php use Filament\Support\Facades\FilamentAsset; @endphp
@props(['columns', 'config'])

<div
    class="relative flex h-full w-full flex-col"
    x-load
    x-load-src="{{ FilamentAsset::getAlpineComponentSrc('flowforge', package: 'relaticle/flowforge') }}"
    x-data="flowforge({
        state: {
            columns: @js($columns),
            titleField: '{{ $config['recordTitleAttribute'] }}',
            columnField: '{{ $config['columnIdentifierAttribute'] }}',
            cardLabel: '{{ $config['cardLabel'] }}',
            pluralCardLabel: '{{ $config['pluralCardLabel'] }}',
        }
    })"
>
    @unless($config['headerToolbar'] ?? false)
        @include('flowforge::components.filters')
    @endunless

    <div class="min-h-0 flex-1 overflow-hidden pt-2">
        <div class="flex h-full flex-row gap-4 overflow-x-auto overflow-y-hidden px-1 pb-4">
            @foreach($columns as $columnId => $column)
                <x-flowforge::column
                    :columnId="$columnId"
                    :column="$column"
                    :config="$config"
                    wire:key="column-{{ $columnId }}"
                />
            @endforeach
        </div>
    </div>

    <x-filament-actions::modals/>
</div>
