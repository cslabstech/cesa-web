<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Relaticle\Flowforge\Services\DecimalPosition;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rekrutmen_job_applications', function (Blueprint $table) {
            $table->decimal('position', 20, 10)->nullable()->after('current_stage_id');
        });

        DB::table('rekrutmen_stages')->pluck('id')->each(function (int $stageId) {
            $position = DecimalPosition::forEmptyColumn();

            DB::table('rekrutmen_job_applications')
                ->where('current_stage_id', $stageId)
                ->whereNull('position')
                ->orderBy('created_at')
                ->chunkById(500, function ($applications) use (&$position) {
                    foreach ($applications as $app) {
                        DB::table('rekrutmen_job_applications')
                            ->where('id', $app->id)
                            ->update(['position' => $position]);

                        $position = DecimalPosition::after($position);
                    }
                });
        });
    }

    public function down(): void
    {
        Schema::table('rekrutmen_job_applications', function (Blueprint $table) {
            $table->dropColumn('position');
        });
    }
};
