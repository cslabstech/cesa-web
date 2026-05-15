<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('documents') || Schema::hasColumn('documents', 'creator_id')) {
            return;
        }

        Schema::table('documents', function (Blueprint $table): void {
            $table->foreignId('creator_id')
                ->nullable()
                ->after('id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('documents') || ! Schema::hasColumn('documents', 'creator_id')) {
            return;
        }

        Schema::table('documents', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('creator_id');
        });
    }
};
