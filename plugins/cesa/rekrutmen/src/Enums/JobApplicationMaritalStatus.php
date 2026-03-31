<?php

namespace Cesa\Rekrutmen\Enums;

use Filament\Support\Contracts\HasLabel;

enum JobApplicationMaritalStatus: string implements HasLabel
{
    case Single = 'single';
    case Married = 'married';
    case Divorced = 'divorced';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Single   => __('rekrutmen::enums/job-application-marital-status.single'),
            self::Married  => __('rekrutmen::enums/job-application-marital-status.married'),
            self::Divorced => __('rekrutmen::enums/job-application-marital-status.divorced'),
        };
    }
}
