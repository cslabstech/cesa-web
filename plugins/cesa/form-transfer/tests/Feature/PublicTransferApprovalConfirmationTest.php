<?php

namespace Cesa\FormTransfer\Tests\Feature;

use Cesa\FormTransfer\Enums\ApprovalStatus;
use Cesa\FormTransfer\Livewire\PublicTransferApprovalPage;
use Cesa\FormTransfer\Models\TransferRequest;
use Cesa\FormTransfer\Tests\FormTransferTestCase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

class PublicTransferApprovalConfirmationTest extends FormTransferTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        require base_path('plugins/cesa/form-transfer/routes/web.php');

        $routes = app('router')->getRoutes();
        $routes->refreshNameLookups();
        $routes->refreshActionLookups();
    }

    public function test_public_approval_actions_require_confirmation_before_execution(): void
    {
        app()->setLocale('id');

        $request = TransferRequest::factory()->create([
            'uid'       => 'CSN-00243',
            'approvals' => [
                [
                    'label'   => 'Approval 1',
                    'name'    => 'Approver Finance',
                    'email'   => 'approver@example.com',
                    'title'   => 'Finance Manager',
                    'status'  => ApprovalStatus::PENDING->value,
                    'task_id' => 'approval-task-123',
                ],
            ],
        ]);

        $response = $this->get('/transfer-requests/approval/approval-task-123');

        $response
            ->assertOk()
            ->assertDontSee('wire:confirm', false)
            ->assertSee("mountAction('confirmApprove'", false)
            ->assertSee("mountAction('confirmReject'", false);

        expect(substr_count($response->getContent(), 'x-data="{ expanded: true }"'))
            ->toBeGreaterThanOrEqual(3);

        Livewire::test(PublicTransferApprovalPage::class, ['task' => 'approval-task-123'])
            ->assertActionExists('confirmApprove')
            ->assertActionExists('confirmReject')
            ->mountAction('confirmApprove')
            ->assertActionMounted('confirmApprove')
            ->assertMountedActionModalSee([
                __('form-transfer::public.approval.confirm.approve_heading'),
                __('form-transfer::public.approval.confirm.approve'),
            ])
            ->unmountAction()
            ->mountAction('confirmReject')
            ->assertActionMounted('confirmReject')
            ->assertMountedActionModalSee([
                __('form-transfer::public.approval.confirm.reject_heading'),
                __('form-transfer::public.approval.confirm.reject'),
            ]);

        expect($request->fresh()->approvals[0]['status'])->toBe(ApprovalStatus::PENDING->value);
    }

    public function test_public_approval_action_executes_after_filament_modal_is_submitted(): void
    {
        Notification::fake();

        $request = TransferRequest::factory()->create([
            'uid'       => 'CSN-00244',
            'approvals' => [
                [
                    'label'   => 'Approval 1',
                    'name'    => 'Approver Finance',
                    'email'   => 'approver@example.com',
                    'title'   => 'Finance Manager',
                    'status'  => ApprovalStatus::PENDING->value,
                    'task_id' => 'approval-task-submit',
                ],
            ],
        ]);

        $component = Livewire::test(PublicTransferApprovalPage::class, ['task' => 'approval-task-submit'])
            ->mountAction('confirmApprove')
            ->assertActionMounted('confirmApprove');

        expect($request->fresh()->approvals[0]['status'])->toBe(ApprovalStatus::PENDING->value);

        $component->callMountedAction();

        expect($request->fresh()->approvals[0]['status'])->toBe(ApprovalStatus::APPROVED->value);
    }
}
