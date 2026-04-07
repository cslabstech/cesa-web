<?php

namespace Cesa\Rekrutmen\Filament\Resources\RequestManPowerResource\Pages;

use Cesa\Rekrutmen\Filament\Resources\RequestManPowerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRequestManPower extends EditRecord
{
    protected static string $resource = RequestManPowerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['status'] = $this->record->status;
        $data['approved_by'] = $this->record->approved_by;

        return $data;
    }
}
