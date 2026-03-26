<?php

namespace Cesa\Lead\Enums;

enum StoreTeamPosition: string
{
    case StoreHead = 'Kepala Toko';
    case Promotor = 'Promotor';
    case Cashier = 'Kasir';
    case Frontliner = 'Frontliner';

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
