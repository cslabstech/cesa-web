<?php

namespace Cesa\FormTransfer\Filament\Clusters\Configurations\Resources\PublicCategoryResource\Pages;

use Cesa\FormTransfer\Filament\Clusters\Configurations\Resources\PublicCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPublicCategories extends ListRecords
{
    protected static string $resource = PublicCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->icon('heroicon-o-plus-circle')
                ->slideOver()
                ->modalWidth('md'),
        ];
    }
}
