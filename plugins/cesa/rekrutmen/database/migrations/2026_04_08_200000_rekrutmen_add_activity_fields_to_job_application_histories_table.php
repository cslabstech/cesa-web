<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function indexExists(string $table, string $indexName): bool
    {
        return Schema::hasIndex($table, $indexName);
    }

    public function up(): void
    {
        Schema::table('rekrutmen_job_application_histories', function (Blueprint $table) {
            if (! Schema::hasColumn('rekrutmen_job_application_histories', 'activity_type')) {
                $table->string('activity_type')->nullable()->after('to_stage_id');
            }
            if (! Schema::hasColumn('rekrutmen_job_application_histories', 'activity_date')) {
                $table->date('activity_date')->nullable()->after('activity_type');
            }
            if (! Schema::hasColumn('rekrutmen_job_application_histories', 'result')) {
                $table->string('result')->nullable()->after('activity_date');
            }
            if (! Schema::hasColumn('rekrutmen_job_application_histories', 'activity_title')) {
                $table->string('activity_title')->nullable()->after('result');
            }
            if (! Schema::hasColumn('rekrutmen_job_application_histories', 'activity_group_id')) {
                $table->uuid('activity_group_id')->nullable()->after('activity_title');
            }
        });

        Schema::table('rekrutmen_job_application_histories', function (Blueprint $table) {
            if (! $this->indexExists('rekrutmen_job_application_histories', 'rek_hist_activity_type_idx')) {
                $table->index('activity_type', 'rek_hist_activity_type_idx');
            }
            if (! $this->indexExists('rekrutmen_job_application_histories', 'rek_hist_activity_date_idx')) {
                $table->index('activity_date', 'rek_hist_activity_date_idx');
            }
            if (! $this->indexExists('rekrutmen_job_application_histories', 'rek_hist_activity_group_idx')) {
                $table->index('activity_group_id', 'rek_hist_activity_group_idx');
            }
            if (! $this->indexExists('rekrutmen_job_application_histories', 'rek_hist_group_app_idx')) {
                $table->index(['activity_group_id', 'job_application_id'], 'rek_hist_group_app_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('rekrutmen_job_application_histories', function (Blueprint $table) {
            if ($this->indexExists('rekrutmen_job_application_histories', 'rek_hist_group_app_idx')) {
                $table->dropIndex('rek_hist_group_app_idx');
            }
            if ($this->indexExists('rekrutmen_job_application_histories', 'rek_hist_activity_group_idx')) {
                $table->dropIndex('rek_hist_activity_group_idx');
            }
            if ($this->indexExists('rekrutmen_job_application_histories', 'rek_hist_activity_date_idx')) {
                $table->dropIndex('rek_hist_activity_date_idx');
            }
            if ($this->indexExists('rekrutmen_job_application_histories', 'rek_hist_activity_type_idx')) {
                $table->dropIndex('rek_hist_activity_type_idx');
            }
        });

        Schema::table('rekrutmen_job_application_histories', function (Blueprint $table) {
            $table->dropColumn([
                'activity_type',
                'activity_date',
                'result',
                'activity_title',
                'activity_group_id',
            ]);
        });
    }
};
