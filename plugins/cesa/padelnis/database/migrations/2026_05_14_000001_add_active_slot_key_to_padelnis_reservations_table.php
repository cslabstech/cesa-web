<?php

use Cesa\Padelnis\Models\Reservation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('padelnis_reservations', 'active_slot_key')) {
            Schema::table('padelnis_reservations', function (Blueprint $table): void {
                $table->string('active_slot_key', 512)->nullable()->after('reservation_time');
            });
        }

        $retainedActiveSlotKeys = [];

        DB::table('padelnis_reservations')
            ->select(['id', 'court', 'reservation_date', 'reservation_time', 'deleted_at'])
            ->orderBy('id')
            ->get()
            ->each(function (object $reservation) use (&$retainedActiveSlotKeys): void {
                $activeSlotKey = null;

                if ($reservation->deleted_at === null) {
                    $candidateSlotKey = Reservation::makeActiveSlotKey($reservation->court, $reservation->reservation_date, $reservation->reservation_time);

                    if ($candidateSlotKey !== null && ! array_key_exists($candidateSlotKey, $retainedActiveSlotKeys)) {
                        $retainedActiveSlotKeys[$candidateSlotKey] = true;
                        $activeSlotKey = $candidateSlotKey;
                    }
                }

                DB::table('padelnis_reservations')
                    ->where('id', $reservation->id)
                    ->update(['active_slot_key' => $activeSlotKey]);
            });

        Schema::table('padelnis_reservations', function (Blueprint $table): void {
            $table->unique('active_slot_key', 'padelnis_reservations_active_slot_key_unique');
        });
    }

    public function down(): void
    {
        Schema::table('padelnis_reservations', function (Blueprint $table): void {
            $table->dropUnique('padelnis_reservations_active_slot_key_unique');
            $table->dropColumn('active_slot_key');
        });
    }
};
