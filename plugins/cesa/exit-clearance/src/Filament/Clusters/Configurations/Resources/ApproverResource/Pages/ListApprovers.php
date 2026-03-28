<?php

namespace Cesa\ExitClearance\Filament\Clusters\Configurations\Resources\ApproverResource\Pages;

use Cesa\ExitClearance\Filament\Clusters\Configurations\Resources\ApproverResource;
use Cesa\ExitClearance\Models\Approver;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListApprovers extends ManageRecords
{
    protected static string $resource = ApproverResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make(__('exit-clearance::filament/resources/approver/pages/list-approver.tabs.all'))
                ->badge(Approver::query()->applyPermissionScope()->count()),
            'archived' => Tab::make(__('exit-clearance::filament/resources/approver/pages/list-approver.tabs.archived'))
                ->badge(Approver::query()->applyPermissionScope()->onlyTrashed()->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->onlyTrashed()),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->icon('heroicon-o-plus-circle')->slideOver()->modalWidth('md'),
        ];
    }
}
