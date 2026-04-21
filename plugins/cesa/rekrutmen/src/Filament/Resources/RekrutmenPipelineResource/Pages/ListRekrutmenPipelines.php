<?php

namespace Cesa\Rekrutmen\Filament\Resources\RekrutmenPipelineResource\Pages;

use Cesa\Rekrutmen\Filament\Resources\RekrutmenPipelineResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListRekrutmenPipelines extends ListRecords
{
    protected static string $resource = RekrutmenPipelineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->icon('heroicon-o-plus-circle')->slideOver()->modalWidth('md'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')
                ->badge(static::getResource()::getModel()::query()->withoutTrashed()->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->withoutTrashed()),
            'archived' => Tab::make('Archived')
                ->badge(static::getResource()::getModel()::query()->onlyTrashed()->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->onlyTrashed()),
        ];
    }
}
