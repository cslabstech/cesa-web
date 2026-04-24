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
        Schema::table('rekrutmen_request_man_powers', function (Blueprint $table) {
            $table->text('hold_reason')->nullable()->after('approved_by');
            $table->timestamp('held_at')->nullable()->after('hold_reason');
            $table->foreignId('held_by')->nullable()->after('held_at')->constrained('users')->nullOnDelete();
            $table->timestamp('resumed_at')->nullable()->after('held_by');
            $table->foreignId('resumed_by')->nullable()->after('resumed_at')->constrained('users')->nullOnDelete();
            $table->boolean('hold_job_posting_was_published')->default(false)->after('resumed_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rekrutmen_request_man_powers', function (Blueprint $table) {
            $table->dropForeign(['held_by']);
            $table->dropForeign(['resumed_by']);
            $table->dropColumn([
                'hold_reason',
                'held_at',
                'held_by',
                'resumed_at',
                'resumed_by',
                'hold_job_posting_was_published',
            ]);
        });
    }
};
