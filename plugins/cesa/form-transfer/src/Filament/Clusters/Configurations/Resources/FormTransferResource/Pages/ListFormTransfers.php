<?php

namespace Cesa\FormTransfer\Filament\Clusters\Configurations\Resources\FormTransferResource\Pages;

use Cesa\FormTransfer\Filament\Clusters\Configurations\Resources\FormTransferResource;
use Cesa\FormTransfer\Models\FormTransfer;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListFormTransfers extends ListRecords
{
    protected static string $resource = FormTransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->icon('heroicon-o-plus-circle')
                ->slideOver()
                ->modalWidth('md'),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return FormTransfer::PUBLIC_ENTRY_TYPE_INTERNAL;
    }

    public function getTabs(): array
    {
        return [
            FormTransfer::PUBLIC_ENTRY_TYPE_INTERNAL => Tab::make(
                __('form-transfer::filament/clusters/configurations/resources/form-transfer.options.public_entry_type.internal')
            )
                ->badge(
                    FormTransfer::query()
                        ->where('public_entry_type', FormTransfer::PUBLIC_ENTRY_TYPE_INTERNAL)
                        ->count()
                )
                ->modifyQueryUsing(
                    fn (Builder $query): Builder => $query->where('public_entry_type', FormTransfer::PUBLIC_ENTRY_TYPE_INTERNAL)
                ),
            FormTransfer::PUBLIC_ENTRY_TYPE_EXTERNAL => Tab::make(
                __('form-transfer::filament/clusters/configurations/resources/form-transfer.options.public_entry_type.external')
            )
                ->badge(
                    FormTransfer::query()
                        ->where('public_entry_type', FormTransfer::PUBLIC_ENTRY_TYPE_EXTERNAL)
                        ->count()
                )
                ->modifyQueryUsing(
                    fn (Builder $query): Builder => $query->where('public_entry_type', FormTransfer::PUBLIC_ENTRY_TYPE_EXTERNAL)
                ),
        ];
    }
}
