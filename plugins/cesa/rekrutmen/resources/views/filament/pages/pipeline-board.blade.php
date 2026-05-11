<x-filament-panels::page>
    <style>
        /* Prevent vertical scrolling on the board page */
        .fi-main-ctn {
            overflow-y: hidden !important;
        }
    </style>
    <div class="h-[calc(100vh-11rem)] relative">
        {{ $this->board }}
    </div>
</x-filament-panels::page>
