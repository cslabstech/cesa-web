<?php

namespace Cesa\Rekrutmen\Filament\Resources;

use Cesa\Rekrutmen\Filament\Clusters\Configurations;
use Cesa\Rekrutmen\Filament\Resources\DivisionResource\Pages;
use Cesa\Rekrutmen\Models\Division;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DivisionResource extends RekrutmenConfigurationResource
{
    protected static ?string $model = Division::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $cluster = Configurations::class;

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('rekrutmen::filament/resources/division.navigation.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('rekrutmen::filament/resources/division.model.plural');
    }

    public static function getModelLabel(): string
    {
        return __('rekrutmen::filament/resources/division.model.singular');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('company_id')
                    ->label(__('rekrutmen::filament/resources/division.form.fields.company_id'))
                    ->relationship(
                        name: 'company',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query): Builder => $query
                            ->where('is_active', true)
                            ->orderBy('name')
                    )
                    ->required()
                    ->searchable()
                    ->preload(),
                TextInput::make('name')
                    ->label(__('rekrutmen::filament/resources/division.form.fields.name'))
                    ->required()
                    ->maxLength(255),
                Toggle::make('is_active')
                    ->label(__('rekrutmen::filament/resources/division.form.fields.is_active'))
                    ->default(true),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('rekrutmen::filament/resources/division.table.columns.name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('company.name')
                    ->label(__('rekrutmen::filament/resources/division.table.columns.company_id'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('rekrutmen::filament/resources/division.table.columns.is_active'))
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('company_id')
                    ->relationship('company', 'name')
                    ->label(__('rekrutmen::filament/resources/division.table.filters.company_id'))
                    ->searchable()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('rekrutmen::filament/resources/division.table.filters.is_active')),
            ])
            ->recordActions([
                EditAction::make()
                    ->slideOver()
                    ->modalWidth('md')
                    ->visible(fn ($record): bool => ! method_exists($record, 'trashed') || ! $record->trashed()),
                DeleteAction::make()
                    ->visible(fn ($record): bool => ! method_exists($record, 'trashed') || ! $record->trashed()),
                RestoreAction::make()
                    ->visible(fn ($record): bool => method_exists($record, 'trashed') && $record->trashed()),
                ForceDeleteAction::make()
                    ->visible(fn ($record): bool => method_exists($record, 'trashed') && $record->trashed()),
            ])
            ->bulkActions([
                DeleteBulkAction::make()
                    ->visible(fn ($livewire = null): bool => ! static::isArchivedTab($livewire)),
                RestoreBulkAction::make()
                    ->visible(fn ($livewire = null): bool => static::isArchivedTab($livewire)),
                ForceDeleteBulkAction::make()
                    ->visible(fn ($livewire = null): bool => static::isArchivedTab($livewire)),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDivisions::route('/'),
        ];
    }
}
