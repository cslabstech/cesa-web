<?php

namespace Cesa\FormTransfer\Tests\Unit\Models;

use App\Models\User;
use Cesa\FormTransfer\Models\FormTransfer;
use Cesa\FormTransfer\Tests\FormTransferTestCase;
use Illuminate\Support\Facades\DB;
use Webkul\Security\Models\Role;
use Webkul\Security\Models\User as SecurityUser;

class SecurityUserFormTransferAccessTest extends FormTransferTestCase
{
    public function test_default_user_is_treated_as_having_global_form_transfer_access(): void
    {
        $this->artisan('migrate', [
            '--path'     => 'plugins/webkul/security/database/migrations/2025_08_21_101646_alter_users_table.php',
            '--realpath' => false,
        ]);

        $user = User::factory()->create();

        DB::table('users')
            ->where('id', $user->id)
            ->update([
                'is_default' => true,
            ]);

        $securityUser = SecurityUser::query()->findOrFail($user->id);

        $this->assertTrue($securityUser->hasAllFormTransferAccess());
    }

    public function test_security_user_can_report_global_form_transfer_access(): void
    {
        $user = User::factory()->create();

        $securityUser = SecurityUser::query()->findOrFail($user->id);

        $this->assertFalse($securityUser->hasAllFormTransferAccess());

        DB::table('users')
            ->where('id', $user->id)
            ->update([
                'has_all_form_transfer_access' => true,
            ]);

        $this->assertTrue($securityUser->fresh()->hasAllFormTransferAccess());
    }

    public function test_admin_role_is_treated_as_having_global_form_transfer_access(): void
    {
        $user = User::factory()->create();

        $adminRole = Role::query()->create([
            'name'       => (string) config('filament-shield.panel_user.name', 'Admin'),
            'guard_name' => 'web',
        ]);

        $securityUser = SecurityUser::query()->findOrFail($user->id);
        $securityUser->assignRole($adminRole);

        $this->assertTrue($securityUser->fresh()->hasAllFormTransferAccess());
    }

    public function test_form_transfer_access_migration_backfills_existing_admin_users(): void
    {
        $migration = require base_path('plugins/cesa/form-transfer/database/migrations/2026_02_05_000001_create_form_transfer_user_accesses_table.php');
        $migration->down();

        $user = User::factory()->create();

        $adminRole = Role::query()->create([
            'name'       => (string) config('filament-shield.panel_user.name', 'Admin'),
            'guard_name' => 'web',
        ]);

        $securityUser = SecurityUser::query()->findOrFail($user->id);
        $securityUser->assignRole($adminRole);

        $migration->up();

        $this->assertTrue((bool) DB::table('users')
            ->where('id', $securityUser->id)
            ->value('has_all_form_transfer_access'));
    }

    public function test_security_user_returns_assigned_form_transfer_ids(): void
    {
        $user = User::factory()->create();
        $formTransferA = FormTransfer::factory()->create();
        $formTransferB = FormTransfer::factory()->create();

        DB::table('form_transfer_user_accesses')->insert([
            [
                'user_id'          => $user->id,
                'form_transfer_id' => $formTransferB->id,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            [
                'user_id'          => $user->id,
                'form_transfer_id' => $formTransferA->id,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
        ]);

        $securityUser = SecurityUser::query()->findOrFail($user->id);

        $this->assertSame(
            [$formTransferA->id, $formTransferB->id],
            $securityUser->getAccessibleFormTransferIds()
        );
    }
}
