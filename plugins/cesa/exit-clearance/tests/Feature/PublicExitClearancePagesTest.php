<?php

namespace Cesa\ExitClearance\Tests\Feature;

use Cesa\ExitClearance\Livewire\PublicExitClearanceApprovalPage;
use Cesa\ExitClearance\Livewire\PublicExitClearanceProgressPage;
use Cesa\ExitClearance\Livewire\PublicExitClearanceRequestForm;
use Cesa\ExitClearance\Models\Approver;
use Cesa\ExitClearance\Models\Request;
use Cesa\ExitClearance\Services\ExitClearanceRequestService;
use Cesa\ExitClearance\Tests\ExitClearanceTestCase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

class PublicExitClearancePagesTest extends ExitClearanceTestCase
{
    public function test_progress_page_uses_form_uid_for_public_header(): void
    {
        $request = Request::query()->create([
            'name'             => 'Jane Doe',
            'email'            => 'jane@example.com',
            'form_uid'         => 'EXC-54321',
            'form_response_id' => 'resp-progress-1',
        ]);

        Livewire::test(PublicExitClearanceProgressPage::class, [
            'response' => $request->form_response_id,
        ])
            ->assertSet('applicantUid', 'EXC-54321')
            ->assertSee('EXC-54321');

        $this->withoutVite();
        $this->registerPublicRoutes();

        $response = $this->get(route('exit-clearance.public.progress', [
            'response' => $request->form_response_id,
        ]));

        $response->assertOk();

        expect(substr_count($response->getContent(), 'x-data="{ expanded: true'))
            ->toBeGreaterThanOrEqual(2);
    }

    public function test_approval_page_uses_form_uid_for_public_header(): void
    {
        $approver = Approver::query()->create([
            'name'  => 'Approver One',
            'email' => 'approver1@example.com',
            'title' => 'Head HR',
        ]);

        $request = Request::query()->create([
            'name'             => 'John Doe',
            'email'            => 'john@example.com',
            'form_uid'         => 'EXC-12345',
            'form_response_id' => 'resp-approval-1',
        ]);

        $request->approvers()->sync([
            $approver->id => [
                'status'      => 'approved',
                'notes'       => 'Catatan approval tertata',
                'approved_at' => '2026-04-09 10:01:00',
            ],
        ]);

        Livewire::test(PublicExitClearanceApprovalPage::class, [
            'request'  => $request->id,
            'approver' => $approver->id,
        ])->assertSee('EXC-12345');

        $this->withoutVite();
        $this->registerPublicRoutes();

        $response = $this->get(URL::signedRoute('exit-clearance.public.approval', [
            'request'  => $request,
            'approver' => $approver,
        ]));

        $response
            ->assertOk()
            ->assertSee('EXC-12345')
            ->assertSee('data-testid="approval-flow-item"', false)
            ->assertSee('data-testid="approval-flow-notes"', false)
            ->assertSee('Catatan approval tertata')
            ->assertSee('2026-04-09 10:01');

        expect(substr_count($response->getContent(), 'x-data="{ expanded: true'))
            ->toBeGreaterThanOrEqual(3);
    }

    public function test_pending_approval_action_buttons_are_side_by_side_and_require_confirmation(): void
    {
        $approver = Approver::query()->create([
            'name'  => 'Approver Two',
            'email' => 'approver2@example.com',
            'title' => 'Finance',
        ]);

        $request = Request::query()->create([
            'name'             => 'Action Button Test',
            'email'            => 'action-button@example.com',
            'form_uid'         => 'EXC-ACTION-BUTTON',
            'form_response_id' => 'resp-action-button',
        ]);

        $request->approvers()->sync([
            $approver->id => ['status' => 'pending'],
        ]);

        Livewire::test(PublicExitClearanceApprovalPage::class, [
            'request'  => $request->id,
            'approver' => $approver->id,
        ])
            ->assertActionExists('confirmApprove')
            ->assertActionExists('confirmReject')
            ->assertSee('data-testid="approval-action-buttons"', false)
            ->assertSee('grid grid-cols-2 gap-3', false)
            ->assertSee("mountAction('confirmApprove'", false)
            ->assertSee("mountAction('confirmReject'", false)
            ->assertSee(__('exit-clearance::livewire/public-exit-clearance-approval-page.approve'))
            ->assertSee(__('exit-clearance::livewire/public-exit-clearance-approval-page.reject'))
            ->mountAction('confirmApprove')
            ->assertActionMounted('confirmApprove')
            ->assertMountedActionModalSee([
                __('exit-clearance::livewire/public-exit-clearance-approval-page.confirm.approve_heading'),
                __('exit-clearance::livewire/public-exit-clearance-approval-page.confirm.approve_description'),
            ])
            ->unmountAction()
            ->mountAction('confirmReject')
            ->assertActionMounted('confirmReject')
            ->assertMountedActionModalSee([
                __('exit-clearance::livewire/public-exit-clearance-approval-page.confirm.reject_heading'),
                __('exit-clearance::livewire/public-exit-clearance-approval-page.confirm.reject_description'),
            ]);

        $request->refresh()->load('approvers');

        expect($request->approvers->first()?->pivot?->status)
            ->toBe(ExitClearanceRequestService::APPROVAL_PENDING);
    }

    public function test_pending_approval_action_executes_after_confirmation_modal_is_submitted(): void
    {
        Notification::fake();

        $approver = Approver::query()->create([
            'name'  => 'Approver Three',
            'email' => 'approver3@example.com',
            'title' => 'HR',
        ]);

        $request = Request::query()->create([
            'name'             => 'Confirmation Submit Test',
            'email'            => 'confirmation-submit@example.com',
            'form_uid'         => 'EXC-CONFIRM-SUBMIT',
            'form_response_id' => 'resp-confirm-submit',
        ]);

        $request->approvers()->sync([
            $approver->id => ['status' => ExitClearanceRequestService::APPROVAL_PENDING],
        ]);

        $component = Livewire::test(PublicExitClearanceApprovalPage::class, [
            'request'  => $request->id,
            'approver' => $approver->id,
        ])
            ->mountAction('confirmApprove')
            ->assertActionMounted('confirmApprove');

        $request->refresh()->load('approvers');

        expect($request->approvers->first()?->pivot?->status)
            ->toBe(ExitClearanceRequestService::APPROVAL_PENDING);

        $component->callMountedAction();

        $request->refresh()->load('approvers');

        expect($request->approvers->first()?->pivot?->status)
            ->toBe(ExitClearanceRequestService::APPROVAL_APPROVED);
    }

    public function test_next_step_validation_dispatches_feedback_events_on_the_public_form(): void
    {
        Livewire::test(PublicExitClearanceRequestForm::class)
            ->set('currentStep', 2)
            ->call('nextStep')
            ->assertSet('currentStep', 2)
            ->assertHasErrors([
                'data.name',
            ])
            ->assertDispatched('form-errors-presented');
    }

    private function registerPublicRoutes(): void
    {
        $routes = app('router')->getRoutes();

        if (! $routes->hasNamedRoute('exit-clearance.public.progress')) {
            require base_path('plugins/cesa/exit-clearance/routes/web.php');

            $routes = app('router')->getRoutes();
            $routes->refreshNameLookups();
            $routes->refreshActionLookups();
        }
    }
}
