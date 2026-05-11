<?php

namespace Cesa\Rekrutmen\Filament\Resources\JobPostingResource\Pages;

use Cesa\Rekrutmen\Filament\Resources\JobApplicationResource;
use Cesa\Rekrutmen\Filament\Resources\JobPostingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditJobPosting extends EditRecord
{
    protected static string $resource = JobPostingResource::class;

    /**
     * @var array<int, int>
     */
    protected array $linkedRequestManPowerIds = [];

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('open_pipeline')
                ->label(__('rekrutmen::filament/resources/job-posting.table.actions.open_pipeline'))
                ->icon('heroicon-o-view-columns')
                ->color('gray')
                ->url(fn (): string => JobApplicationResource::getUrl('board', ['job_posting_id' => $this->record->id])),
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->linkedRequestManPowerIds = JobPostingResource::normalizeLinkedRequestManPowerIds(
            $data[JobPostingResource::LINKED_REQUEST_MAN_POWER_IDS_FIELD] ?? []
        );

        unset($data[JobPostingResource::LINKED_REQUEST_MAN_POWER_IDS_FIELD]);

        JobPostingResource::validateLinkedRequestManPowerSelection($this->record, $this->linkedRequestManPowerIds);

        return $data;
    }

    protected function afterSave(): void
    {
        JobPostingResource::syncLinkedRequestManPowers($this->record, $this->linkedRequestManPowerIds);
    }
}
