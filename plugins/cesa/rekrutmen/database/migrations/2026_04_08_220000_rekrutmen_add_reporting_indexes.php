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
        Schema::table('rekrutmen_job_applications', function (Blueprint $table) {
            if (! $this->indexExists('rekrutmen_job_applications', 'rekrutmen_job_apps_posting_status_stage_index')) {
                $table->index(
                    ['job_posting_id', 'status', 'current_stage_id'],
                    'rekrutmen_job_apps_posting_status_stage_index'
                );
            }
        });

        Schema::table('rekrutmen_job_application_histories', function (Blueprint $table) {
            if (! $this->indexExists('rekrutmen_job_application_histories', 'rekrutmen_histories_app_date_type_index')) {
                $table->index(
                    ['job_application_id', 'activity_date', 'activity_type'],
                    'rekrutmen_histories_app_date_type_index'
                );
            }

            if (! $this->indexExists('rekrutmen_job_application_histories', 'rekrutmen_histories_stage_result_app_index')) {
                $table->index(
                    ['to_stage_id', 'result', 'job_application_id'],
                    'rekrutmen_histories_stage_result_app_index'
                );
            }
        });
    }

    public function down(): void
    {
        Schema::table('rekrutmen_job_applications', function (Blueprint $table) {
            if ($this->indexExists('rekrutmen_job_applications', 'rekrutmen_job_apps_posting_status_stage_index')) {
                $table->dropIndex('rekrutmen_job_apps_posting_status_stage_index');
            }
        });

        Schema::table('rekrutmen_job_application_histories', function (Blueprint $table) {
            if ($this->indexExists('rekrutmen_job_application_histories', 'rekrutmen_histories_app_date_type_index')) {
                $table->dropIndex('rekrutmen_histories_app_date_type_index');
            }
            if ($this->indexExists('rekrutmen_job_application_histories', 'rekrutmen_histories_stage_result_app_index')) {
                $table->dropIndex('rekrutmen_histories_stage_result_app_index');
            }
        });
    }
};
