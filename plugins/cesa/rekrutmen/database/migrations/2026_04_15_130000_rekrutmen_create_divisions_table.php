<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rekrutmen_divisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'name']);
            $table->index('is_active');
        });

        $this->seedDefaultDivisionsFromLegacyData();
    }

    public function down(): void
    {
        Schema::dropIfExists('rekrutmen_divisions');
    }

    private function seedDefaultDivisionsFromLegacyData(): void
    {
        collect([
            'rekrutmen_request_man_powers',
            'rekrutmen_approvers',
        ])->each(function (string $table): void {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'divisi')) {
                return;
            }

            DB::table($table)
                ->select('company_id', 'divisi')
                ->whereNotNull('divisi')
                ->orderBy('company_id')
                ->orderBy('divisi')
                ->get()
                ->each(function (object $record): void {
                    $name = is_string($record->divisi) ? trim($record->divisi) : '';

                    if ($name === '') {
                        return;
                    }

                    DB::table('rekrutmen_divisions')->updateOrInsert(
                        [
                            'company_id' => is_numeric($record->company_id) ? (int) $record->company_id : null,
                            'name'       => $name,
                        ],
                        [
                            'is_active'  => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ],
                    );
                });
        });
    }
};
