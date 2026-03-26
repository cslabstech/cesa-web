<?php

namespace Cesa\Lead\Enums;

enum PhoneTransactionRange: string
{
    case Below2Million = 'Harga di bawah 2 juta';
    case TwoTo3Million = 'Harga 2 - 3 juta';
    case ThreeTo4Million = 'Harga 3 - 4 juta';
    case FourTo7Million = 'Harga 4 - 7 juta';
    case Above7Million = 'Harga di atas 7 juta';

    /**
     * Get all enum values as an array.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get all enum labels for use in forms.
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->value])
            ->toArray();
    }

    /**
     * Get the label for display.
     */
    public function label(): string
    {
        return $this->value;
    }
}
