<?php

namespace Cesa\Rekrutmen\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ActivityEntryResult: string implements HasColor, HasLabel
{
    case PASSED = 'passed';
    case FAILED = 'failed';
    case PENDING = 'pending';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PASSED  => __('rekrutmen::enums/activity-entry-result.passed'),
            self::FAILED  => __('rekrutmen::enums/activity-entry-result.failed'),
            self::PENDING => __('rekrutmen::enums/activity-entry-result.pending'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PASSED  => 'success',
            self::FAILED  => 'danger',
            self::PENDING => 'warning',
        };
    }
}
