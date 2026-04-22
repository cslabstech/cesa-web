<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('form_transfers') || ! Schema::hasColumn('form_transfers', 'public_open_in_new_tab')) {
            return;
        }

        Schema::table('form_transfers', function (Blueprint $table): void {
            $table->dropColumn('public_open_in_new_tab');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('form_transfers') || Schema::hasColumn('form_transfers', 'public_open_in_new_tab')) {
            return;
        }

        Schema::table('form_transfers', function (Blueprint $table): void {
            $table->boolean('public_open_in_new_tab')->default(false)->after('show_on_affiliate_index');
        });
    }
};
