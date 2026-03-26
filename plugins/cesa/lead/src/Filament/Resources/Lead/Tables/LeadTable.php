<?php

namespace Cesa\Lead\Filament\Resources\Lead\Tables;

use Carbon\Carbon;
use Cesa\Lead\Models\Lead;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LeadTable
{
    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                return $query->select([
                    'id',
                    'name',
                    'phone',
                    'address',
                    'sales_person',
                    'store_team_position',
                    'store_branch',
                    'phone_transaction_range',
                    'created_at',
                ]);
            })
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('lead::filament/resources/lead.table.columns.name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label(__('lead::filament/resources/lead.table.columns.phone'))
                    ->searchable()
                    ->copyable()
                    ->copyMessage(__('lead::filament/resources/lead.actions.copy_phone'))
                    ->copyMessageDuration(1500),
                Tables\Columns\TextColumn::make('sales_person')
                    ->label(__('lead::filament/resources/lead.table.columns.sales_person'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('store_team_position')
                    ->label(__('lead::filament/resources/lead.table.columns.store_team_position'))
                    ->badge()
                    ->state(fn ($record) => $record->store_team_position?->label() ?? '')
                    ->color(fn (string $state): string => match ($state) {
                        'Kepala Toko' => 'danger',
                        'Promotor'    => 'warning',
                        'Kasir'       => 'info',
                        'Frontliner'  => 'success',
                        default       => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('store_branch')
                    ->label(__('lead::filament/resources/lead.table.columns.store_branch'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('phone_transaction_range')
                    ->label(__('lead::filament/resources/lead.table.columns.phone_transaction_range'))
                    ->badge()
                    ->state(fn ($record) => $record->phone_transaction_range?->label() ?? '')
                    ->color(fn (string $state): string => match ($state) {
                        'Harga di bawah 2 juta' => 'danger',
                        'Harga 2 - 3 juta'      => 'warning',
                        'Harga 3 - 4 juta'      => 'info',
                        'Harga 4 - 7 juta'      => 'success',
                        'Harga di atas 7 juta'  => 'primary',
                        default                 => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('lead::filament/resources/lead.table.columns.created_at'))
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->filters([
                Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')->label(__('lead::filament/resources/lead.filters.created_from')),
                        Forms\Components\DatePicker::make('created_until')->label(__('lead::filament/resources/lead.filters.created_until')),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['created_from'], fn ($q) => $q->whereDate('created_at', '>=', $data['created_from']))
                            ->when($data['created_until'], fn ($q) => $q->whereDate('created_at', '<=', $data['created_until']));
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['created_from'] && ! $data['created_until']) {
                            return null;
                        }

                        $from = $data['created_from'] ? Carbon::parse($data['created_from'])->format('d M Y') : null;
                        $until = $data['created_until'] ? Carbon::parse($data['created_until'])->format('d M Y') : null;

                        if ($from && $until) {
                            return __('lead::filament/resources/lead.filters.date_range').": {$from} - {$until}";
                        }

                        return $from ? __('lead::filament/resources/lead.filters.created_from').": {$from}" : __('lead::filament/resources/lead.filters.created_until').": {$until}";
                    }),
                SelectFilter::make('store_team_position')
                    ->label(__('lead::filament/resources/lead.filters.store_team_position'))
                    ->options([
                        'Kepala Toko' => __('lead::filament/resources/lead.options.store_team_position.kepala_toko'),
                        'Promotor'    => __('lead::filament/resources/lead.options.store_team_position.promotor'),
                        'Kasir'       => __('lead::filament/resources/lead.options.store_team_position.kasir'),
                        'Frontliner'  => __('lead::filament/resources/lead.options.store_team_position.frontliner'),
                    ]),
                SelectFilter::make('store_branch')
                    ->label(__('lead::filament/resources/lead.filters.store_branch'))
                    ->searchable()
                    ->options(Lead::storeBranchOptions()),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
