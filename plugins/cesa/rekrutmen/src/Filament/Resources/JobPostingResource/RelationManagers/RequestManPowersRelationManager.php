<?php

namespace Cesa\Rekrutmen\Filament\Resources\JobPostingResource\RelationManagers;

use Cesa\Rekrutmen\Enums\RequestManPowerFulfillmentStatus;
use Cesa\Rekrutmen\Enums\RequestManPowerStatus;
use Cesa\Rekrutmen\Filament\Resources\RequestManPowerResource;
use Cesa\Rekrutmen\Models\RequestManPower;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class RequestManPowersRelationManager extends RelationManager
{
    protected static string $relationship = 'requestManPowers';

    protected static bool $isReadOnly = true;

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return true;
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('rekrutmen::filament/resources/job-posting.relations.request_man_powers.title');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with(['division', 'jobPosting.applications:id,job_posting_id,status']))
            ->recordTitleAttribute('posisi_dibutuhkan')
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label(__('rekrutmen::filament/resources/request-man-power.table.columns.id'))
                    ->formatStateUsing(fn (int|string|null $state): string => filled($state) ? 'MPP #'.$state : '-')
                    ->badge()
                    ->color('gray')
                    ->sortable(),
                Tables\Columns\TextColumn::make('posisi_dibutuhkan')
                    ->label(__('rekrutmen::filament/resources/request-man-power.table.columns.posisi_dibutuhkan'))
                    ->description(fn (RequestManPower $record): string => RequestManPowerResource::formatTablePositionDescription($record))
                    ->searchable()
                    ->wrap()
                    ->lineClamp(2)
                    ->sortable(),
                Tables\Columns\TextColumn::make('jumlah_karyawan_dibutuhkan')
                    ->label(__('rekrutmen::filament/resources/request-man-power.table.columns.jumlah_karyawan_dibutuhkan'))
                    ->numeric()
                    ->suffix(' orang')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('fulfillment_status')
                    ->label(__('rekrutmen::filament/resources/request-man-power.table.columns.fulfillment_status'))
                    ->state(fn (RequestManPower $record): RequestManPowerFulfillmentStatus => $record->fulfillmentStatus())
                    ->formatStateUsing(fn (RequestManPowerFulfillmentStatus|string|null $state): string => $state instanceof RequestManPowerFulfillmentStatus
                        ? (string) $state->getLabel()
                        : (string) ($state ?? '-'))
                    ->badge()
                    ->color(fn (RequestManPowerFulfillmentStatus|string|null $state): string|array|null => $state instanceof RequestManPowerFulfillmentStatus
                        ? $state->getColor()
                        : null),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('rekrutmen::filament/resources/request-man-power.table.columns.status'))
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tanggal_pengajuan')
                    ->label(__('rekrutmen::filament/resources/request-man-power.table.columns.tanggal_pengajuan'))
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('rekrutmen::filament/resources/request-man-power.table.filters.status'))
                    ->options(RequestManPowerStatus::class),
            ])
            ->headerActions([])
            ->actions([
                Action::make('view_request_man_power')
                    ->label(__('rekrutmen::filament/resources/job-posting.relations.request_man_powers.actions.view'))
                    ->url(fn (RequestManPower $record): string => RequestManPowerResource::getUrl('view', ['record' => $record]))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([]);
    }
}
