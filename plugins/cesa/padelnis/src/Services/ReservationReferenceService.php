<?php

namespace Cesa\Padelnis\Services;

use Cesa\Padelnis\Models\Reservation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReservationReferenceService
{
    public function generate(?Carbon $date = null): string
    {
        return DB::transaction(function () use ($date): string {
            $year = (int) ($date ?? now())->format('Y');
            $prefix = "BOOK-{$year}-";

            $lastReference = Reservation::query()
                ->withTrashed()
                ->where('id_reff', 'like', $prefix.'%')
                ->lockForUpdate()
                ->orderByDesc('id_reff')
                ->value('id_reff');

            $nextNumber = 1;

            if (is_string($lastReference) && preg_match('/^BOOK-'.$year.'-(\d{5})$/', $lastReference, $matches) === 1) {
                $nextNumber = ((int) $matches[1]) + 1;
            }

            return sprintf('%s%05d', $prefix, $nextNumber);
        });
    }
}
