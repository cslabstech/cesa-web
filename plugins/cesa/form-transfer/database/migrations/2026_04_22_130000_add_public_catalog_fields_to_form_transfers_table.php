<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('form_transfers')) {
            return;
        }

        Schema::table('form_transfers', function (Blueprint $table): void {
            if (! Schema::hasColumn('form_transfers', 'public_entry_type')) {
                $table->string('public_entry_type', 20)->default('internal')->after('description');
            }

            if (! Schema::hasColumn('form_transfers', 'public_external_url')) {
                $table->text('public_external_url')->nullable()->after('public_entry_type');
            }

            if (! Schema::hasColumn('form_transfers', 'public_badge_label')) {
                $table->string('public_badge_label', 100)->nullable()->after('public_external_url');
            }

            if (! Schema::hasColumn('form_transfers', 'public_sort_order')) {
                $table->integer('public_sort_order')->default(0)->after('public_badge_label');
            }

            if (! Schema::hasColumn('form_transfers', 'show_on_transfer_request_index')) {
                $table->boolean('show_on_transfer_request_index')->default(true)->after('public_sort_order');
            }

            if (! Schema::hasColumn('form_transfers', 'show_on_affiliate_index')) {
                $table->boolean('show_on_affiliate_index')->default(false)->after('show_on_transfer_request_index');
            }

            if (! Schema::hasColumn('form_transfers', 'public_open_in_new_tab')) {
                $table->boolean('public_open_in_new_tab')->default(false)->after('show_on_affiliate_index');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('form_transfers')) {
            return;
        }

        Schema::table('form_transfers', function (Blueprint $table): void {
            foreach ([
                'public_open_in_new_tab',
                'show_on_affiliate_index',
                'show_on_transfer_request_index',
                'public_sort_order',
                'public_badge_label',
                'public_external_url',
                'public_entry_type',
            ] as $column) {
                if (Schema::hasColumn('form_transfers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
