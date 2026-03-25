<?php

namespace Cesa\Rekrutmen\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum JobApplicationStatus: string implements HasColor, HasLabel
{
    case IN_PROGRESS = 'in_progress';
    case HIRED = 'hired';
    case REJECTED = 'rejected';
    case WITHDRAWN = 'withdrawn';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::IN_PROGRESS => __('rekrutmen::enums/job-application-status.in_progress'),
            self::HIRED       => __('rekrutmen::enums/job-application-status.hired'),
            self::REJECTED    => __('rekrutmen::enums/job-application-status.rejected'),
            self::WITHDRAWN   => __('rekrutmen::enums/job-application-status.withdrawn'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::IN_PROGRESS => 'primary',
            self::HIRED       => 'success',
            self::REJECTED    => 'danger',
            self::WITHDRAWN   => 'warning',
        };
    }
}
