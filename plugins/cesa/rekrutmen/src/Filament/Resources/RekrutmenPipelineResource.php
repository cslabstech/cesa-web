<?php

namespace Cesa\Rekrutmen\Filament\Resources;

use Cesa\Rekrutmen\Filament\Clusters\Configurations;
use Cesa\Rekrutmen\Filament\Resources\RekrutmenPipelineResource\Pages;
use Cesa\Rekrutmen\Models\RekrutmenPipeline;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class RekrutmenPipelineResource extends RekrutmenConfigurationResource
{
    protected static ?string $model = RekrutmenPipeline::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-queue-list';

    protected static ?string $cluster = Configurations::class;

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('rekrutmen::filament/resources/rekrutmen-pipeline.navigation.label');
    }

    public static function getModelLabel(): string
    {
        return __('rekrutmen::filament/resources/rekrutmen-pipeline.model.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('rekrutmen::filament/resources/rekrutmen-pipeline.model.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('rekrutmen::filament/resources/rekrutmen-pipeline.form.fields.name'))
                    ->required()
                    ->unique(RekrutmenPipeline::class, 'name', ignoreRecord: true)
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->label(__('rekrutmen::filament/resources/rekrutmen-pipeline.form.fields.description'))
                    ->maxLength(65535)
                    ->columnSpanFull(),
                Forms\Components\Repeater::make('activeStages')
                    ->relationship('activeStages')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('rekrutmen::filament/resources/rekrutmen-pipeline.form.fields.name'))
                            ->required()
                            ->maxLength(255),
                    ])
                    ->orderColumn('order_column')
                    ->defaultItems(1)
                    ->minItems(1)
                    ->addActionLabel(__('rekrutmen::filament/resources/rekrutmen-pipeline.form.actions.add_stage'))
                    ->reorderableWithButtons()
                    ->columnSpanFull(),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('rekrutmen::filament/resources/rekrutmen-pipeline.table.columns.name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('active_stages_count')
                    ->counts('activeStages')
                    ->label(__('rekrutmen::filament/resources/rekrutmen-pipeline.table.columns.stages_count'))
                    ->badge(),
                Tables\Columns\TextColumn::make('description')
                    ->label(__('rekrutmen::filament/resources/rekrutmen-pipeline.table.columns.description'))
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
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
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRekrutmenPipelines::route('/'),
        ];
    }
}
