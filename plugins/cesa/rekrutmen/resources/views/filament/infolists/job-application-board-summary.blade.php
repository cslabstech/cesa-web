@php
    $state = $getState() ?? [];
@endphp

<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    <div class="rekrutmen-board-card-body">
        <div class="rekrutmen-board-card-profile">
            @if (filled($state['avatar_url'] ?? null))
                <img
                    src="{{ $state['avatar_url'] }}"
                    alt="Foto kandidat"
                    class="rekrutmen-board-card-avatar"
                >
            @else
                <div class="rekrutmen-board-card-avatar rekrutmen-board-card-avatar-fallback">
                    <span>{{ $state['avatar_initials'] ?? 'NA' }}</span>
                </div>
            @endif
        </div>

        <div class="rekrutmen-board-card-meta">
            @if (filled($state['gender'] ?? null))
                <x-filament::badge
                    color="gray"
                    icon="heroicon-m-user"
                >
                    {{ $state['gender'] }}
                </x-filament::badge>
            @endif

            @if (filled($state['source'] ?? null))
                <x-filament::badge
                    color="gray"
                    icon="heroicon-m-globe-alt"
                >
                    {{ $state['source'] }}
                </x-filament::badge>
            @endif

            @if (filled($state['age'] ?? null))
                <x-filament::badge
                    color="gray"
                    icon="heroicon-m-calendar-days"
                >
                    {{ $state['age'] }}
                </x-filament::badge>
            @endif
        </div>

        <div class="rekrutmen-board-card-status">
            <x-filament::badge
                :color="$state['status_color'] ?? 'gray'"
                :icon="$state['status_icon'] ?? 'heroicon-m-clock'"
                size="md"
            >
                {{ $state['status'] ?? '-' }}
            </x-filament::badge>
        </div>

        @if (filled($state['last_updated'] ?? null))
            <p class="rekrutmen-board-card-updated">{{ $state['last_updated'] }}</p>
        @endif

        @if (filled($state['status_context'] ?? null))
            <p class="rekrutmen-board-card-context">{{ $state['status_context'] }}</p>
        @endif
    </div>
</x-dynamic-component>
