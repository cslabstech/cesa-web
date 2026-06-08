<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CATEGORIES_TABLE = 'form_transfer_public_categories';

    private const ASSIGNMENTS_TABLE = 'form_transfer_public_category_assignments';

    public function up(): void
    {
        if (
            ! Schema::hasTable('form_transfers')
            || ! Schema::hasTable(self::CATEGORIES_TABLE)
            || ! Schema::hasTable(self::ASSIGNMENTS_TABLE)
        ) {
            return;
        }

        $transferRequestsCategoryId = $this->upsertCategory(
            name: 'Permintaan Transfer',
            slug: 'transfer-requests',
            sortOrder: 10,
        );
        $affiliatesCategoryId = $this->upsertCategory(
            name: 'Afiliasi',
            slug: 'afiliasi',
            sortOrder: 20,
        );

        DB::table('form_transfers')
            ->select(['id', 'show_on_transfer_request_index', 'show_on_affiliate_index'])
            ->orderBy('id')
            ->chunkById(100, function ($formTransfers) use ($transferRequestsCategoryId, $affiliatesCategoryId): void {
                foreach ($formTransfers as $formTransfer) {
                    if ((bool) ($formTransfer->show_on_transfer_request_index ?? false)) {
                        $this->attachCategory((int) $formTransfer->id, $transferRequestsCategoryId);
                    }

                    if ((bool) ($formTransfer->show_on_affiliate_index ?? false)) {
                        $this->attachCategory((int) $formTransfer->id, $affiliatesCategoryId);
                    }
                }
            });
    }

    public function down(): void {}

    private function upsertCategory(string $name, string $slug, int $sortOrder): int
    {
        $existingId = DB::table(self::CATEGORIES_TABLE)
            ->where('slug', $slug)
            ->value('id');

        if (is_numeric($existingId)) {
            DB::table(self::CATEGORIES_TABLE)
                ->where('id', $existingId)
                ->update([
                    'name'       => $name,
                    'sort_order' => $sortOrder,
                    'is_active'  => true,
                    'updated_at' => now(),
                ]);

            return (int) $existingId;
        }

        return (int) DB::table(self::CATEGORIES_TABLE)->insertGetId([
            'name'       => $name,
            'slug'       => $slug,
            'sort_order' => $sortOrder,
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function attachCategory(int $formTransferId, int $categoryId): void
    {
        $attributes = [
            'form_transfer_id'                 => $formTransferId,
            'form_transfer_public_category_id' => $categoryId,
        ];

        if (
            DB::table(self::ASSIGNMENTS_TABLE)
                ->where($attributes)
                ->exists()
        ) {
            DB::table(self::ASSIGNMENTS_TABLE)
                ->where($attributes)
                ->update(['updated_at' => now()]);

            return;
        }

        DB::table(self::ASSIGNMENTS_TABLE)->insert([
            ...$attributes,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
