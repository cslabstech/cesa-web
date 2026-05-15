<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('padelnis_reservations')) {
            return;
        }

        Schema::table('padelnis_reservations', function (Blueprint $table): void {
            if (! Schema::hasColumn('padelnis_reservations', 'transfer_date')) {
                $table->date('transfer_date')->nullable()->after('transfer_amount');
            }

            if (! Schema::hasColumn('padelnis_reservations', 'notes')) {
                $table->text('notes')->nullable()->after('transfer_date');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('padelnis_reservations')) {
            return;
        }

        Schema::table('padelnis_reservations', function (Blueprint $table): void {
            if (Schema::hasColumn('padelnis_reservations', 'notes')) {
                $table->dropColumn('notes');
            }

            if (Schema::hasColumn('padelnis_reservations', 'transfer_date')) {
                $table->dropColumn('transfer_date');
            }
        });
    }
};
