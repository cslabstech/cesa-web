<?php

namespace Cesa\Rekrutmen\Tests\Feature\Models;

use Cesa\Rekrutmen\Enums\JobApplicationGender;
use Cesa\Rekrutmen\Enums\JobApplicationMaritalStatus;
use Cesa\Rekrutmen\Enums\JobApplicationStatus;
use Cesa\Rekrutmen\Enums\RequestManPowerStatus;
use Cesa\Rekrutmen\Enums\StatusKebutuhan;
use Cesa\Rekrutmen\Livewire\PublicRequestManPowerProgressPage;
use Cesa\Rekrutmen\Models\Approver;
use Cesa\Rekrutmen\Models\Division;
use Cesa\Rekrutmen\Models\JobApplication;
use Cesa\Rekrutmen\Models\JobApplicationHistory;
use Cesa\Rekrutmen\Models\JobPosting;
use Cesa\Rekrutmen\Models\RekrutmenPipeline;
use Cesa\Rekrutmen\Models\RekrutmenStage;
use Cesa\Rekrutmen\Models\RequestManPower;
use Cesa\Rekrutmen\Models\RequestManPowerApproval;
use Cesa\Rekrutmen\Models\RequestManPowerApprovalRequestedNotification;
use Cesa\Rekrutmen\Models\RequestManPowerStatusChangedNotification;
use Cesa\Rekrutmen\Models\RequestManPowerStatusHistory;
use Cesa\Rekrutmen\Models\RequestManPowerSubmittedNotification;
use Cesa\Rekrutmen\Tests\RekrutmenTestCase;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Webkul\Security\Models\User;
use Webkul\Security\Models\User as SecurityUser;
use Webkul\Support\Models\Company;

class RequestManPowerTest extends RekrutmenTestCase
{
    public function test_nama_karyawan_replacement_is_only_stored_for_replacement_status(): void
    {
        $newHiring = RequestManPower::query()->create([
            ...$this->basePayload(),
            'status_kebutuhan'          => StatusKebutuhan::NEW_HIRING,
            'nama_karyawan_replacement' => 'Should Be Null',
        ]);

        $replacement = RequestManPower::query()->create([
            ...$this->basePayload([
                'email_address'    => 'replacement@example.com',
                'status_kebutuhan' => StatusKebutuhan::REPLACEMENT,
            ]),
            'nama_karyawan_replacement' => '  Rina Putri  ',
        ]);

        $this->assertNull($newHiring->fresh()->nama_karyawan_replacement);
        $this->assertSame('Rina Putri', $replacement->fresh()->nama_karyawan_replacement);
        $this->assertTrue($replacement->isReplacement());
    }

    public function test_request_man_power_generates_public_status_response_id(): void
    {
        $request = RequestManPower::query()->create($this->basePayload());

        $this->assertNotEmpty($request->status_response_id);
        $this->assertTrue(Str::isUuid($request->status_response_id));
    }

    public function test_company_selection_syncs_business_entity_snapshot_from_erp_core(): void
    {
        $company = Company::query()->create([
            'name' => 'PT ERP Core',
        ]);
        $division = $this->createDivision($company, 'IT');

        $request = RequestManPower::query()->create($this->basePayload([
            'company_id'  => $company->id,
            'division_id' => $division->id,
            'divisi'      => 'Legacy Division',
        ]));

        $request->refresh();

        $this->assertSame($company->id, $request->company_id);
        $this->assertSame($division->id, $request->division_id);
        $this->assertSame('IT', $request->divisi);
        $this->assertSame('PT ERP Core', $request->business_entity_name);
    }

    public function test_request_man_power_resolves_matching_approvers_by_company_and_division(): void
    {
        $companyA = Company::query()->create(['name' => 'PT Cesa A']);
        $companyB = Company::query()->create(['name' => 'PT Cesa B']);
        $divisionA = $this->createDivision($companyA, 'IT');
        $divisionB = $this->createDivision($companyA, 'Finance');
        $divisionOtherCompany = $this->createDivision($companyB, 'IT');

        Approver::query()->create([
            'name'      => 'Global Approver',
            'email'     => 'global.approver@example.com',
            'title'     => 'General Manager',
            'is_active' => true,
        ]);

        Approver::query()->create([
            'name'       => 'Company Approver',
            'email'      => 'company.approver@example.com',
            'title'      => 'HR Manager',
            'company_id' => $companyA->id,
            'is_active'  => true,
        ]);

        Approver::query()->create([
            'name'          => 'Division Approver',
            'email'         => 'division.approver@example.com',
            'title'         => 'IT Director',
            'division_id'   => $divisionA->id,
            'is_active'     => true,
        ]);

        Approver::query()->create([
            'name'          => 'Other Division Approver',
            'email'         => 'finance.approver@example.com',
            'title'         => 'Finance Director',
            'division_id'   => $divisionB->id,
            'is_active'     => true,
        ]);

        Approver::query()->create([
            'name'          => 'Other Company Approver',
            'email'         => 'other-company.approver@example.com',
            'title'         => 'Ops Director',
            'division_id'   => $divisionOtherCompany->id,
            'is_active'     => true,
        ]);

        Approver::query()->create([
            'name'          => 'Inactive Approver',
            'email'         => 'inactive.approver@example.com',
            'title'         => 'Inactive Manager',
            'division_id'   => $divisionA->id,
            'is_active'     => false,
        ]);

        $request = RequestManPower::query()->create($this->basePayload([
            'company_id'  => $companyA->id,
            'division_id' => $divisionA->id,
        ]));

        $resolvedEmails = $request->approvalApprovers()
            ->pluck('email')
            ->all();

        $this->assertSame([
            'company.approver@example.com',
            'division.approver@example.com',
            'global.approver@example.com',
        ], $resolvedEmails);
    }

    public function test_request_man_power_sends_approval_request_notifications_only_to_the_first_pending_step(): void
    {
        Notification::fake();

        $company = Company::query()->create(['name' => 'PT Notification Match']);
        $division = $this->createDivision($company, 'IT');

        Approver::query()->create([
            'name'           => 'Scoped Approver',
            'email'          => 'scoped.approver@example.com',
            'title'          => 'HRBP',
            'approval_order' => 1,
            'division_id'    => $division->id,
            'is_active'      => true,
        ]);

        Approver::query()->create([
            'name'           => 'Global Approver',
            'email'          => 'global.notify@example.com',
            'title'          => 'GM HR',
            'approval_order' => 2,
            'is_active'      => true,
        ]);

        $request = RequestManPower::query()->create($this->basePayload([
            'company_id'    => $company->id,
            'division_id'   => $division->id,
            'email_address' => 'requester@example.com',
        ]));

        $request->sendApprovalRequestNotifications();

        $request->refresh();
        $request->load('approvals');

        Notification::assertSentOnDemandTimes(RequestManPowerApprovalRequestedNotification::class, 1);
        Notification::assertSentOnDemand(RequestManPowerApprovalRequestedNotification::class, function (
            RequestManPowerApprovalRequestedNotification $notification,
            array $channels,
            object $notifiable
        ): bool {
            return in_array('mail', $channels, true)
                && ($notifiable->routes['mail'] ?? null) === 'scoped.approver@example.com';
        });
        $this->assertCount(2, $request->approvals);
        $this->assertSame('scoped.approver@example.com', $request->approvals->firstWhere('step_order', 1)?->approver_email);
        $this->assertSame('global.notify@example.com', $request->approvals->firstWhere('step_order', 2)?->approver_email);
    }

    public function test_request_man_power_requires_requester_email(): void
    {
        $this->expectException(ValidationException::class);

        RequestManPower::query()->create($this->basePayload([
            'email_address' => '   ',
        ]));
    }

    public function test_request_man_power_notifications_are_queued_on_standard_notifications_queue(): void
    {
        $request = new RequestManPower;
        $approval = new RequestManPowerApproval;

        $submittedNotification = new RequestManPowerSubmittedNotification($request);
        $statusChangedNotification = new RequestManPowerStatusChangedNotification(
            $request,
            RequestManPowerStatus::PENDING,
            RequestManPowerStatus::APPROVED
        );
        $approvalRequestedNotification = new RequestManPowerApprovalRequestedNotification($request, $approval);

        $this->assertSame('notifications', config('rekrutmen.notifications.queue'));
        $this->assertInstanceOf(ShouldQueue::class, $submittedNotification);
        $this->assertInstanceOf(ShouldQueue::class, $statusChangedNotification);
        $this->assertInstanceOf(ShouldQueue::class, $approvalRequestedNotification);
        $this->assertSame('notifications', $submittedNotification->queue);
        $this->assertSame('notifications', $statusChangedNotification->queue);
        $this->assertSame('notifications', $approvalRequestedNotification->queue);
    }

    public function test_approve_by_updates_status_and_creates_job_posting(): void
    {
        Notification::fake();

        RekrutmenPipeline::query()->create([
            'name'        => 'Default Pipeline',
            'description' => 'Main pipeline',
        ]);

        $request = RequestManPower::query()->create($this->basePayload([
            'email_address' => 'requester@example.com',
            'status'        => RequestManPowerStatus::PENDING,
        ]));

        $approver = User::factory()->create();
        $request->approveBy($approver->id);

        $request->refresh();
        $jobPosting = $request->jobPosting;

        $this->assertNotNull($jobPosting);
        $this->assertSame(RequestManPowerStatus::APPROVED, $request->status);
        $this->assertSame($approver->id, $request->approved_by);
        $this->assertSame($request->lokasi_penempatan, $jobPosting->location);
        $this->assertNotEmpty($jobPosting->slug);
    }

    public function test_create_job_posting_generates_unique_slug_for_same_title(): void
    {
        RekrutmenPipeline::query()->create([
            'name' => 'Default Pipeline',
        ]);

        $firstRequest = RequestManPower::query()->create($this->basePayload([
            'email_address' => 'first@example.com',
        ]));
        $secondRequest = RequestManPower::query()->create($this->basePayload([
            'email_address' => 'second@example.com',
        ]));

        $firstPosting = $firstRequest->createJobPostingIfMissing();
        $secondPosting = $secondRequest->createJobPostingIfMissing();
        $repeatSecondPosting = $secondRequest->createJobPostingIfMissing();

        $this->assertNotSame($firstPosting->slug, $secondPosting->slug);
        $this->assertStringStartsWith($firstPosting->slug, $secondPosting->slug);
        $this->assertSame($secondPosting->id, $repeatSecondPosting->id);
    }

    public function test_approve_by_uses_the_configured_default_pipeline_and_refreshes_existing_job_posting(): void
    {
        RekrutmenPipeline::query()->create([
            'name' => 'Fallback Pipeline',
        ]);

        $preferredPipeline = RekrutmenPipeline::query()->create([
            'name' => 'Preferred Pipeline',
        ]);

        config()->set('rekrutmen.default_pipeline_id', $preferredPipeline->id);

        $request = RequestManPower::query()->create($this->basePayload([
            'email_address' => 'preferred@example.com',
            'status'        => RequestManPowerStatus::PENDING,
        ]));

        $approver = User::factory()->create();

        $request->approveBy($approver->id);

        $jobPosting = $request->jobPosting()->first();

        $this->assertNotNull($jobPosting);
        $this->assertSame($preferredPipeline->id, $jobPosting->rekrutmen_pipeline_id);

        $request->markPending();
        $request->update([
            'posisi_dibutuhkan'        => 'Senior Software Engineer',
            'lokasi_penempatan'        => 'Bandung',
            'job_description'          => 'Updated job description',
            'requirements_kualifikasi' => 'Updated requirements',
            'estimasi_tanggal_join'    => '2026-05-01',
        ]);

        $request->approveBy($approver->id);

        $jobPosting->refresh();

        $this->assertSame('Senior Software Engineer Bandung', $jobPosting->title);
        $this->assertSame('senior-software-engineer-bandung', $jobPosting->slug);
        $this->assertSame('Updated job description', $jobPosting->description);
        $this->assertSame('Updated requirements', $jobPosting->requirements);
        $this->assertSame('Bandung', $jobPosting->location);
        $this->assertSame('2026-05-01', $jobPosting->closing_date?->toDateString());
        $this->assertSame($preferredPipeline->id, $jobPosting->rekrutmen_pipeline_id);
    }

    public function test_rejecting_and_marking_pending_unpublish_existing_job_posting(): void
    {
        Notification::fake();

        RekrutmenPipeline::query()->create([
            'name' => 'Default Pipeline',
        ]);

        $company = Company::query()->create(['name' => 'PT Pending Notification']);
        $division = $this->createDivision($company, 'IT');

        Approver::query()->create([
            'name'          => 'Pending Approver',
            'email'         => 'pending.approver@example.com',
            'title'         => 'Approver',
            'division_id'   => $division->id,
            'is_active'     => true,
        ]);

        $request = RequestManPower::query()->create($this->basePayload([
            'email_address' => 'lifecycle@example.com',
            'status'        => RequestManPowerStatus::PENDING,
            'company_id'    => $company->id,
            'division_id'   => $division->id,
        ]));

        $approver = User::factory()->create();

        $request->approveBy($approver->id);

        $jobPosting = $request->jobPosting()->firstOrFail();
        $jobPosting->update(['is_published' => true]);

        $request->rejectBy($approver->id);
        $jobPosting->refresh();

        $this->assertSame(RequestManPowerStatus::REJECTED, $request->fresh()->status);
        $this->assertFalse($jobPosting->is_published);

        $jobPosting->update(['is_published' => true]);

        $request->markPending();
        $jobPosting->refresh();

        $this->assertSame(RequestManPowerStatus::PENDING, $request->fresh()->status);
        $this->assertNull($request->fresh()->approved_by);
        $this->assertFalse($jobPosting->is_published);

        Notification::assertSentOnDemand(RequestManPowerApprovalRequestedNotification::class, function (
            RequestManPowerApprovalRequestedNotification $notification,
            array $channels,
            object $notifiable
        ): bool {
            return in_array('mail', $channels, true)
                && ($notifiable->routes['mail'] ?? null) === 'pending.approver@example.com';
        });
    }

    public function test_holding_an_approved_request_unpublishes_its_job_posting_and_can_resume(): void
    {
        Notification::fake();

        RekrutmenPipeline::query()->create([
            'name' => 'Default Pipeline',
        ]);

        $request = RequestManPower::query()->create($this->basePayload([
            'email_address' => 'hold-lifecycle@example.com',
            'status'        => RequestManPowerStatus::PENDING,
        ]));

        $approver = User::factory()->create();

        $request->approveBy($approver->id);

        $jobPosting = $request->jobPosting()->firstOrFail();
        $jobPosting->update(['is_published' => true]);

        $request->markOnHold($approver->id, 'Budget recruitment ditunda sampai kuartal berikutnya.');

        $heldRequest = $request->fresh(['heldBy', 'statusHistories']);

        $this->assertSame(RequestManPowerStatus::HOLD, $heldRequest->status);
        $previousLocale = app()->getLocale();
        app()->setLocale('id');
        $this->assertSame('Hold', RequestManPowerStatus::HOLD->getLabel());
        app()->setLocale($previousLocale);
        $this->assertSame($approver->id, $heldRequest->approved_by);
        $this->assertSame($approver->id, $heldRequest->held_by);
        $this->assertSame('Budget recruitment ditunda sampai kuartal berikutnya.', $heldRequest->hold_reason);
        $this->assertNotNull($heldRequest->held_at);
        $this->assertNull($heldRequest->resumed_at);
        $this->assertTrue($heldRequest->hold_job_posting_was_published);
        $this->assertFalse($jobPosting->fresh()->is_published);
        $this->assertSame(2, $heldRequest->statusHistories->count());
        $this->assertSame(RequestManPowerStatus::HOLD, $heldRequest->statusHistories->first()->to_status);
        $this->assertSame('Budget recruitment ditunda sampai kuartal berikutnya.', $heldRequest->statusHistories->first()->reason);

        $request->resumeFromHold($approver->id);

        $resumedRequest = $request->fresh(['resumedBy', 'statusHistories']);

        $this->assertSame(RequestManPowerStatus::APPROVED, $resumedRequest->status);
        $this->assertSame($approver->id, $resumedRequest->approved_by);
        $this->assertSame($approver->id, $resumedRequest->resumed_by);
        $this->assertNotNull($resumedRequest->resumed_at);
        $this->assertSame($jobPosting->id, $resumedRequest->jobPosting?->id);
        $this->assertTrue($jobPosting->fresh()->is_published);
        $this->assertSame(3, RequestManPowerStatusHistory::query()->where('request_man_power_id', $request->id)->count());
        $this->assertSame(3, $resumedRequest->statusHistories->count());
        $this->assertSame(RequestManPowerStatus::APPROVED, $resumedRequest->statusHistories->first()->to_status);
        $this->assertSame(RequestManPowerStatus::HOLD, $resumedRequest->statusHistories->first()->from_status);
    }

    public function test_holding_a_request_requires_a_reason(): void
    {
        Notification::fake();

        RekrutmenPipeline::query()->create([
            'name' => 'Default Pipeline',
        ]);

        $request = RequestManPower::query()->create($this->basePayload([
            'email_address' => 'hold-reason-required@example.com',
            'status'        => RequestManPowerStatus::PENDING,
        ]));

        $approver = User::factory()->create();

        $request->approveBy($approver->id);

        $this->expectException(ValidationException::class);

        $request->markOnHold($approver->id, '   ');
    }

    public function test_approve_by_fails_without_changing_status_when_default_pipeline_cannot_be_resolved(): void
    {
        config()->set('rekrutmen.default_pipeline_id', 999999);
        config()->set('rekrutmen.default_pipeline_name', 'Missing Pipeline');

        $request = RequestManPower::query()->create($this->basePayload([
            'email_address' => 'missing-pipeline@example.com',
            'status'        => RequestManPowerStatus::PENDING,
        ]));

        $approver = User::factory()->create();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(__('rekrutmen::filament/resources/request-man-power.errors.default_pipeline_not_configured'));

        try {
            $request->approveBy($approver->id);
        } finally {
            $request->refresh();

            $this->assertSame(RequestManPowerStatus::PENDING, $request->status);
            $this->assertNull($request->approved_by);
            $this->assertNull(JobPosting::query()->where('request_man_power_id', $request->id)->first());
        }
    }

    public function test_approve_by_falls_back_to_oldest_pipeline_when_configured_name_is_stale(): void
    {
        config()->set('rekrutmen.default_pipeline_id', null);
        config()->set('rekrutmen.default_pipeline_name', 'Default Recruitment Pipeline');

        $renamedDefaultPipeline = RekrutmenPipeline::query()->create([
            'name' => 'Renamed Default Pipeline',
        ]);

        RekrutmenPipeline::query()->create([
            'name' => 'Another Pipeline',
        ]);

        $request = RequestManPower::query()->create($this->basePayload([
            'email_address' => 'stale-name@example.com',
            'status'        => RequestManPowerStatus::PENDING,
        ]));

        $approver = User::factory()->create();

        $request->approveBy($approver->id);

        $jobPosting = $request->jobPosting()->first();

        $this->assertNotNull($jobPosting);
        $this->assertSame($renamedDefaultPipeline->id, $jobPosting->rekrutmen_pipeline_id);
        $this->assertSame(RequestManPowerStatus::APPROVED, $request->fresh()->status);
    }

    public function test_scopes_filter_records_by_divisi_status_and_tanggal(): void
    {
        RequestManPower::query()->create($this->basePayload([
            'email_address'     => 'it.pending@example.com',
            'divisi'            => 'IT',
            'status'            => RequestManPowerStatus::PENDING,
            'tanggal_pengajuan' => '2026-03-01',
        ]));

        RequestManPower::query()->create($this->basePayload([
            'email_address'     => 'it.approved@example.com',
            'divisi'            => 'IT',
            'status'            => RequestManPowerStatus::APPROVED,
            'tanggal_pengajuan' => '2026-03-10',
        ]));

        RequestManPower::query()->create($this->basePayload([
            'email_address'     => 'finance.pending@example.com',
            'divisi'            => 'Finance',
            'status'            => RequestManPowerStatus::PENDING,
            'tanggal_pengajuan' => '2026-04-10',
        ]));

        $this->assertSame(2, RequestManPower::query()->byDivisi('IT')->count());
        $this->assertSame(2, RequestManPower::query()->byStatus(RequestManPowerStatus::PENDING->value)->count());
        $this->assertSame(2, RequestManPower::query()->byTanggal('2026-03-01', '2026-03-31')->count());
    }

    public function test_public_progress_page_loads_request_by_status_response_id(): void
    {
        $request = RequestManPower::query()->create($this->basePayload());

        $page = app(PublicRequestManPowerProgressPage::class);
        $page->mount($request->status_response_id);

        $this->assertSame($request->id, $page->requestManPower->id);
        $this->assertSame(__('rekrutmen::livewire/public-request-man-power-progress-page.heading'), $page->getHeading());
        $this->assertSame(__('rekrutmen::livewire/public-request-man-power-progress-page.subheading'), $page->getSubheading());
    }

    public function test_public_progress_page_shows_hold_reason_and_status_history(): void
    {
        Notification::fake();

        RekrutmenPipeline::query()->create([
            'name' => 'Default Pipeline',
        ]);

        $request = RequestManPower::query()->create($this->basePayload([
            'email_address' => 'progress-hold@example.com',
            'status'        => RequestManPowerStatus::PENDING,
        ]));

        $approver = User::factory()->create();

        $request->approveBy($approver->id);
        $request->markOnHold($approver->id, 'Budget recruitment ditunda sampai kuartal berikutnya.');

        $this->get('man-power/progress/'.$request->status_response_id)
            ->assertOk()
            ->assertSee(__('rekrutmen::livewire/public-request-man-power-progress-page.hold_notice.title'))
            ->assertSee(__('rekrutmen::livewire/public-request-man-power-progress-page.status_history_heading'))
            ->assertSee('Budget recruitment ditunda sampai kuartal berikutnya.');
    }

    public function test_request_man_power_notifications_include_public_progress_url(): void
    {
        $request = RequestManPower::query()->create($this->basePayload());

        $submittedMail = (new RequestManPowerSubmittedNotification($request))->toMail(new \stdClass);
        $statusChangedMail = (new RequestManPowerStatusChangedNotification(
            $request,
            RequestManPowerStatus::PENDING,
            RequestManPowerStatus::APPROVED,
        ))->toMail(new \stdClass);
        $request->forceFill([
            'hold_reason' => 'Budget recruitment ditunda.',
        ]);
        $holdMail = (new RequestManPowerStatusChangedNotification(
            $request,
            RequestManPowerStatus::APPROVED,
            RequestManPowerStatus::HOLD,
        ))->toMail(new \stdClass);

        $this->assertSame('rekrutmen::mail.request-man-power-submitted', $submittedMail->view);
        $this->assertSame('rekrutmen::mail.request-man-power-status-changed', $statusChangedMail->view);
        $this->assertSame($request->getPublicProgressUrl(), $submittedMail->actionUrl);
        $this->assertSame(__('rekrutmen::mail/request-man-power-submitted.view_progress'), $submittedMail->actionText);
        $this->assertSame($request->getPublicProgressUrl(), $statusChangedMail->actionUrl);
        $this->assertSame(__('rekrutmen::mail/request-man-power-status-changed.view_progress'), $statusChangedMail->actionText);
        $this->assertNotEmpty($submittedMail->viewData['summary'] ?? []);
        $this->assertNotEmpty($statusChangedMail->viewData['summary'] ?? []);
        $this->assertContains(
            'Budget recruitment ditunda.',
            collect($holdMail->viewData['summary'])->pluck('value')->all()
        );
    }

    public function test_soft_deleted_relations_remain_readable(): void
    {
        $pipeline = RekrutmenPipeline::query()->create([
            'name'        => 'Default Pipeline',
            'description' => 'Main pipeline',
        ]);

        $stage = RekrutmenStage::query()->create([
            'rekrutmen_pipeline_id' => $pipeline->id,
            'name'                  => 'Screening',
            'order_column'          => 1,
        ]);

        $request = RequestManPower::query()->create($this->basePayload([
            'email_address' => 'requester@example.com',
        ]));

        $jobPosting = $request->createJobPostingIfMissing();

        $application = JobApplication::query()->create([
            'job_posting_id'             => $jobPosting->id,
            'current_stage_id'           => $stage->id,
            'full_name'                  => 'Candidate One',
            'email'                      => 'candidate@example.com',
            'gender'                     => JobApplicationGender::Male,
            'birth_date'                 => '1998-05-10',
            'marital_status'             => JobApplicationMaritalStatus::Single,
            'address_ktp'                => 'Alamat KTP',
            'address_domicile'           => 'Alamat Domisili',
            'whatsapp_number'            => '08123456789',
            'active_phone'               => '08123456789',
            'emergency_contact_name'     => 'Bunga',
            'emergency_contact_relation' => 'Saudara',
            'emergency_contact_phone'    => '081111111111',
            'status'                     => JobApplicationStatus::IN_PROGRESS,
        ]);

        $performer = User::factory()->create();

        $history = JobApplicationHistory::query()->create([
            'job_application_id' => $application->id,
            'from_stage_id'      => $stage->id,
            'to_stage_id'        => $stage->id,
            'status'             => JobApplicationStatus::IN_PROGRESS,
            'notes'              => 'Moved',
            'performed_by'       => $performer->id,
        ]);

        $stage->delete();
        $pipeline->delete();
        $jobPosting->delete();
        SecurityUser::query()->findOrFail($performer->id)->delete();

        $freshRequest = RequestManPower::query()->findOrFail($request->id);
        $freshPipeline = RekrutmenPipeline::withTrashed()->findOrFail($pipeline->id);
        $freshHistory = JobApplicationHistory::query()->findOrFail($history->id);

        $this->assertSame($jobPosting->id, $freshRequest->jobPosting?->id);
        $this->assertTrue($freshPipeline->stages->contains('id', $stage->id));
        $this->assertTrue($freshPipeline->jobPostings->contains('id', $jobPosting->id));
        $this->assertSame($application->id, $freshHistory->jobApplication?->id);
        $this->assertSame($performer->id, $freshHistory->performer?->id);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function basePayload(array $overrides = []): array
    {
        $companyId = $overrides['company_id'] ?? Company::query()->create([
            'name' => 'PT Cesa Indonesia',
        ])->id;
        $divisionId = $overrides['division_id'] ?? $this->createDivision(
            Company::query()->findOrFail($companyId),
            $overrides['divisi'] ?? 'IT',
        )->id;

        return array_merge([
            'company_id'                 => $companyId,
            'division_id'                => $divisionId,
            'email_address'              => 'requester@example.com',
            'nama_pengaju'               => 'Andi Saputra',
            'posisi_pengaju'             => 'HR Manager',
            'tanggal_pengajuan'          => '2026-03-02',
            'posisi_dibutuhkan'          => 'Software Engineer',
            'lokasi_penempatan'          => 'Jakarta',
            'status_kebutuhan'           => StatusKebutuhan::NEW_HIRING,
            'divisi'                     => 'IT',
            'level_pekerjaan'            => 'Staff',
            'jumlah_karyawan_dibutuhkan' => 1,
            'estimasi_tanggal_join'      => '2026-04-01',
            'requirements_kualifikasi'   => 'PHP, Laravel, SQL',
            'job_description'            => 'Develop internal systems',
            'status'                     => RequestManPowerStatus::PENDING,
        ], $overrides);
    }

    private function createDivision(Company $company, string $name): Division
    {
        return Division::query()->create([
            'name'       => $name,
            'company_id' => $company->id,
        ]);
    }
}
