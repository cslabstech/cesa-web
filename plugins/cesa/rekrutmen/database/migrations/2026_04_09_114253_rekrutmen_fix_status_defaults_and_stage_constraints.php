<?php

use Cesa\Rekrutmen\Enums\JobApplicationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('rekrutmen_job_applications')
            ->where(function ($query): void {
                $query->whereNull('status')
                    ->orWhereNotIn('status', array_map(
                        static fn (JobApplicationStatus $status): string => $status->value,
                        JobApplicationStatus::cases(),
                    ));
            })
            ->update([
                'status' => JobApplicationStatus::IN_PROGRESS->value,
            ]);

        $this->normalizeStageOrderColumns();

        Schema::table('rekrutmen_job_applications', function (Blueprint $table): void {
            $table->string('status')
                ->default(JobApplicationStatus::IN_PROGRESS->value)
                ->change();
        });

        if (! Schema::hasIndex('rekrutmen_stages', 'rekrutmen_stages_pipeline_order_unique')) {
            Schema::table('rekrutmen_stages', function (Blueprint $table): void {
                $table->unique(
                    ['rekrutmen_pipeline_id', 'order_column'],
                    'rekrutmen_stages_pipeline_order_unique'
                );
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('rekrutmen_stages', 'rekrutmen_stages_pipeline_order_unique')) {
            Schema::table('rekrutmen_stages', function (Blueprint $table): void {
                $table->dropUnique('rekrutmen_stages_pipeline_order_unique');
            });
        }

        Schema::table('rekrutmen_job_applications', function (Blueprint $table): void {
            $table->string('status')
                ->default('new')
                ->change();
        });
    }

    private function normalizeStageOrderColumns(): void
    {
        DB::table('rekrutmen_stages')
            ->select('rekrutmen_pipeline_id')
            ->distinct()
            ->orderBy('rekrutmen_pipeline_id')
            ->pluck('rekrutmen_pipeline_id')
            ->each(function (int $pipelineId): void {
                $nextOrder = 1;

                DB::table('rekrutmen_stages')
                    ->where('rekrutmen_pipeline_id', $pipelineId)
                    ->orderBy('order_column')
                    ->orderBy('id')
                    ->get(['id'])
                    ->each(function (object $stage) use (&$nextOrder): void {
                        DB::table('rekrutmen_stages')
                            ->where('id', $stage->id)
                            ->update([
                                'order_column' => $nextOrder,
                            ]);

                        $nextOrder++;
                    });
            });
    }
};
