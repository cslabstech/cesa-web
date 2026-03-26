<?php

namespace Cesa\Document\Filament\Resources\Document\Pages;

use Cesa\Document\Filament\Resources\DocumentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDocuments extends ListRecords
{
    protected static string $resource = DocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('document::filament/resources/document/pages/list-documents.header-actions.create.label')),
        ];
    }
}
