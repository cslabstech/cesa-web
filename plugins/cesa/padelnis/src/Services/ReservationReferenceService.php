<?php

namespace Cesa\Padelnis\Services;

use Cesa\Padelnis\Models\Reservation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReservationReferenceService
{
    public function generate(?Carbon $date = null): string
    {
        return DB::transaction(function (): string {
            $prefix = "UID";

            $lastReference = Reservation::query()
                ->withTrashed()
                ->lockForUpdate()
                ->latest('id')
                ->value('id_reff');

            $nextNumber = 1;

            if (is_string($lastReference) && preg_match('/(\d+)$/', $lastReference, $matches) === 1) {
                $nextNumber = ((int) $matches[1]) + 1;
            }

            return sprintf('%s%04d', $prefix, $nextNumber);
        });
    }
}
