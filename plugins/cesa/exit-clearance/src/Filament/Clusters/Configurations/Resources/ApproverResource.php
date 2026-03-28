<?php

namespace Cesa\ExitClearance\Filament\Clusters\Configurations\Resources;

use BackedEnum;
use Cesa\ExitClearance\Filament\Clusters\Configurations;
use Cesa\ExitClearance\Filament\Clusters\Configurations\Resources\ApproverResource\Pages\ListApprovers;
use Cesa\ExitClearance\Models\Approver;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ApproverResource extends ExitClearanceConfigurationResource
{
    protected static ?string $model = Approver::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-check-badge';

    protected static ?string $cluster = Configurations::class;

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return trans_choice('exit-clearance::filament/resources/approver.label', 2);
    }

    public static function getPluralModelLabel(): string
    {
        return trans_choice('exit-clearance::filament/resources/approver.label', 2);
    }

    public static function getModelLabel(): string
    {
        return trans_choice('exit-clearance::filament/resources/approver.label', 1);
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label(__('exit-clearance::filament/resources/approver.fields.name'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label(__('exit-clearance::filament/resources/approver.fields.email'))
                    ->email()
                    ->required()
                    ->maxLength(255),
                TextInput::make('phone')
                    ->label(__('exit-clearance::filament/resources/approver.fields.phone'))
                    ->tel()
                    ->maxLength(255),
                TextInput::make('title')
                    ->label(__('exit-clearance::filament/resources/approver.fields.title'))
                    ->required()
                    ->maxLength(255),
                Select::make('departments')
                    ->label(__('exit-clearance::filament/resources/approver.fields.departments'))
                    ->relationship('departments', 'name', modifyQueryUsing: fn (Builder $query): Builder => $query->applyPermissionScope())
                    ->multiple()
                    ->preload()
                    ->searchable(),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('exit-clearance::filament/resources/approver.fields.name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label(__('exit-clearance::filament/resources/approver.fields.email'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label(__('exit-clearance::filament/resources/approver.fields.phone'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->label(__('exit-clearance::filament/resources/approver.fields.title'))
                    ->searchable()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make()
                    ->slideOver()
                    ->modalWidth('md')
                    ->visible(fn (Approver $record): bool => ! $record->trashed()),
                DeleteAction::make()
                    ->visible(fn (Approver $record): bool => ! $record->trashed()),
                RestoreAction::make()
                    ->visible(fn (Approver $record): bool => $record->trashed()),
                ForceDeleteAction::make()
                    ->visible(fn (Approver $record): bool => $record->trashed()),
            ])
            ->bulkActions([
                DeleteBulkAction::make()
                    ->visible(fn ($livewire = null): bool => ! static::isArchivedTab($livewire)),
                RestoreBulkAction::make()
                    ->visible(fn ($livewire = null): bool => static::isArchivedTab($livewire)),
                ForceDeleteBulkAction::make()
                    ->visible(fn ($livewire = null): bool => static::isArchivedTab($livewire)),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListApprovers::route('/'),
        ];
    }
}
