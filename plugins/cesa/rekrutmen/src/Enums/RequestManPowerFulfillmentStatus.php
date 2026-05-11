<?php

namespace Cesa\Rekrutmen\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum RequestManPowerFulfillmentStatus: string implements HasColor, HasLabel
{
    case FULFILLED = 'fulfilled';
    case UNFULFILLED = 'unfulfilled';
    case CLOSED = 'closed';
    case PENDING_APPROVAL = 'pending_approval';
    case ON_HOLD = 'on_hold';
    case IN_PROCESS = 'in_process';
    case NO_CANDIDATE = 'no_candidate';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::FULFILLED        => __('rekrutmen::enums/request-man-power-fulfillment-status.fulfilled'),
            self::UNFULFILLED      => __('rekrutmen::enums/request-man-power-fulfillment-status.unfulfilled'),
            self::CLOSED           => __('rekrutmen::enums/request-man-power-fulfillment-status.closed'),
            self::PENDING_APPROVAL => __('rekrutmen::enums/request-man-power-fulfillment-status.pending_approval'),
            self::ON_HOLD          => __('rekrutmen::enums/request-man-power-fulfillment-status.on_hold'),
            self::IN_PROCESS       => __('rekrutmen::enums/request-man-power-fulfillment-status.in_process'),
            self::NO_CANDIDATE     => __('rekrutmen::enums/request-man-power-fulfillment-status.no_candidate'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::FULFILLED        => 'success',
            self::UNFULFILLED      => 'warning',
            self::CLOSED           => 'gray',
            self::PENDING_APPROVAL => 'warning',
            self::ON_HOLD          => 'gray',
            self::IN_PROCESS       => 'primary',
            self::NO_CANDIDATE     => 'danger',
        };
    }
}
