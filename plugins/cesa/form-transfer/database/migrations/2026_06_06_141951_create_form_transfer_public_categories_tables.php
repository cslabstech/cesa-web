<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const ASSIGNMENTS_TABLE = 'form_transfer_public_category_assignments';

    private const ASSIGNMENT_FORM_TRANSFER_FK = 'ft_pubcat_assign_form_transfer_fk';

    private const ASSIGNMENT_PUBLIC_CATEGORY_FK = 'ft_pubcat_assign_public_category_fk';

    public function up(): void
    {
        if (! Schema::hasTable('form_transfers')) {
            return;
        }

        if (! Schema::hasTable('form_transfer_public_categories')) {
            Schema::create('form_transfer_public_categories', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();

                $table->index(['is_active', 'sort_order'], 'form_transfer_public_categories_active_sort_index');
            });
        }

        if (! Schema::hasTable(self::ASSIGNMENTS_TABLE)) {
            Schema::create(self::ASSIGNMENTS_TABLE, function (Blueprint $table): void {
                $table->id();
                $table->foreignId('form_transfer_id');
                $table->foreignId('form_transfer_public_category_id');
                $table->timestamps();

                $table->unique(
                    ['form_transfer_id', 'form_transfer_public_category_id'],
                    'form_transfer_public_category_assignment_unique'
                );
                $table->foreign('form_transfer_id', self::ASSIGNMENT_FORM_TRANSFER_FK)
                    ->references('id')
                    ->on('form_transfers')
                    ->cascadeOnDelete();
                $table->foreign('form_transfer_public_category_id', self::ASSIGNMENT_PUBLIC_CATEGORY_FK)
                    ->references('id')
                    ->on('form_transfer_public_categories')
                    ->cascadeOnDelete();
            });
        }

        $this->ensureAssignmentForeignKeys();

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

        $categoryIdsBySlug = [
            'transfer-requests' => $transferRequestsCategoryId,
            'afiliasi'          => $affiliatesCategoryId,
        ];

        $hasJsonSlugs = Schema::hasColumn('form_transfers', 'public_index_slugs');

        DB::table('form_transfers')
            ->orderBy('id')
            ->chunkById(100, function ($formTransfers) use (&$categoryIdsBySlug, $hasJsonSlugs): void {
                foreach ($formTransfers as $formTransfer) {
                    $slugs = [];

                    if ($hasJsonSlugs && filled($formTransfer->public_index_slugs ?? null)) {
                        $decodedSlugs = json_decode((string) $formTransfer->public_index_slugs, true);

                        if (is_array($decodedSlugs)) {
                            $slugs = array_merge($slugs, $decodedSlugs);
                        }
                    }

                    if ((bool) ($formTransfer->show_on_transfer_request_index ?? false)) {
                        $slugs[] = 'transfer-requests';
                    }

                    if ((bool) ($formTransfer->show_on_affiliate_index ?? false)) {
                        $slugs[] = 'afiliasi';
                    }

                    foreach ($this->normalizeSlugs($slugs) as $slug) {
                        $categoryIdsBySlug[$slug] ??= $this->upsertCategory(
                            name: str($slug)->replace('-', ' ')->headline()->toString(),
                            slug: $slug,
                            sortOrder: 100,
                        );

                        DB::table('form_transfer_public_category_assignments')->updateOrInsert([
                            'form_transfer_id'                 => $formTransfer->id,
                            'form_transfer_public_category_id' => $categoryIdsBySlug[$slug],
                        ], [
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists(self::ASSIGNMENTS_TABLE);
        Schema::dropIfExists('form_transfer_public_categories');
    }

    private function ensureAssignmentForeignKeys(): void
    {
        if (! Schema::hasTable(self::ASSIGNMENTS_TABLE)) {
            return;
        }

        $foreignKeys = collect(Schema::getForeignKeys(self::ASSIGNMENTS_TABLE));

        $hasFormTransferForeignKey = $foreignKeys->contains(
            fn (array $foreignKey): bool => in_array('form_transfer_id', $foreignKey['columns'] ?? [], true)
        );
        $hasPublicCategoryForeignKey = $foreignKeys->contains(
            fn (array $foreignKey): bool => in_array('form_transfer_public_category_id', $foreignKey['columns'] ?? [], true)
        );

        if ($hasFormTransferForeignKey && $hasPublicCategoryForeignKey) {
            return;
        }

        Schema::table(self::ASSIGNMENTS_TABLE, function (Blueprint $table) use ($hasFormTransferForeignKey, $hasPublicCategoryForeignKey): void {
            if (! $hasFormTransferForeignKey) {
                $table->foreign('form_transfer_id', self::ASSIGNMENT_FORM_TRANSFER_FK)
                    ->references('id')
                    ->on('form_transfers')
                    ->cascadeOnDelete();
            }

            if (! $hasPublicCategoryForeignKey) {
                $table->foreign('form_transfer_public_category_id', self::ASSIGNMENT_PUBLIC_CATEGORY_FK)
                    ->references('id')
                    ->on('form_transfer_public_categories')
                    ->cascadeOnDelete();
            }
        });
    }

    private function upsertCategory(string $name, string $slug, int $sortOrder): int
    {
        $existingId = DB::table('form_transfer_public_categories')
            ->where('slug', $slug)
            ->value('id');

        if (is_numeric($existingId)) {
            DB::table('form_transfer_public_categories')
                ->where('id', $existingId)
                ->update([
                    'name'       => $name,
                    'sort_order' => $sortOrder,
                    'is_active'  => true,
                    'updated_at' => now(),
                ]);

            return (int) $existingId;
        }

        return (int) DB::table('form_transfer_public_categories')->insertGetId([
            'name'       => $name,
            'slug'       => $slug,
            'sort_order' => $sortOrder,
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  array<int, mixed>  $slugs
     * @return array<int, string>
     */
    private function normalizeSlugs(array $slugs): array
    {
        return collect($slugs)
            ->map(fn (mixed $slug): string => str((string) $slug)
                ->trim()
                ->lower()
                ->replace('_', '-')
                ->slug('-')
                ->toString())
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
};
