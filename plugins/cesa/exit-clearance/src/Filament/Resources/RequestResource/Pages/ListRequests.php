<?php

namespace Cesa\ExitClearance\Filament\Resources\RequestResource\Pages;

use Cesa\ExitClearance\Filament\Resources\RequestResource;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ListRequests extends ListRecords
{
    protected static string $resource = RequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->icon('heroicon-o-plus-circle'),
        ];
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('name')
                ->label(__('exit-clearance::filament/resources/request.table.employee_name'))
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('email')
                ->label(__('exit-clearance::filament/resources/request.table.email'))
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('department.name')
                ->label(__('exit-clearance::filament/resources/request.table.department'))
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('request_date')
                ->label(__('exit-clearance::filament/resources/request.table.request_date'))
                ->sortable()
                ->date(),
        ];
    }

    public function getTableFilters(): array
    {
        return [
            Tables\Filters\SelectFilter::make('department_id')
                ->label(__('exit-clearance::filament/resources/request.filters.department'))
                ->relationship('department', 'name')
                ->preload(),
            Tables\Filters\Filter::make('request_date')
                ->label(__('exit-clearance::filament/resources/request.filters.request_date'))
                ->form([
                    DatePicker::make('request_date')
                        ->label(__('exit-clearance::filament/resources/request.filters.request_date'))
                        ->displayFormat('Y-m-d'),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    $requestDate = $data['request_date'] ?? null;

                    if (blank($requestDate)) {
                        return $query;
                    }

                    return $query->whereDate('request_date', $requestDate);
                })
                ->indicateUsing(function (array $state): ?string {
                    $value = $state['request_date'] ?? null;

                    return $value ? Carbon::parse($value)->format('Y-m-d') : null;
                }),
        ];
    }
}
