<?php

namespace Cesa\Padelnis\Filament\Resources\ReservationResource\Pages;

use Cesa\Padelnis\Filament\Exports\ReservationExporter;
use Cesa\Padelnis\Filament\Resources\ReservationResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;

class ListReservations extends ListRecords
{
    protected static string $resource = ReservationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('padelnis::filament/resources/reservation.pages.list.header_actions.create.label'))
                ->icon('heroicon-o-plus-circle'),
            ExportAction::make()
                ->exporter(ReservationExporter::class)
                ->label(__('padelnis::filament/resources/reservation.pages.list.header_actions.export.label'))
                ->icon('heroicon-o-arrow-down-tray'),
        ];
    }
}
