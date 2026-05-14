<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('padelnis_reservations', function (Blueprint $table): void {
            $table->id();
            $table->string('id_reff')->unique();
            $table->string('customer_name');
            $table->date('reservation_date');
            $table->string('court');
            $table->string('reservation_time', 20);
            $table->decimal('transfer_amount', 15, 2);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['reservation_date', 'reservation_time']);
            $table->index(['court', 'reservation_date']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('padelnis_reservations');
    }
};
