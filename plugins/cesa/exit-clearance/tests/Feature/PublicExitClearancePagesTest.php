<?php

namespace Cesa\ExitClearance\Tests\Feature;

use Cesa\ExitClearance\Livewire\PublicExitClearanceApprovalPage;
use Cesa\ExitClearance\Livewire\PublicExitClearanceProgressPage;
use Cesa\ExitClearance\Models\Approver;
use Cesa\ExitClearance\Models\Request;
use Cesa\ExitClearance\Tests\ExitClearanceTestCase;
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
            $approver->id => ['status' => 'pending'],
        ]);

        Livewire::test(PublicExitClearanceApprovalPage::class, [
            'request'  => $request->id,
            'approver' => $approver->id,
        ])->assertSee('EXC-12345');
    }
}
