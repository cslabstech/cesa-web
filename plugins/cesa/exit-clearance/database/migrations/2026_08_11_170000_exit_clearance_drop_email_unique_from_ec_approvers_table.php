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
        try {
            Schema::table('exit_clearance_approvers', function (Blueprint $table): void {
                $table->dropUnique('exit_clearance_approvers_email_unique');
            });
        } catch (Throwable) {
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            Schema::table('exit_clearance_approvers', function (Blueprint $table): void {
                $table->unique('email');
            });
        } catch (Throwable) {
        }
    }
};
