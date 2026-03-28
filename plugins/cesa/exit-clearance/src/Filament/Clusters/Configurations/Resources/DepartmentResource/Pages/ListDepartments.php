<?php

namespace Cesa\ExitClearance\Filament\Clusters\Configurations\Resources\DepartmentResource\Pages;

use Cesa\ExitClearance\Filament\Clusters\Configurations\Resources\DepartmentResource;
use Cesa\ExitClearance\Models\Department;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListDepartments extends ManageRecords
{
    protected static string $resource = DepartmentResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make(__('exit-clearance::filament/resources/department/pages/list-department.tabs.all'))
                ->badge(Department::query()->applyPermissionScope()->count()),
            'archived' => Tab::make(__('exit-clearance::filament/resources/department/pages/list-department.tabs.archived'))
                ->badge(Department::query()->applyPermissionScope()->onlyTrashed()->count())
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
