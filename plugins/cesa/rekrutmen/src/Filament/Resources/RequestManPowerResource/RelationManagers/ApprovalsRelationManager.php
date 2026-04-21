<?php

namespace Cesa\Rekrutmen\Filament\Resources\RequestManPowerResource\RelationManagers;

use Cesa\Rekrutmen\Enums\RequestManPowerApprovalStatus;
use Cesa\Rekrutmen\Models\RequestManPowerApproval;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ApprovalsRelationManager extends RelationManager
{
    protected static string $relationship = 'approvals';

    protected static bool $isReadOnly = true;

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return true;
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Alur Persetujuan';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('approver_name')
            ->defaultSort('step_order')
            ->columns([
                Tables\Columns\TextColumn::make('step_order')
                    ->label('Langkah')
                    ->sortable()
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('approver_name')
                    ->label('Nama Approver')
                    ->searchable(),
                Tables\Columns\TextColumn::make('approver_title')
                    ->label('Jabatan')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('approver_email')
                    ->label('Email')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (RequestManPowerApprovalStatus|string|null $state) => $state instanceof RequestManPowerApprovalStatus ? $state->getLabel() : $state)
                    ->color(fn (RequestManPowerApproval $record): string|array|null => $record->status?->getColor()),
                Tables\Columns\TextColumn::make('notified_at')
                    ->label('Dikirim')
                    ->dateTime('d M Y H:i')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('acted_at')
                    ->label('Ditindaklanjuti')
                    ->dateTime('d M Y H:i')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('notes')
                    ->label('Catatan Approver')
                    ->limit(60)
                    ->placeholder('—'),
            ])
            ->filters([])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
