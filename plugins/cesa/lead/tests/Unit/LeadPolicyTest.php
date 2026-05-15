<?php

namespace Cesa\Lead\Tests\Unit;

use Cesa\Lead\Models\Lead;
use Cesa\Lead\Policies\LeadPolicy;
use Cesa\Lead\Tests\TestCase;
use Spatie\Permission\Models\Permission;
use Webkul\Security\Enums\PermissionType;
use Webkul\Security\Models\User;

class LeadPolicyTest extends TestCase
{
    protected LeadPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new LeadPolicy;
    }

    public function test_view_any_allows_user_with_permission(): void
    {
        $user = User::factory()->create();
        Permission::create(['name' => 'view_any_lead_lead', 'guard_name' => 'web']);
        $user->givePermissionTo('view_any_lead_lead');

        $this->assertTrue($this->policy->viewAny($user));
    }

    public function test_view_any_denies_user_without_permission(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($this->policy->viewAny($user));
    }

    public function test_view_allows_user_with_permission(): void
    {
        $user = User::factory()->create([
            'resource_permission' => PermissionType::GLOBAL->value,
        ]);
        $lead = Lead::factory()->create();
        Permission::create(['name' => 'view_lead_lead', 'guard_name' => 'web']);
        $user->givePermissionTo('view_lead_lead');

        $this->assertTrue($this->policy->view($user, $lead));
    }

    public function test_view_denies_user_without_permission(): void
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->create();

        $this->assertFalse($this->policy->view($user, $lead));
    }

    public function test_view_denies_individual_user_with_permission_but_no_scope_access(): void
    {
        $user = User::factory()->create([
            'resource_permission' => PermissionType::INDIVIDUAL->value,
        ]);
        $lead = Lead::factory()->create();
        Permission::create(['name' => 'view_lead_lead', 'guard_name' => 'web']);
        $user->givePermissionTo('view_lead_lead');

        $this->assertFalse($this->policy->view($user, $lead));
    }

    public function test_view_allows_individual_creator_with_permission(): void
    {
        $user = User::factory()->create([
            'resource_permission' => PermissionType::INDIVIDUAL->value,
        ]);
        $lead = Lead::factory()->create([
            'creator_id' => $user->getKey(),
        ]);
        Permission::create(['name' => 'view_lead_lead', 'guard_name' => 'web']);
        $user->givePermissionTo('view_lead_lead');

        $this->assertTrue($this->policy->view($user, $lead));
    }

    public function test_create_allows_user_with_permission(): void
    {
        $user = User::factory()->create();
        Permission::create(['name' => 'create_lead_lead', 'guard_name' => 'web']);
        $user->givePermissionTo('create_lead_lead');

        $this->assertTrue($this->policy->create($user));
    }

    public function test_create_denies_user_without_permission(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($this->policy->create($user));
    }

    public function test_update_denies_user_without_permission(): void
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->create();

        $this->assertFalse($this->policy->update($user, $lead));
    }

    public function test_update_allows_user_with_permission_and_global_access(): void
    {
        $user = User::factory()->create([
            'resource_permission' => PermissionType::GLOBAL->value,
        ]);
        $lead = Lead::factory()->create();
        Permission::create(['name' => 'update_lead_lead', 'guard_name' => 'web']);
        $user->givePermissionTo('update_lead_lead');

        $this->assertTrue($this->policy->update($user, $lead));
    }

    public function test_update_denies_user_with_permission_but_no_scope_access(): void
    {
        $user = User::factory()->create([
            'resource_permission' => PermissionType::INDIVIDUAL->value,
        ]);
        $lead = Lead::factory()->create();
        Permission::create(['name' => 'update_lead_lead', 'guard_name' => 'web']);
        $user->givePermissionTo('update_lead_lead');

        $this->assertFalse($this->policy->update($user, $lead));
    }

    public function test_delete_denies_user_without_permission(): void
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->create();

        $this->assertFalse($this->policy->delete($user, $lead));
    }

    public function test_delete_allows_user_with_permission_and_global_access(): void
    {
        $user = User::factory()->create([
            'resource_permission' => PermissionType::GLOBAL->value,
        ]);
        $lead = Lead::factory()->create();
        Permission::create(['name' => 'delete_lead_lead', 'guard_name' => 'web']);
        $user->givePermissionTo('delete_lead_lead');

        $this->assertTrue($this->policy->delete($user, $lead));
    }

    public function test_delete_denies_user_with_permission_but_no_scope_access(): void
    {
        $user = User::factory()->create([
            'resource_permission' => PermissionType::INDIVIDUAL->value,
        ]);
        $lead = Lead::factory()->create();
        Permission::create(['name' => 'delete_lead_lead', 'guard_name' => 'web']);
        $user->givePermissionTo('delete_lead_lead');

        $this->assertFalse($this->policy->delete($user, $lead));
    }

    public function test_delete_any_allows_user_with_permission(): void
    {
        $user = User::factory()->create();
        Permission::create(['name' => 'delete_any_lead_lead', 'guard_name' => 'web']);
        $user->givePermissionTo('delete_any_lead_lead');

        $this->assertTrue($this->policy->deleteAny($user));
    }

    public function test_delete_any_denies_user_without_permission(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($this->policy->deleteAny($user));
    }

    public function test_force_delete_denies_user_without_permission(): void
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->create();

        $this->assertFalse($this->policy->forceDelete($user, $lead));
    }

    public function test_force_delete_allows_user_with_permission_and_global_access(): void
    {
        $user = User::factory()->create([
            'resource_permission' => PermissionType::GLOBAL->value,
        ]);
        $lead = Lead::factory()->create();
        Permission::create(['name' => 'force_delete_lead_lead', 'guard_name' => 'web']);
        $user->givePermissionTo('force_delete_lead_lead');

        $this->assertTrue($this->policy->forceDelete($user, $lead));
    }

    public function test_force_delete_denies_user_with_permission_but_no_scope_access(): void
    {
        $user = User::factory()->create([
            'resource_permission' => PermissionType::INDIVIDUAL->value,
        ]);
        $lead = Lead::factory()->create();
        Permission::create(['name' => 'force_delete_lead_lead', 'guard_name' => 'web']);
        $user->givePermissionTo('force_delete_lead_lead');

        $this->assertFalse($this->policy->forceDelete($user, $lead));
    }

    public function test_force_delete_any_allows_user_with_permission(): void
    {
        $user = User::factory()->create();
        Permission::create(['name' => 'force_delete_any_lead_lead', 'guard_name' => 'web']);
        $user->givePermissionTo('force_delete_any_lead_lead');

        $this->assertTrue($this->policy->forceDeleteAny($user));
    }

    public function test_force_delete_any_denies_user_without_permission(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($this->policy->forceDeleteAny($user));
    }

    public function test_restore_denies_user_without_permission(): void
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->create();

        $this->assertFalse($this->policy->restore($user, $lead));
    }

    public function test_restore_allows_user_with_permission_and_global_access(): void
    {
        $user = User::factory()->create([
            'resource_permission' => PermissionType::GLOBAL->value,
        ]);
        $lead = Lead::factory()->create();
        Permission::create(['name' => 'restore_lead_lead', 'guard_name' => 'web']);
        $user->givePermissionTo('restore_lead_lead');

        $this->assertTrue($this->policy->restore($user, $lead));
    }

    public function test_restore_denies_user_with_permission_but_no_scope_access(): void
    {
        $user = User::factory()->create([
            'resource_permission' => PermissionType::INDIVIDUAL->value,
        ]);
        $lead = Lead::factory()->create();
        Permission::create(['name' => 'restore_lead_lead', 'guard_name' => 'web']);
        $user->givePermissionTo('restore_lead_lead');

        $this->assertFalse($this->policy->restore($user, $lead));
    }

    public function test_restore_any_allows_user_with_permission(): void
    {
        $user = User::factory()->create();
        Permission::create(['name' => 'restore_any_lead_lead', 'guard_name' => 'web']);
        $user->givePermissionTo('restore_any_lead_lead');

        $this->assertTrue($this->policy->restoreAny($user));
    }

    public function test_restore_any_denies_user_without_permission(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($this->policy->restoreAny($user));
    }

    public function test_reorder_allows_user_with_permission(): void
    {
        $user = User::factory()->create();
        Permission::create(['name' => 'reorder_lead_lead', 'guard_name' => 'web']);
        $user->givePermissionTo('reorder_lead_lead');

        $this->assertTrue($this->policy->reorder($user));
    }

    public function test_reorder_denies_user_without_permission(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($this->policy->reorder($user));
    }

    public function test_multiple_permissions_work_together(): void
    {
        $user = User::factory()->create([
            'resource_permission' => PermissionType::GLOBAL->value,
        ]);
        $lead = Lead::factory()->create();

        Permission::create(['name' => 'view_any_lead_lead', 'guard_name' => 'web']);
        Permission::create(['name' => 'view_lead_lead', 'guard_name' => 'web']);
        Permission::create(['name' => 'create_lead_lead', 'guard_name' => 'web']);
        Permission::create(['name' => 'update_lead_lead', 'guard_name' => 'web']);
        Permission::create(['name' => 'delete_lead_lead', 'guard_name' => 'web']);

        $user->givePermissionTo([
            'view_any_lead_lead',
            'view_lead_lead',
            'create_lead_lead',
            'update_lead_lead',
            'delete_lead_lead',
        ]);

        $this->assertTrue($this->policy->viewAny($user));
        $this->assertTrue($this->policy->view($user, $lead));
        $this->assertTrue($this->policy->create($user));
        $this->assertTrue($this->policy->update($user, $lead));
        $this->assertTrue($this->policy->delete($user, $lead));
    }
}
