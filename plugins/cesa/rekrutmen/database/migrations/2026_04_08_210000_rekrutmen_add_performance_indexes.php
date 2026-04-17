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
            if (! $this->indexExists('rekrutmen_job_applications', 'rekrutmen_job_apps_posting_status_index')) {
                $table->index(['job_posting_id', 'status'], 'rekrutmen_job_apps_posting_status_index');
            }
            if (! $this->indexExists('rekrutmen_job_applications', 'rekrutmen_job_apps_stage_index')) {
                $table->index('current_stage_id', 'rekrutmen_job_apps_stage_index');
            }
        });

        Schema::table('rekrutmen_stages', function (Blueprint $table) {
            if (! $this->indexExists('rekrutmen_stages', 'rekrutmen_stages_pipeline_order_index')) {
                $table->index(['rekrutmen_pipeline_id', 'order_column'], 'rekrutmen_stages_pipeline_order_index');
            }
        });

        Schema::table('rekrutmen_job_application_histories', function (Blueprint $table) {
            if (! $this->indexExists('rekrutmen_job_application_histories', 'rekrutmen_histories_app_created_index')) {
                $table->index(['job_application_id', 'created_at'], 'rekrutmen_histories_app_created_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('rekrutmen_job_applications', function (Blueprint $table) {
            if ($this->indexExists('rekrutmen_job_applications', 'rekrutmen_job_apps_posting_status_index')) {
                $table->dropIndex('rekrutmen_job_apps_posting_status_index');
            }
            if ($this->indexExists('rekrutmen_job_applications', 'rekrutmen_job_apps_stage_index')) {
                $table->dropIndex('rekrutmen_job_apps_stage_index');
            }
        });

        Schema::table('rekrutmen_stages', function (Blueprint $table) {
            if ($this->indexExists('rekrutmen_stages', 'rekrutmen_stages_pipeline_order_index')) {
                $table->dropIndex('rekrutmen_stages_pipeline_order_index');
            }
        });

        Schema::table('rekrutmen_job_application_histories', function (Blueprint $table) {
            if ($this->indexExists('rekrutmen_job_application_histories', 'rekrutmen_histories_app_created_index')) {
                $table->dropIndex('rekrutmen_histories_app_created_index');
            }
        });
    }
};
