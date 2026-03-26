<?php

namespace Cesa\FormTransfer\Tests\Unit\Services;

use Cesa\FormTransfer\Models\TransferApprovalWorkflow;
use Cesa\FormTransfer\Services\ApprovalWorkflowService;
use Cesa\FormTransfer\Tests\FormTransferTestCase;

class ApprovalWorkflowServiceTest extends FormTransferTestCase
{
    public function test_prepare_approvals_from_workflow_does_not_include_task_token(): void
    {
        $workflow = TransferApprovalWorkflow::factory()->create([
            'steps' => [
                [
                    'label'         => 'Manager Approval',
                    'default_name'  => 'Manager',
                    'default_email' => 'manager@example.com',
                    'default_title' => 'Manager',
                    'default_phone' => '08123456789',
                    'is_mandatory'  => true,
                    'sort_order'    => 1,
                ],
            ],
        ]);

        $approvals = app(ApprovalWorkflowService::class)->prepareApprovalsFromWorkflow($workflow->id);

        $this->assertCount(1, $approvals);
        $this->assertArrayHasKey('task_id', $approvals[0]);
        $this->assertArrayNotHasKey('task_token', $approvals[0]);
    }
}
