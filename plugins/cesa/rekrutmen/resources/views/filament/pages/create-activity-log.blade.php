<x-filament-panels::page>
    <form wire:submit="create" class="space-y-6">
        {{ $this->form }}

        <div class="flex justify-end">
            <x-filament::button type="submit">
                {{ __('rekrutmen::filament/resources/activity-log.form.actions.create') }}
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
