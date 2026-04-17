<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('rekrutmen_approvers', 'approval_order')) {
            Schema::table('rekrutmen_approvers', function (Blueprint $table): void {
                $table->unsignedInteger('approval_order')
                    ->default(1)
                    ->after('division_id');

                $table->index('approval_order');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('rekrutmen_approvers', 'approval_order')) {
            return;
        }

        Schema::table('rekrutmen_approvers', function (Blueprint $table): void {
            $table->dropIndex(['approval_order']);
            $table->dropColumn('approval_order');
        });
    }
};
