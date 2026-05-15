<?php

namespace Cesa\ExitClearance\Tests\Feature;

use Cesa\ExitClearance\Filament\Clusters\Configurations\Resources\ApproverResource;
use Cesa\ExitClearance\Filament\Clusters\Configurations\Resources\ApproverResource\Pages\ListApprovers;
use Cesa\ExitClearance\Filament\Clusters\Configurations\Resources\DepartmentResource;
use Cesa\ExitClearance\Filament\Clusters\Configurations\Resources\DepartmentResource\Pages\ListDepartments;
use Cesa\ExitClearance\Filament\Resources\RequestResource;
use Cesa\ExitClearance\Models\Approver;
use Cesa\ExitClearance\Models\Department;
use Cesa\ExitClearance\Models\Request;
use Cesa\ExitClearance\Policies\ApproverPolicy;
use Cesa\ExitClearance\Policies\DepartmentPolicy;
use Cesa\ExitClearance\Policies\RequestPolicy;
use Cesa\ExitClearance\Tests\ExitClearanceTestCase;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Webkul\Security\Enums\PermissionType;
use Webkul\Security\Models\Team;
use Webkul\Security\Models\User;
use Webkul\Security\Traits\HasResourcePermissionQuery;

class AdminResourceHardeningTest extends ExitClearanceTestCase
{
    public function test_request_policy_uses_creator_id_scope_for_view_and_update(): void
    {
        $owner = $this->fakeScopedUser(
            id: 10,
            permissionType: PermissionType::INDIVIDUAL,
            grantedAbilities: [],
        );

        $actor = $this->fakeScopedUser(
            id: 10,
            permissionType: PermissionType::INDIVIDUAL,
            grantedAbilities: ['view_exit_clearance_request', 'update_exit_clearance_request'],
        );

        $otherUser = $this->fakeScopedUser(
            id: 11,
            permissionType: PermissionType::INDIVIDUAL,
            grantedAbilities: ['view_exit_clearance_request', 'update_exit_clearance_request'],
        );

        $request = new Request;
        $request->setRelation('creator', $owner);

        $policy = new RequestPolicy;

        $this->assertTrue($policy->view($actor, $request));
        $this->assertTrue($policy->update($actor, $request));
        $this->assertFalse($policy->view($otherUser, $request));
        $this->assertFalse($policy->update($otherUser, $request));
    }

    public function test_public_request_without_creator_id_is_only_accessible_to_global_user(): void
    {
        $globalUser = $this->fakeScopedUser(
            id: 40,
            permissionType: PermissionType::GLOBAL,
            grantedAbilities: ['view_exit_clearance_request', 'update_exit_clearance_request'],
        );

        $individualUser = $this->fakeScopedUser(
            id: 41,
            permissionType: PermissionType::INDIVIDUAL,
            grantedAbilities: ['view_exit_clearance_request', 'update_exit_clearance_request'],
        );

        $groupUser = $this->fakeScopedUser(
            id: 42,
            permissionType: PermissionType::GROUP,
            grantedAbilities: ['view_exit_clearance_request', 'update_exit_clearance_request'],
            teamIds: [11],
        );

        $request = new Request;
        $policy = new RequestPolicy;

        $this->assertTrue($policy->view($globalUser, $request));
        $this->assertTrue($policy->update($globalUser, $request));
        $this->assertFalse($policy->view($individualUser, $request));
        $this->assertFalse($policy->update($individualUser, $request));
        $this->assertFalse($policy->view($groupUser, $request));
        $this->assertFalse($policy->update($groupUser, $request));
    }

    public function test_approver_policy_respects_group_scope_via_creator_id(): void
    {
        $actor = $this->fakeScopedUser(
            id: 20,
            permissionType: PermissionType::GROUP,
            grantedAbilities: ['view_exit_clearance_approver', 'update_exit_clearance_approver'],
            teamIds: [7],
        );

        $sameGroupCreator = $this->fakeScopedUser(
            id: 21,
            permissionType: PermissionType::INDIVIDUAL,
            grantedAbilities: [],
            teamIds: [7],
        );

        $otherGroupCreator = $this->fakeScopedUser(
            id: 22,
            permissionType: PermissionType::INDIVIDUAL,
            grantedAbilities: [],
            teamIds: [8],
        );

        $approver = new Approver;
        $policy = new ApproverPolicy;

        $approver->setRelation('creator', $sameGroupCreator);
        $this->assertTrue($policy->view($actor, $approver));
        $this->assertTrue($policy->update($actor, $approver));

        $approver->setRelation('creator', $otherGroupCreator);
        $this->assertFalse($policy->view($actor, $approver));
        $this->assertFalse($policy->update($actor, $approver));
    }

    public function test_department_policy_respects_group_scope_via_creator_id(): void
    {
        $actor = $this->fakeScopedUser(
            id: 30,
            permissionType: PermissionType::GROUP,
            grantedAbilities: ['view_exit_clearance_department', 'delete_exit_clearance_department'],
            teamIds: [9],
        );

        $sameGroupCreator = $this->fakeScopedUser(
            id: 31,
            permissionType: PermissionType::INDIVIDUAL,
            grantedAbilities: [],
            teamIds: [9],
        );

        $otherGroupCreator = $this->fakeScopedUser(
            id: 32,
            permissionType: PermissionType::INDIVIDUAL,
            grantedAbilities: [],
            teamIds: [10],
        );

        $department = new Department;
        $policy = new DepartmentPolicy;

        $department->setRelation('creator', $sameGroupCreator);
        $this->assertTrue($policy->view($actor, $department));
        $this->assertTrue($policy->delete($actor, $department));

        $department->setRelation('creator', $otherGroupCreator);
        $this->assertFalse($policy->view($actor, $department));
        $this->assertFalse($policy->delete($actor, $department));
    }

    public function test_configuration_resources_use_resource_permission_query_trait(): void
    {
        $approverResourceTraits = class_uses_recursive(ApproverResource::class);
        $departmentResourceTraits = class_uses_recursive(DepartmentResource::class);
        $requestResourceTraits = class_uses_recursive(RequestResource::class);

        $this->assertContains(HasResourcePermissionQuery::class, $approverResourceTraits);
        $this->assertContains(HasResourcePermissionQuery::class, $departmentResourceTraits);
        $this->assertContains(HasResourcePermissionQuery::class, $requestResourceTraits);
    }

    public function test_department_edit_form_only_lists_scoped_approvers(): void
    {
        Filament::setCurrentPanel('admin');

        $user = $this->fakeScopedUser(
            id: 50,
            permissionType: PermissionType::INDIVIDUAL,
            grantedAbilities: ['view_any_exit_clearance_department', 'view_exit_clearance_department', 'update_exit_clearance_department'],
        );

        $this->actingAs($user);

        $department = Department::query()->create([
            'code'       => 'HR-50',
            'name'       => 'HR 50',
            'creator_id' => $user->id,
        ]);

        $this->fakeScopedUser(
            id: 51,
            permissionType: PermissionType::INDIVIDUAL,
            grantedAbilities: [],
        );

        $scopedApprover = Approver::query()->create([
            'name'       => 'Scoped Approver',
            'email'      => 'scoped-approver@example.com',
            'title'      => 'Scoped',
            'creator_id' => $user->id,
        ]);

        $outOfScopeApprover = Approver::query()->create([
            'name'       => 'Out Of Scope Approver',
            'email'      => 'out-of-scope-approver@example.com',
            'title'      => 'Hidden',
            'creator_id' => 51,
        ]);

        $this->registerConfigurationClusterRoute();

        Livewire::test(ListDepartments::class)
            ->mountTableAction('edit', $department)
            ->assertFormFieldExists('approvers', function (Select $field) use ($scopedApprover, $outOfScopeApprover): bool {
                $optionIds = array_map('intval', array_keys($field->getOptions()));

                $this->assertContains($scopedApprover->id, $optionIds);
                $this->assertNotContains($outOfScopeApprover->id, $optionIds);

                return true;
            });
    }

    public function test_approver_create_form_only_lists_scoped_departments(): void
    {
        Filament::setCurrentPanel('admin');

        $user = $this->fakeScopedUser(
            id: 60,
            permissionType: PermissionType::INDIVIDUAL,
            grantedAbilities: ['view_any_exit_clearance_approver', 'create_exit_clearance_approver'],
        );

        $this->actingAs($user);

        $this->fakeScopedUser(
            id: 61,
            permissionType: PermissionType::INDIVIDUAL,
            grantedAbilities: [],
        );

        $scopedDepartment = Department::query()->create([
            'code'       => 'FIN-60',
            'name'       => 'Finance 60',
            'creator_id' => $user->id,
        ]);

        $outOfScopeDepartment = Department::query()->create([
            'code'       => 'IT-61',
            'name'       => 'IT 61',
            'creator_id' => 61,
        ]);

        $this->registerConfigurationClusterRoute();

        Livewire::test(ListApprovers::class)
            ->mountAction('create')
            ->assertFormFieldExists('departments', function (Select $field) use ($scopedDepartment, $outOfScopeDepartment): bool {
                $optionIds = array_map('intval', array_keys($field->getOptions()));

                $this->assertContains($scopedDepartment->id, $optionIds);
                $this->assertNotContains($outOfScopeDepartment->id, $optionIds);

                return true;
            });
    }

    public function test_department_list_shows_soft_deleted_records_in_archived_tab(): void
    {
        Filament::setCurrentPanel('admin');

        $user = $this->fakeScopedUser(
            id: 70,
            permissionType: PermissionType::INDIVIDUAL,
            grantedAbilities: ['view_any_exit_clearance_department'],
        );

        $this->actingAs($user);

        $activeDepartment = Department::query()->create([
            'code'       => 'ACT-70',
            'name'       => 'Active Department',
            'creator_id' => $user->id,
        ]);

        $archivedDepartment = Department::query()->create([
            'code'       => 'ARC-70',
            'name'       => 'Archived Department',
            'creator_id' => $user->id,
        ]);

        $archivedDepartment->delete();

        $this->registerConfigurationClusterRoute();

        Livewire::test(ListDepartments::class)
            ->set('activeTab', 'archived')
            ->assertCanSeeTableRecords([$archivedDepartment->fresh()])
            ->assertCanNotSeeTableRecords([$activeDepartment]);
    }

    public function test_department_actions_are_scoped_to_archived_tab(): void
    {
        Filament::setCurrentPanel('admin');

        $user = $this->fakeScopedUser(
            id: 75,
            permissionType: PermissionType::INDIVIDUAL,
            grantedAbilities: [
                'view_any_exit_clearance_department',
                'update_exit_clearance_department',
                'delete_exit_clearance_department',
                'restore_exit_clearance_department',
                'force_delete_exit_clearance_department',
                'restore_any_exit_clearance_department',
                'force_delete_any_exit_clearance_department',
            ],
        );

        $this->actingAs($user);

        $activeDepartment = Department::query()->create([
            'code'       => 'ACT-75',
            'name'       => 'Action Department',
            'creator_id' => $user->id,
        ]);

        $archivedDepartment = Department::query()->create([
            'code'       => 'ARC-75',
            'name'       => 'Archived Action Department',
            'creator_id' => $user->id,
        ]);

        $archivedDepartment->delete();
        $this->registerConfigurationClusterRoute();

        Livewire::test(ListDepartments::class)
            ->assertTableActionHidden('restore', $activeDepartment->getKey())
            ->assertTableActionHidden('forceDelete', $activeDepartment->getKey())
            ->assertTableBulkActionHidden('restore')
            ->assertTableBulkActionHidden('forceDelete');

        Livewire::test(ListDepartments::class)
            ->set('activeTab', 'archived')
            ->assertTableBulkActionVisible('restore')
            ->assertTableBulkActionVisible('forceDelete');
    }

    public function test_approver_list_shows_soft_deleted_records_in_archived_tab(): void
    {
        Filament::setCurrentPanel('admin');

        $user = $this->fakeScopedUser(
            id: 80,
            permissionType: PermissionType::INDIVIDUAL,
            grantedAbilities: ['view_any_exit_clearance_approver'],
        );

        $this->actingAs($user);

        $activeApprover = Approver::query()->create([
            'name'       => 'Active Approver',
            'email'      => 'active-approver@example.com',
            'title'      => 'Active',
            'creator_id' => $user->id,
        ]);

        $archivedApprover = Approver::query()->create([
            'name'       => 'Archived Approver',
            'email'      => 'archived-approver@example.com',
            'title'      => 'Archived',
            'creator_id' => $user->id,
        ]);

        $archivedApprover->delete();
        $this->registerConfigurationClusterRoute();

        Livewire::test(ListApprovers::class)
            ->set('activeTab', 'archived')
            ->assertCanSeeTableRecords([$archivedApprover->fresh()])
            ->assertCanNotSeeTableRecords([$activeApprover]);
    }

    public function test_approver_actions_are_scoped_to_archived_tab(): void
    {
        Filament::setCurrentPanel('admin');

        $user = $this->fakeScopedUser(
            id: 85,
            permissionType: PermissionType::INDIVIDUAL,
            grantedAbilities: [
                'view_any_exit_clearance_approver',
                'update_exit_clearance_approver',
                'delete_exit_clearance_approver',
                'restore_exit_clearance_approver',
                'force_delete_exit_clearance_approver',
                'restore_any_exit_clearance_approver',
                'force_delete_any_exit_clearance_approver',
            ],
        );

        $this->actingAs($user);

        $activeApprover = Approver::query()->create([
            'name'       => 'Action Approver',
            'email'      => 'action-approver@example.com',
            'title'      => 'Action',
            'creator_id' => $user->id,
        ]);

        $archivedApprover = Approver::query()->create([
            'name'       => 'Archived Action Approver',
            'email'      => 'archived-action-approver@example.com',
            'title'      => 'Archived',
            'creator_id' => $user->id,
        ]);

        $archivedApprover->delete();

        $this->registerConfigurationClusterRoute();

        Livewire::test(ListApprovers::class)
            ->assertTableActionHidden('restore', $activeApprover->getKey())
            ->assertTableActionHidden('forceDelete', $activeApprover->getKey())
            ->assertTableBulkActionHidden('restore')
            ->assertTableBulkActionHidden('forceDelete');

        Livewire::test(ListApprovers::class)
            ->set('activeTab', 'archived')
            ->assertTableBulkActionVisible('restore')
            ->assertTableBulkActionVisible('forceDelete');
    }

    /**
     * @param  array<int, string>  $grantedAbilities
     * @param  array<int, int>  $teamIds
     */
    private function fakeScopedUser(int $id, PermissionType $permissionType, array $grantedAbilities, array $teamIds = []): User
    {
        DB::table('users')->updateOrInsert(
            ['id' => $id],
            [
                'name'       => "Scoped User {$id}",
                'email'      => "scoped-user-{$id}@example.com",
                'password'   => bcrypt('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        $user = new class extends User
        {
            /** @var array<int, string> */
            public array $grantedAbilities = [];

            public function can($ability, $arguments = []): bool
            {
                return in_array($ability, $this->grantedAbilities, true);
            }
        };

        $user->id = $id;
        $user->resource_permission = $permissionType;
        $user->grantedAbilities = $grantedAbilities;
        $user->setRelation('teams', new EloquentCollection(
            array_map(function (int $teamId): Team {
                $team = new Team;
                $team->id = $teamId;

                return $team;
            }, $teamIds),
        ));

        $this->resetBouncerAuthorizedUserIdsCache();

        return $user;
    }

    private function registerConfigurationClusterRoute(): void
    {
        if (! Route::has('filament.admin.exit-clearance.configurations')) {
            Route::get('/filament/admin/exit-clearance/configurations', fn (): string => 'exit-clearance-configurations')
                ->name('filament.admin.exit-clearance.configurations');
        }
    }
}
