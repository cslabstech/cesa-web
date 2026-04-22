<?php

namespace Cesa\FormTransfer\Filament\Clusters\Configurations\Resources;

use Cesa\FormTransfer\Filament\Clusters\Configurations;
use Cesa\FormTransfer\Filament\Clusters\Configurations\Resources\ReferenceNoteResource\Pages;
use Cesa\FormTransfer\Models\TransferReferenceNote;
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

class ReferenceNoteResource extends Resource
{
    protected static ?string $model = TransferReferenceNote::class;

    protected static ?string $cluster = Configurations::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 130;

    public static function getNavigationLabel(): string
    {
        return __('form-transfer::filament/clusters/configurations/resources/reference-note.navigation.label');
    }

    public static function getNavigationGroup(): string
    {
        return __('form-transfer::filament/clusters/configurations/resources/reference-note.navigation.group');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('form_transfer_id')
                    ->label(__('form-transfer::filament/clusters/configurations/resources/reference-note.fields.form_transfer'))
                    ->relationship(
                        name: 'formTransfer',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query): Builder => $query->internalEntry(),
                    )
                    ->required()
                    ->searchable()
                    ->preload()
                    ->columnSpanFull(),
                TextInput::make('label')
                    ->label(__('form-transfer::filament/clusters/configurations/resources/reference-note.fields.label'))
                    ->maxLength(191)
                    ->required()
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->label(__('form-transfer::filament/clusters/configurations/resources/reference-note.fields.is_active'))
                    ->default(true)
                    ->columnSpanFull(),
                Textarea::make('description')
                    ->label(__('form-transfer::filament/clusters/configurations/resources/reference-note.fields.description'))
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label')
                    ->label(__('form-transfer::filament/clusters/configurations/resources/reference-note.columns.label'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('formTransfer.name')
                    ->label(__('form-transfer::filament/clusters/configurations/resources/reference-note.columns.form_transfer'))
                    ->sortable()
                    ->searchable(),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label(__('form-transfer::filament/clusters/configurations/resources/reference-note.columns.is_active'))
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('form_transfer_id')
                    ->label(__('form-transfer::filament/clusters/configurations/resources/reference-note.filters.form_transfer'))
                    ->relationship(
                        'formTransfer',
                        'name',
                        fn (Builder $query): Builder => $query->internalEntry(),
                    )
                    ->searchable(),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('form-transfer::filament/clusters/configurations/resources/reference-note.filters.is_active')),
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
            'index' => Pages\ListReferenceNotes::route('/'),
        ];
    }
}
