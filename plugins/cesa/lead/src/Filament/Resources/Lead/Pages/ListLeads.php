<?php

namespace Cesa\Lead\Filament\Resources\Lead\Pages;

use Cesa\Lead\Filament\Exports\LeadExporter;
use Cesa\Lead\Filament\Imports\LeadImporter;
use Cesa\Lead\Filament\Resources\LeadResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLeads extends ListRecords
{
    protected static string $resource = LeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('lead::filament/resources/lead/pages/list-leads.header-actions.create.label')),
            Actions\ImportAction::make()
                ->importer(LeadImporter::class)
                ->label(__('lead::filament/resources/lead/pages/list-leads.header-actions.import.label'))
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->chunkSize(100)
                ->fileRules(['mimes:csv,txt', 'max:12288']),
            Actions\ExportAction::make()
                ->exporter(LeadExporter::class)
                ->label(__('lead::filament/resources/lead/pages/list-leads.header-actions.export.label'))
                ->icon('heroicon-o-arrow-down-tray'),
        ];
    }
}
