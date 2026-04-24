<?php

namespace Cesa\Rekrutmen\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum RequestManPowerStatus: string implements HasColor, HasLabel
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case HOLD = 'hold';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PENDING  => __('rekrutmen::enums/request-man-power-status.pending'),
            self::APPROVED => __('rekrutmen::enums/request-man-power-status.approved'),
            self::REJECTED => __('rekrutmen::enums/request-man-power-status.rejected'),
            self::HOLD     => __('rekrutmen::enums/request-man-power-status.hold'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PENDING  => 'warning',
            self::APPROVED => 'success',
            self::REJECTED => 'danger',
            self::HOLD     => 'gray',
        };
    }
}
