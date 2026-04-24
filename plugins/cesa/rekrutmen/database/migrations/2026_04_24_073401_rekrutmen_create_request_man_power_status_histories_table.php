<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rekrutmen_request_man_power_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_man_power_id')
                ->constrained(
                    table: 'rekrutmen_request_man_powers',
                    indexName: 'rekrutmen_rmp_status_histories_request_fk',
                )
                ->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->text('reason')->nullable();
            $table->foreignId('acted_by_user_id')
                ->nullable()
                ->constrained(
                    table: 'users',
                    indexName: 'rekrutmen_rmp_status_histories_actor_fk',
                )
                ->nullOnDelete();
            $table->timestamps();

            $table->index(['request_man_power_id', 'created_at'], 'rmp_status_histories_request_created_at_index');
            $table->index('to_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rekrutmen_request_man_power_status_histories');
    }
};
