<?php

namespace Cesa\Rekrutmen\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum RequestManPowerApprovalStatus: string implements HasColor, HasLabel
{
    case WAITING = 'waiting';
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::WAITING  => __('rekrutmen::enums/request-man-power-approval-status.waiting'),
            self::PENDING  => __('rekrutmen::enums/request-man-power-approval-status.pending'),
            self::APPROVED => __('rekrutmen::enums/request-man-power-approval-status.approved'),
            self::REJECTED => __('rekrutmen::enums/request-man-power-approval-status.rejected'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::WAITING  => 'gray',
            self::PENDING  => 'warning',
            self::APPROVED => 'success',
            self::REJECTED => 'danger',
        };
    }
}
