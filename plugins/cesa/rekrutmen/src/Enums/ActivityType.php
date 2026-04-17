<?php

namespace Cesa\Rekrutmen\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Support\Str;

enum ActivityType: string implements HasColor, HasLabel
{
    case SCREENING = 'screening';
    case INTERVIEW_HRD = 'interview_hrd';
    case INTERVIEW_USER = 'interview_user';
    case TEST_TEKNIS = 'test_teknis';
    case TEST_PSIKOLOGI = 'test_psikologi';
    case MEDICAL_CHECKUP = 'medical_checkup';
    case REFERENCE_CHECK = 'reference_check';
    case OFFERING = 'offering';
    case OTHER = 'other';

    /**
     * @return array<string, string>
     */
    public static function optionsForStageName(?string $stageName): array
    {
        return collect(self::casesForStageName($stageName))
            ->mapWithKeys(fn (self $case): array => [
                $case->value => $case->getLabel() ?? $case->value,
            ])
            ->all();
    }

    /**
     * @return array<int, self>
     */
    public static function casesForStageName(?string $stageName): array
    {
        $normalizedStageName = Str::of((string) $stageName)
            ->lower()
            ->squish()
            ->value();

        if ($normalizedStageName === '') {
            return [];
        }

        if (str_contains($normalizedStageName, 'screen')) {
            return [self::SCREENING];
        }

        if (
            str_contains($normalizedStageName, 'interview hr')
            || str_contains($normalizedStageName, 'hr interview')
            || (str_contains($normalizedStageName, 'interview') && str_contains($normalizedStageName, 'hr'))
        ) {
            return [self::INTERVIEW_HRD];
        }

        if (
            str_contains($normalizedStageName, 'interview user')
            || str_contains($normalizedStageName, 'user interview')
            || (str_contains($normalizedStageName, 'interview') && str_contains($normalizedStageName, 'user'))
        ) {
            return [self::INTERVIEW_USER, self::TEST_TEKNIS, self::TEST_PSIKOLOGI];
        }

        if (str_contains($normalizedStageName, 'interview')) {
            return [self::INTERVIEW_HRD, self::INTERVIEW_USER];
        }

        if (str_contains($normalizedStageName, 'teknis') || str_contains($normalizedStageName, 'technical')) {
            return [self::TEST_TEKNIS];
        }

        if (str_contains($normalizedStageName, 'psikologi') || str_contains($normalizedStageName, 'psycholog')) {
            return [self::TEST_PSIKOLOGI];
        }

        if (str_contains($normalizedStageName, 'medical')) {
            return [self::MEDICAL_CHECKUP];
        }

        if (str_contains($normalizedStageName, 'reference')) {
            return [self::REFERENCE_CHECK];
        }

        if (str_contains($normalizedStageName, 'offer')) {
            return [self::OFFERING];
        }

        return [self::OTHER];
    }

    public function getLabel(): ?string
    {
        return match ($this) {
            self::SCREENING       => __('rekrutmen::enums/activity-type.screening'),
            self::INTERVIEW_HRD   => __('rekrutmen::enums/activity-type.interview_hrd'),
            self::INTERVIEW_USER  => __('rekrutmen::enums/activity-type.interview_user'),
            self::TEST_TEKNIS     => __('rekrutmen::enums/activity-type.test_teknis'),
            self::TEST_PSIKOLOGI  => __('rekrutmen::enums/activity-type.test_psikologi'),
            self::MEDICAL_CHECKUP => __('rekrutmen::enums/activity-type.medical_checkup'),
            self::REFERENCE_CHECK => __('rekrutmen::enums/activity-type.reference_check'),
            self::OFFERING        => __('rekrutmen::enums/activity-type.offering'),
            self::OTHER           => __('rekrutmen::enums/activity-type.other'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::SCREENING       => 'gray',
            self::INTERVIEW_HRD   => 'warning',
            self::INTERVIEW_USER  => 'info',
            self::TEST_TEKNIS     => 'primary',
            self::TEST_PSIKOLOGI  => 'purple',
            self::MEDICAL_CHECKUP => 'success',
            self::REFERENCE_CHECK => 'gray',
            self::OFFERING        => 'success',
            self::OTHER           => 'gray',
        };
    }
}
