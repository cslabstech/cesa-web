<?php

namespace Cesa\FormTransfer\Enums;

enum TransferRequestRealizationStatus: string
{
    case PENDING = 'pending';
    case DONE = 'done';
    case CANCELLED = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING   => __('form-transfer::enums/transfer-request-realization-status.pending'),
            self::DONE      => __('form-transfer::enums/transfer-request-realization-status.done'),
            self::CANCELLED => __('form-transfer::enums/transfer-request-realization-status.cancelled'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PENDING   => 'warning',
            self::DONE      => 'success',
            self::CANCELLED => 'danger',
        };
    }

    public static function getOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status): array => [$status->value => $status->getLabel()])
            ->all();
    }
}
