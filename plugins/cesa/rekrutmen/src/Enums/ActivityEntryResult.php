<?php

namespace Cesa\Rekrutmen\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ActivityEntryResult: string implements HasColor, HasLabel
{
    case PASSED = 'passed';
    case FAILED = 'failed';
    case PENDING = 'pending';
    case ACCEPTED = 'accepted';
    case REJECTED = 'rejected';

    /**
     * @return array<string, string>
     */
    public static function activityOptions(): array
    {
        return collect([
            self::PASSED,
            self::FAILED,
            self::PENDING,
        ])
            ->mapWithKeys(fn (self $result): array => [$result->value => (string) $result->getLabel()])
            ->all();
    }

    public function isActivityOutcome(): bool
    {
        return in_array($this, [
            self::PASSED,
            self::FAILED,
            self::PENDING,
        ], true);
    }

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PASSED   => __('rekrutmen::enums/activity-entry-result.passed'),
            self::FAILED   => __('rekrutmen::enums/activity-entry-result.failed'),
            self::PENDING  => __('rekrutmen::enums/activity-entry-result.pending'),
            self::ACCEPTED => __('rekrutmen::enums/activity-entry-result.accepted'),
            self::REJECTED => __('rekrutmen::enums/activity-entry-result.rejected'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PASSED   => 'success',
            self::FAILED   => 'danger',
            self::PENDING  => 'warning',
            self::ACCEPTED => 'success',
            self::REJECTED => 'danger',
        };
    }
}
