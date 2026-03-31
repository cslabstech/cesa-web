<?php

namespace Cesa\Rekrutmen\Enums;

use Filament\Support\Contracts\HasLabel;

enum JobApplicationGender: string implements HasLabel
{
    case Male = 'male';
    case Female = 'female';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Male   => __('rekrutmen::enums/job-application-gender.male'),
            self::Female => __('rekrutmen::enums/job-application-gender.female'),
        };
    }
}
