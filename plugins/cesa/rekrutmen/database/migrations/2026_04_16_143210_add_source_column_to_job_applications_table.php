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
        if (Schema::hasColumn('rekrutmen_job_applications', 'source')) {
            return;
        }

        Schema::table('rekrutmen_job_applications', function (Blueprint $table) {
            $table->string('source')->nullable()->after('resume_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('rekrutmen_job_applications', 'source')) {
            return;
        }

        Schema::table('rekrutmen_job_applications', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
