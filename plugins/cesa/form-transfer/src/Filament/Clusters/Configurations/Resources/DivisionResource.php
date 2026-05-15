<?php

namespace Cesa\FormTransfer\Filament\Clusters\Configurations\Resources;

use Cesa\FormTransfer\Filament\Clusters\Configurations;
use Cesa\FormTransfer\Filament\Clusters\Configurations\Resources\DivisionResource\Pages;
use Cesa\FormTransfer\Models\TransferDivision;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Webkul\Security\Traits\HasResourcePermissionQuery;

class DivisionResource extends Resource
{
    use HasResourcePermissionQuery;

    protected static ?string $model = TransferDivision::class;

    protected static ?string $cluster = Configurations::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?int $navigationSort = 110;

    public static function getNavigationLabel(): string
    {
        return __('form-transfer::filament/clusters/configurations/resources/division.navigation.label');
    }

    public static function getNavigationGroup(): string
    {
        return __('form-transfer::filament/clusters/configurations/resources/division.navigation.group');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('form_transfer_id')
                    ->label(__('form-transfer::filament/clusters/configurations/resources/division.fields.form_transfer'))
                    ->relationship(
                        name: 'formTransfer',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query): Builder => $query->internalEntry(),
                    )
                    ->required()
                    ->searchable()
                    ->preload()
                    ->columnSpanFull(),
                TextInput::make('name')
                    ->label(__('form-transfer::filament/clusters/configurations/resources/division.fields.name'))
                    ->maxLength(191)
                    ->required()
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->label(__('form-transfer::filament/clusters/configurations/resources/division.fields.is_active'))
                    ->default(true)
                    ->columnSpanFull(),
                Textarea::make('description')
                    ->label(__('form-transfer::filament/clusters/configurations/resources/division.fields.description'))
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('form-transfer::filament/clusters/configurations/resources/division.columns.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('formTransfer.name')
                    ->label(__('form-transfer::filament/clusters/configurations/resources/division.columns.form_transfer'))
                    ->sortable()
                    ->searchable(),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label(__('form-transfer::filament/clusters/configurations/resources/division.columns.is_active'))
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('form_transfer_id')
                    ->label(__('form-transfer::filament/clusters/configurations/resources/division.filters.form_transfer'))
                    ->relationship(
                        'formTransfer',
                        'name',
                        fn (Builder $query): Builder => $query->internalEntry(),
                    )
                    ->searchable(),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('form-transfer::filament/clusters/configurations/resources/division.filters.is_active')),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make()->slideOver()->modalWidth('md'),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
                RestoreBulkAction::make(),
                ForceDeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDivisions::route('/'),
        ];
    }
}
