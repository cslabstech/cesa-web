<?php

namespace Cesa\ExitClearance\Tests\Feature;

use App\Models\User;
use Cesa\ExitClearance\Models\Approver;
use Cesa\ExitClearance\Models\Department;
use Cesa\ExitClearance\Models\Request;
use Cesa\ExitClearance\Tests\ExitClearanceTestCase;
use Webkul\Security\Models\User as SecurityUser;

class ApproversTest extends ExitClearanceTestCase
{
    public function test_department_can_attach_multiple_approvers(): void
    {
        $department = Department::factory()->create();

        $approverOne = Approver::query()->create([
            'name'  => 'Approver One',
            'email' => 'approver1@example.com',
            'title' => 'Head HR',
        ]);

        $approverTwo = Approver::query()->create([
            'name'  => 'Approver Two',
            'email' => 'approver2@example.com',
            'title' => 'Finance Manager',
        ]);

        $department->approvers()->sync([$approverOne->id, $approverTwo->id]);

        $this->assertCount(2, $department->fresh()->approvers);
        $this->assertDatabaseHas('exit_clearance_department_approver', [
            'department_id' => $department->id,
            'approver_id'   => $approverOne->id,
        ]);
        $this->assertDatabaseHas('exit_clearance_department_approver', [
            'department_id' => $department->id,
            'approver_id'   => $approverTwo->id,
        ]);
    }

    public function test_soft_deleted_relations_remain_readable(): void
    {
        $creator = User::factory()->create();
        $headDepartment = Department::factory()->create(['creator_id' => $creator->id]);
        $childDepartment = Department::factory()->create([
            'creator_id'            => $creator->id,
            'head_of_department_id' => $headDepartment->id,
        ]);

        $request = Request::factory()->create([
            'department_id' => $childDepartment->id,
            'creator_id'    => $creator->id,
        ]);

        SecurityUser::query()->findOrFail($creator->id)->delete();
        $headDepartment->delete();
        $childDepartment->delete();

        $freshRequest = Request::query()->firstOrFail();
        $freshHeadDepartment = Department::withTrashed()->findOrFail($headDepartment->id);
        $freshChildDepartment = Department::withTrashed()->findOrFail($childDepartment->id);

        $this->assertSame($creator->id, $freshRequest->creator?->id);
        $this->assertSame($childDepartment->id, $freshRequest->department?->id);
        $this->assertSame($headDepartment->id, $freshChildDepartment->headOfDepartment?->id);
        $this->assertTrue($freshHeadDepartment->subDepartments->contains('id', $childDepartment->id));
    }

    public function test_multiple_approvers_can_have_the_same_email(): void
    {
        $sharedEmail = 'same-email@example.com';

        $approverOne = Approver::query()->create([
            'name'  => 'Approver Alpha',
            'email' => $sharedEmail,
            'title' => 'Title Alpha',
        ]);

        $approverTwo = Approver::query()->create([
            'name'  => 'Approver Beta',
            'email' => $sharedEmail,
            'title' => 'Title Beta',
        ]);

        $this->assertNotSame($approverOne->id, $approverTwo->id);
        $this->assertSame($sharedEmail, $approverOne->fresh()->email);
        $this->assertSame($sharedEmail, $approverTwo->fresh()->email);

        $matchingApprovers = Approver::query()->where('email', $sharedEmail)->get();
        $this->assertCount(2, $matchingApprovers);
    }
}
