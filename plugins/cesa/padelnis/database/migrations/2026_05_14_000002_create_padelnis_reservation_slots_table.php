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
        Schema::create('padelnis_reservation_slots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reservation_id')
                ->constrained('padelnis_reservations')
                ->cascadeOnDelete();
            $table->string('active_slot_key', 512)->unique();

            $table->index('reservation_id');
        });

        $retainedActiveSlotKeys = [];

        DB::table('padelnis_reservations')
            ->select(['id', 'court', 'reservation_date', 'reservation_time', 'deleted_at'])
            ->orderBy('id')
            ->get()
            ->each(function (object $reservation) use (&$retainedActiveSlotKeys): void {
                if ($reservation->deleted_at !== null) {
                    return;
                }

                foreach (Reservation::activeSlotKeys($reservation->court, $reservation->reservation_date, $reservation->reservation_time) as $activeSlotKey) {
                    if (array_key_exists($activeSlotKey, $retainedActiveSlotKeys)) {
                        continue;
                    }

                    $retainedActiveSlotKeys[$activeSlotKey] = true;

                    DB::table('padelnis_reservation_slots')->insert([
                        'reservation_id'   => $reservation->id,
                        'active_slot_key'  => $activeSlotKey,
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('padelnis_reservation_slots');
    }
};
