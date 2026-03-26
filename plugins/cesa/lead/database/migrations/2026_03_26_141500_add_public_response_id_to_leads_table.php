<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->string('public_response_id')->nullable()->unique()->after('phone_transaction_range');
        });

        DB::table('leads')
            ->whereNull('public_response_id')
            ->orderBy('id')
            ->lazyById(500)
            ->each(function (object $lead): void {
                DB::table('leads')
                    ->where('id', $lead->id)
                    ->update([
                        'public_response_id' => (string) Str::ulid(),
                    ]);
            });

        Schema::table('leads', function (Blueprint $table): void {
            $table->string('public_response_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->dropUnique(['public_response_id']);
            $table->dropColumn('public_response_id');
        });
    }
};
