<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<int, string>
     */
    private array $tables = [
        'form_transfer_banks',
        'form_transfer_divisions',
        'form_transfer_reference_notes',
        'form_transfer_approval_workflows',
        'form_transfer_request_realizations',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            $this->addCreatorColumn($table);
        }

        $this->backfillFromUser('form_transfer_request_realizations', 'user_id');
    }

    public function down(): void
    {
        foreach (array_reverse($this->tables) as $table) {
            $this->dropCreatorColumn($table);
        }
    }

    private function addCreatorColumn(string $tableName): void
    {
        if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'creator_id')) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table): void {
            $table->foreignId('creator_id')
                ->nullable()
                ->after('id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    private function dropCreatorColumn(string $tableName): void
    {
        if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'creator_id')) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table): void {
            $table->dropConstrainedForeignId('creator_id');
        });
    }

    private function backfillFromUser(string $tableName, string $userColumn): void
    {
        if (
            ! Schema::hasTable($tableName)
            || ! Schema::hasColumn($tableName, 'creator_id')
            || ! Schema::hasColumn($tableName, $userColumn)
        ) {
            return;
        }

        DB::table($tableName)
            ->whereNull('creator_id')
            ->whereNotNull($userColumn)
            ->update(['creator_id' => DB::raw($userColumn)]);
    }
};
