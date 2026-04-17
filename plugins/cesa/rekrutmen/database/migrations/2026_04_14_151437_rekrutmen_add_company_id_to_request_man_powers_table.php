<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rekrutmen_request_man_powers', function (Blueprint $table): void {
            $table->foreignId('company_id')
                ->nullable()
                ->after('email_address')
                ->constrained('companies')
                ->nullOnDelete();

            $table->index('company_id');
        });

        DB::table('rekrutmen_request_man_powers')
            ->whereNotNull('badan_usaha')
            ->orderBy('id')
            ->get(['id', 'badan_usaha'])
            ->each(function (object $request): void {
                $name = trim((string) $request->badan_usaha);

                if ($name === '') {
                    return;
                }

                $companyId = DB::table('companies')
                    ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])
                    ->value('id');

                if (! is_numeric($companyId)) {
                    return;
                }

                DB::table('rekrutmen_request_man_powers')
                    ->where('id', $request->id)
                    ->update([
                        'company_id' => (int) $companyId,
                    ]);
            });

        $unmappedBusinessEntities = DB::table('rekrutmen_request_man_powers')
            ->whereNull('company_id')
            ->select('id', 'badan_usaha')
            ->orderBy('id')
            ->get()
            ->map(fn (object $request): string => sprintf(
                '#%d (%s)',
                $request->id,
                trim((string) $request->badan_usaha) !== '' ? $request->badan_usaha : 'EMPTY'
            ))
            ->all();

        if ($unmappedBusinessEntities !== []) {
            throw new RuntimeException(
                'Unable to migrate Request Man Power business entities to company_id. Unmapped rows: '.implode(', ', $unmappedBusinessEntities)
            );
        }

        Schema::table('rekrutmen_request_man_powers', function (Blueprint $table): void {
            $table->dropColumn('badan_usaha');
        });
    }

    public function down(): void
    {
        Schema::table('rekrutmen_request_man_powers', function (Blueprint $table): void {
            $table->string('badan_usaha')->nullable()->after('level_pekerjaan');
        });

        DB::table('rekrutmen_request_man_powers')
            ->whereNotNull('company_id')
            ->orderBy('id')
            ->get(['id', 'company_id'])
            ->each(function (object $request): void {
                $companyName = DB::table('companies')
                    ->where('id', $request->company_id)
                    ->value('name');

                DB::table('rekrutmen_request_man_powers')
                    ->where('id', $request->id)
                    ->update([
                        'badan_usaha' => $companyName,
                    ]);
            });

        Schema::table('rekrutmen_request_man_powers', function (Blueprint $table): void {
            $table->dropIndex(['company_id']);
            $table->dropConstrainedForeignId('company_id');
        });
    }
};
