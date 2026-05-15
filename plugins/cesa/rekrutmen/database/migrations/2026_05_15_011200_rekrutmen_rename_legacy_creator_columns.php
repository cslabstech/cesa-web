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
        'rekrutmen_approvers',
        'rekrutmen_divisions',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            $this->renameCreatorColumn($table, 'created_by', 'creator_id');
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->tables) as $table) {
            $this->renameCreatorColumn($table, 'creator_id', 'created_by');
        }
    }

    private function renameCreatorColumn(string $tableName, string $from, string $to): void
    {
        if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, $from)) {
            return;
        }

        if (Schema::hasColumn($tableName, $to)) {
            DB::table($tableName)
                ->whereNull($to)
                ->whereNotNull($from)
                ->update([$to => DB::raw($from)]);

            $this->dropForeignIfExists($tableName, $from);

            Schema::table($tableName, function (Blueprint $table) use ($from): void {
                $table->dropColumn($from);
            });

            $this->addUserForeignIfPossible($tableName, $to);

            return;
        }

        $this->dropForeignIfExists($tableName, $from);

        Schema::table($tableName, function (Blueprint $table) use ($from, $to): void {
            $table->renameColumn($from, $to);
        });

        $this->addUserForeignIfPossible($tableName, $to);
    }

    private function dropForeignIfExists(string $tableName, string $column): void
    {
        try {
            Schema::table($tableName, function (Blueprint $table) use ($column): void {
                $table->dropForeign([$column]);
            });
        } catch (Throwable) {
        }
    }

    private function addUserForeignIfPossible(string $tableName, string $column): void
    {
        try {
            Schema::table($tableName, function (Blueprint $table) use ($column): void {
                $table->foreign($column)->references('id')->on('users')->nullOnDelete();
            });
        } catch (Throwable) {
        }
    }
};
