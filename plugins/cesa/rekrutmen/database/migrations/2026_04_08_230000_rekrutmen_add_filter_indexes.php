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
        Schema::table('rekrutmen_request_man_powers', function (Blueprint $table) {
            if (! $this->indexExists('rekrutmen_request_man_powers', 'rekrutmen_request_man_powers_divisi_index')) {
                $table->index('divisi', 'rekrutmen_request_man_powers_divisi_index');
            }
        });

        Schema::table('rekrutmen_job_postings', function (Blueprint $table) {
            if (! $this->indexExists('rekrutmen_job_postings', 'rekrutmen_job_postings_request_man_power_index')) {
                $table->index('request_man_power_id', 'rekrutmen_job_postings_request_man_power_index');
            }
            if (! $this->indexExists('rekrutmen_job_postings', 'rekrutmen_job_postings_pipeline_index')) {
                $table->index('rekrutmen_pipeline_id', 'rekrutmen_job_postings_pipeline_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('rekrutmen_request_man_powers', function (Blueprint $table) {
            if ($this->indexExists('rekrutmen_request_man_powers', 'rekrutmen_request_man_powers_divisi_index')) {
                $table->dropIndex('rekrutmen_request_man_powers_divisi_index');
            }
        });

        Schema::table('rekrutmen_job_postings', function (Blueprint $table) {
            if ($this->indexExists('rekrutmen_job_postings', 'rekrutmen_job_postings_request_man_power_index')) {
                $table->dropIndex('rekrutmen_job_postings_request_man_power_index');
            }
            if ($this->indexExists('rekrutmen_job_postings', 'rekrutmen_job_postings_pipeline_index')) {
                $table->dropIndex('rekrutmen_job_postings_pipeline_index');
            }
        });
    }
};
