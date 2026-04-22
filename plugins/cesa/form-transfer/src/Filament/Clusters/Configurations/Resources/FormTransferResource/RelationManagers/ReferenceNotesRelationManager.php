<?php

namespace Cesa\FormTransfer\Filament\Clusters\Configurations\Resources\FormTransferResource\RelationManagers;

use Cesa\FormTransfer\Models\FormTransfer;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ReferenceNotesRelationManager extends RelationManager
{
    protected static string $relationship = 'referenceNotes';

    protected static ?string $title = 'Reference Notes';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('form-transfer::filament/resources/transfer-request/relation-managers.reference_notes');
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord instanceof FormTransfer
            && $ownerRecord->public_entry_type === FormTransfer::PUBLIC_ENTRY_TYPE_INTERNAL;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('label')
                ->label(__('form-transfer::filament/clusters/configurations/resources/reference-note.fields.label'))
                ->required()
                ->maxLength(191),
            Toggle::make('is_active')
                ->label(__('form-transfer::filament/clusters/configurations/resources/reference-note.fields.is_active'))
                ->default(true),
            Textarea::make('description')
                ->label(__('form-transfer::filament/clusters/configurations/resources/reference-note.fields.description'))
                ->rows(3)
                ->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
                CreateAction::make()->icon('heroicon-o-plus-circle')->slideOver()
                    ->modalHeading(__('form-transfer::filament/clusters/configurations/resources/reference-note.navigation.label')),
            ])
            ->columns([
                TextColumn::make('label')
                    ->label(__('form-transfer::filament/clusters/configurations/resources/reference-note.columns.label'))
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label(__('form-transfer::filament/clusters/configurations/resources/reference-note.columns.is_active'))
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make()->slideOver(),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
                RestoreBulkAction::make(),
                ForceDeleteBulkAction::make(),
            ])
            ->filters([
                TrashedFilter::make(),
            ]);
    }
}
