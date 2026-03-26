<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create pivot table for user <-> form_transfer access
        Schema::create('form_transfer_user_accesses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('form_transfer_id')->constrained('form_transfers')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'form_transfer_id'], 'form_transfer_user_access_unique');
        });

        // Add has_all_form_transfer_access column to users table
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('has_all_form_transfer_access')->default(false)->after('resource_permission');
        });

        // Bootstrap existing default and admin-role users with all-access.
        $fullAccessUserIds = collect();

        if (Schema::hasColumn('users', 'is_default')) {
            $fullAccessUserIds = $fullAccessUserIds->merge(
                DB::table('users')
                    ->where('is_default', true)
                    ->pluck('id')
            );
        }

        $rolesTable = config('permission.table_names.roles', 'roles');
        $modelHasRolesTable = config('permission.table_names.model_has_roles', 'model_has_roles');

        if (Schema::hasTable($rolesTable) && Schema::hasTable($modelHasRolesTable)) {
            $adminRoleNames = collect([
                config('filament-shield.panel_user.name'),
                config('filament-shield.super_admin.name'),
                'admin',
                'panel_user',
                'super_admin',
            ])
                ->filter(fn (mixed $name): bool => is_string($name) && $name !== '')
                ->map(fn (string $name): string => Str::of($name)->trim()->lower()->toString())
                ->unique()
                ->values()
                ->all();

            $fullAccessUserIds = $fullAccessUserIds->merge(
                DB::table($modelHasRolesTable)
                    ->join($rolesTable, "{$rolesTable}.id", '=', "{$modelHasRolesTable}.role_id")
                    ->where("{$modelHasRolesTable}.model_type", 'Webkul\\Security\\Models\\User')
                    ->where(function ($query) use ($rolesTable, $adminRoleNames): void {
                        foreach ($adminRoleNames as $index => $adminRoleName) {
                            $method = $index === 0 ? 'whereRaw' : 'orWhereRaw';

                            $query->{$method}("lower({$rolesTable}.name) = ?", [$adminRoleName]);
                        }

                        $query->orWhereRaw("lower({$rolesTable}.name) like ?", ['%admin%']);
                    })
                    ->pluck("{$modelHasRolesTable}.model_id")
            );
        }

        $fullAccessUserIds = $fullAccessUserIds
            ->map(static fn (mixed $userId): int => (int) $userId)
            ->unique()
            ->values();

        if ($fullAccessUserIds->isNotEmpty()) {
            DB::table('users')
                ->whereIn('id', $fullAccessUserIds->all())
                ->update(['has_all_form_transfer_access' => true]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_transfer_user_accesses');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('has_all_form_transfer_access');
        });
    }
};
