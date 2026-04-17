<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('rekrutmen_request_man_powers', 'division_id')) {
            Schema::table('rekrutmen_request_man_powers', function (Blueprint $table): void {
                $table->foreignId('division_id')
                    ->nullable()
                    ->after('company_id')
                    ->constrained('rekrutmen_divisions')
                    ->nullOnDelete();

                $table->index('division_id');
            });
        }

        if (! Schema::hasColumn('rekrutmen_approvers', 'division_id')) {
            Schema::table('rekrutmen_approvers', function (Blueprint $table): void {
                $table->foreignId('division_id')
                    ->nullable()
                    ->after('company_id')
                    ->constrained('rekrutmen_divisions')
                    ->nullOnDelete();

                $table->index('division_id');
            });
        }

        $this->backfillRequestDivisions();
        $this->backfillApproverDivisions();
    }

    public function down(): void
    {
        Schema::table('rekrutmen_approvers', function (Blueprint $table): void {
            $table->dropIndex(['division_id']);
            $table->dropConstrainedForeignId('division_id');
        });

        Schema::table('rekrutmen_request_man_powers', function (Blueprint $table): void {
            $table->dropIndex(['division_id']);
            $table->dropConstrainedForeignId('division_id');
        });
    }

    private function backfillRequestDivisions(): void
    {
        DB::table('rekrutmen_request_man_powers')
            ->select('id', 'company_id', 'divisi')
            ->orderBy('id')
            ->get()
            ->each(function (object $request): void {
                $divisionId = $this->resolveDivisionId($request->company_id, $request->divisi);

                if ($divisionId === null) {
                    return;
                }

                DB::table('rekrutmen_request_man_powers')
                    ->where('id', $request->id)
                    ->update([
                        'division_id' => $divisionId,
                    ]);
            });
    }

    private function backfillApproverDivisions(): void
    {
        DB::table('rekrutmen_approvers')
            ->select('id', 'company_id', 'divisi')
            ->orderBy('id')
            ->get()
            ->each(function (object $approver): void {
                $divisionId = $this->resolveDivisionId($approver->company_id, $approver->divisi);

                if ($divisionId === null) {
                    return;
                }

                DB::table('rekrutmen_approvers')
                    ->where('id', $approver->id)
                    ->update([
                        'division_id' => $divisionId,
                    ]);
            });
    }

    private function resolveDivisionId(mixed $companyId, mixed $division): ?int
    {
        if (! is_string($division) || trim($division) === '') {
            return null;
        }

        $query = DB::table('rekrutmen_divisions')
            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($division))]);

        if (is_numeric($companyId)) {
            $query->where('company_id', (int) $companyId);
        } else {
            $query->whereNull('company_id');
        }

        $divisionId = $query
            ->orderBy('id')
            ->value('id');

        return is_numeric($divisionId) ? (int) $divisionId : null;
    }
};
