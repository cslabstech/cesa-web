<?php

namespace Cesa\Rekrutmen\Filament\Resources\RequestManPowerResource\Pages;

use Cesa\Rekrutmen\Enums\RequestManPowerStatus;
use Cesa\Rekrutmen\Filament\Resources\RequestManPowerResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRequestManPower extends CreateRecord
{
    protected static string $resource = RequestManPowerResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = RequestManPowerStatus::PENDING;
        $data['approved_by'] = null;

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->sendApprovalRequestNotifications();
    }
}
