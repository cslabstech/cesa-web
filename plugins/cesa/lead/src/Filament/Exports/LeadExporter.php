<?php

namespace Cesa\Lead\Filament\Exports;

use Cesa\Lead\Enums\PhoneTransactionRange;
use Cesa\Lead\Enums\StoreTeamPosition;
use Cesa\Lead\Models\Lead;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class LeadExporter extends Exporter
{
    protected static ?string $model = Lead::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('name'),
            ExportColumn::make('phone'),
            ExportColumn::make('address'),
            ExportColumn::make('sales_person'),
            ExportColumn::make('store_team_position')
                ->formatStateUsing(fn ($state) => $state instanceof StoreTeamPosition ? $state->label() : $state),
            ExportColumn::make('store_branch'),
            ExportColumn::make('phone_transaction_range')
                ->formatStateUsing(fn ($state) => $state instanceof PhoneTransactionRange ? $state->label() : $state),
            ExportColumn::make('created_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return __('lead::filament/resources/lead.exports.notifications.completed_body', [
            'success' => number_format($export->successful_rows),
            'failed'  => number_format($export->getFailedRowsCount()),
        ]);
    }
}
