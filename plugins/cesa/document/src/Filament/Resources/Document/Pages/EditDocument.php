<?php

namespace Cesa\Document\Filament\Resources\Document\Pages;

use Cesa\Document\Filament\Resources\DocumentResource;
use Cesa\Document\Services\DocxService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditDocument extends EditRecord
{
    protected static string $resource = DocumentResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (($data['source_type'] ?? null) === 'docx' && ! empty($data['docx_path'])) {
            $path = Storage::disk('local')->path($data['docx_path']);
            try {
                $data['content'] = app(DocxService::class)->toHtml($path);
            } catch (\Throwable) {
                Notification::make()
                    ->title(__('document::filament/resources/document.notifications.docx_conversion_failed.title'))
                    ->warning()
                    ->send();
            }
        }

        return $data;
    }
}
