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
        Schema::table('rekrutmen_job_postings', function (Blueprint $table): void {
            if (! $this->indexExists('rekrutmen_job_postings', 'rekrutmen_job_postings_public_listing_index')) {
                $table->index(
                    ['is_published', 'deleted_at', 'created_at'],
                    'rekrutmen_job_postings_public_listing_index'
                );
            }

            if (! $this->indexExists('rekrutmen_job_postings', 'rekrutmen_job_postings_closing_date_index')) {
                $table->index('closing_date', 'rekrutmen_job_postings_closing_date_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('rekrutmen_job_postings', function (Blueprint $table): void {
            if ($this->indexExists('rekrutmen_job_postings', 'rekrutmen_job_postings_public_listing_index')) {
                $table->dropIndex('rekrutmen_job_postings_public_listing_index');
            }

            if ($this->indexExists('rekrutmen_job_postings', 'rekrutmen_job_postings_closing_date_index')) {
                $table->dropIndex('rekrutmen_job_postings_closing_date_index');
            }
        });
    }
};
