<?php

namespace Cesa\FormTransfer\Tests\Feature\Resources;

use App\Models\User;
use Cesa\FormTransfer\Filament\Resources\TransferRequestResource;
use Cesa\FormTransfer\Models\FormTransfer;
use Cesa\FormTransfer\Models\TransferRequest;
use Cesa\FormTransfer\Tests\FormTransferTestCase;
use Illuminate\Support\Facades\Schema;
use Webkul\Security\Models\Role;
use Webkul\Security\Models\User as SecurityUser;

class TransferRequestResourceAccessTest extends FormTransferTestCase
{
    public function test_resource_query_skips_access_filter_when_access_table_is_unavailable(): void
    {
        $user = User::factory()->create();

        $this->actingAs(SecurityUser::query()->findOrFail($user->id));

        Schema::drop('form_transfer_user_accesses');

        $sql = TransferRequestResource::getEloquentQuery()->toSql();

        $this->assertStringNotContainsString('1 = 0', $sql);
    }

    public function test_resource_query_skips_access_filter_for_admin_role_users(): void
    {
        $user = User::factory()->create();

        $adminRole = Role::query()->create([
            'name'       => (string) config('filament-shield.panel_user.name', 'Admin'),
            'guard_name' => 'web',
        ]);

        $securityUser = SecurityUser::query()->findOrFail($user->id);
        $securityUser->assignRole($adminRole);

        $this->actingAs($securityUser->fresh());

        $sql = TransferRequestResource::getEloquentQuery()->toSql();

        $this->assertStringNotContainsString('1 = 0', $sql);
    }

    public function test_resource_query_keeps_unassigned_form_transfers_accessible(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $openFormTransfer = FormTransfer::factory()->create();
        $restrictedFormTransfer = FormTransfer::factory()->create();

        $restrictedFormTransfer->allowedUsers()->attach($otherUser->id);

        TransferRequest::factory()->create([
            'form_transfer_id' => $openFormTransfer->id,
        ]);

        TransferRequest::factory()->create([
            'form_transfer_id' => $restrictedFormTransfer->id,
        ]);

        $this->actingAs(SecurityUser::query()->findOrFail($user->id));

        $visibleFormTransferIds = TransferRequestResource::getEloquentQuery()
            ->orderBy('form_transfer_id')
            ->pluck('form_transfer_id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();

        $this->assertSame([$openFormTransfer->id], $visibleFormTransferIds);
    }
}
