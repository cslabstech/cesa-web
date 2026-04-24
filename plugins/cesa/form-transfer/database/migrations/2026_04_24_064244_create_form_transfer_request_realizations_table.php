<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('form_transfer_requests')) {
            return;
        }

        if (! Schema::hasColumn('form_transfer_requests', 'realized_amount')) {
            Schema::table('form_transfer_requests', function (Blueprint $table): void {
                $table->decimal('realized_amount', 18, 2)
                    ->default(0)
                    ->after('transfer_amount');
            });
        }

        if (! Schema::hasTable('form_transfer_request_realizations')) {
            Schema::create('form_transfer_request_realizations', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('transfer_request_id')
                    ->constrained('form_transfer_requests')
                    ->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->decimal('amount', 18, 2);
                $table->date('realized_at')->nullable();
                $table->string('proof_path')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['transfer_request_id', 'realized_at'], 'form_transfer_realizations_request_date_index');
            });
        }

        DB::table('form_transfer_requests')
            ->where('realization_status', 'done')
            ->update([
                'realized_amount' => DB::raw('transfer_amount'),
            ]);

        DB::table('form_transfer_requests')
            ->where('realization_status', 'done')
            ->chunkById(100, function ($requests): void {
                $now = now();

                foreach ($requests as $request) {
                    $exists = DB::table('form_transfer_request_realizations')
                        ->where('transfer_request_id', $request->id)
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    DB::table('form_transfer_request_realizations')->insert([
                        'transfer_request_id' => $request->id,
                        'user_id'             => $request->user_id,
                        'amount'              => $request->transfer_amount,
                        'realized_at'         => $request->realized_at,
                        'proof_path'          => $request->realization_proof_path,
                        'notes'               => $request->realization_notes,
                        'created_at'          => $now,
                        'updated_at'          => $now,
                    ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_transfer_request_realizations');

        if (Schema::hasColumn('form_transfer_requests', 'realized_amount')) {
            Schema::table('form_transfer_requests', function (Blueprint $table): void {
                $table->dropColumn('realized_amount');
            });
        }
    }
};
