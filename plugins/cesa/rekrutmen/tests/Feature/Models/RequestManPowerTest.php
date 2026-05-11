<?php

namespace Cesa\Rekrutmen\Tests\Feature\Models;

use Cesa\Rekrutmen\Enums\JobApplicationGender;
use Cesa\Rekrutmen\Enums\JobApplicationMaritalStatus;
use Cesa\Rekrutmen\Enums\JobApplicationStatus;
use Cesa\Rekrutmen\Enums\RequestManPowerApprovalStatus;
use Cesa\Rekrutmen\Enums\RequestManPowerFulfillmentStatus;
use Cesa\Rekrutmen\Enums\RequestManPowerStatus;
use Cesa\Rekrutmen\Enums\StatusKebutuhan;
use Cesa\Rekrutmen\Filament\Resources\RequestManPowerResource;
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
            'name'        => 'Division Approver',
            'email'       => 'division.approver@example.com',
            'title'       => 'IT Director',
            'division_id' => $divisionA->id,
            'is_active'   => true,
        ]);

        Approver::query()->create([
            'name'        => 'Other Division Approver',
            'email'       => 'finance.approver@example.com',
            'title'       => 'Finance Director',
            'division_id' => $divisionB->id,
            'is_active'   => true,
        ]);

        Approver::query()->create([
            'name'        => 'Other Company Approver',
            'email'       => 'other-company.approver@example.com',
            'title'       => 'Ops Director',
            'division_id' => $divisionOtherCompany->id,
            'is_active'   => true,
        ]);

        Approver::query()->create([
            'name'        => 'Inactive Approver',
            'email'       => 'inactive.approver@example.com',
            'title'       => 'Inactive Manager',
            'division_id' => $divisionA->id,
            'is_active'   => false,
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

    public function test_request_man_power_flags_missing_approval_workflow_when_no_matching_approver_exists(): void
    {
        Notification::fake();

        $company = Company::query()->create(['name' => 'PT Missing Approval']);
        $division = $this->createDivision($company, 'Legal');

        $request = RequestManPower::query()->create($this->basePayload([
            'company_id'    => $company->id,
            'division_id'   => $division->id,
            'email_address' => 'missing-approval@example.com',
        ]));

        $approval = $request->sendApprovalRequestNotifications();

        $request->refresh();
        $request->loadCount('approvals');

        $this->assertNull($approval);
        $this->assertTrue($request->hasMissingApprovalWorkflow());
        $this->assertSame(
            __('rekrutmen::filament/resources/request-man-power.table.descriptions.approval_workflow_missing'),
            RequestManPowerResource::formatApprovalDescription($request),
        );

        Notification::assertNothingSent();
    }

    public function test_pending_request_can_start_approval_flow_after_matching_approver_is_configured(): void
    {
        Notification::fake();

        $company = Company::query()->create(['name' => 'PT Approval Recovery']);
        $division = $this->createDivision($company, 'Operations');

        $request = RequestManPower::query()->create($this->basePayload([
            'company_id'    => $company->id,
            'division_id'   => $division->id,
            'email_address' => 'approval-recovery@example.com',
        ]));

        $this->assertNull($request->sendApprovalRequestNotifications());

        Approver::query()->create([
            'name'           => 'Operations Approver',
            'email'          => 'operations.approver@example.com',
            'title'          => 'Operations Lead',
            'approval_order' => 1,
            'division_id'    => $division->id,
            'is_active'      => true,
        ]);

        $approval = $request->initializeAndNotifyApprovalWorkflow(replaceExisting: true, rotateToken: true);

        $request->refresh();
        $request->load(['approvals']);

        $this->assertNotNull($approval);
        $this->assertFalse($request->hasMissingApprovalWorkflow());
        $this->assertCount(1, $request->approvals);
        $this->assertSame(RequestManPowerApprovalStatus::PENDING, $request->approvals->first()->status);
        $this->assertSame('operations.approver@example.com', $request->approvals->first()->approver_email);

        Notification::assertSentOnDemand(RequestManPowerApprovalRequestedNotification::class, function (
            RequestManPowerApprovalRequestedNotification $notification,
            array $channels,
            object $notifiable
        ): bool {
            return in_array('mail', $channels, true)
                && ($notifiable->routes['mail'] ?? null) === 'operations.approver@example.com';
        });
    }

    public function test_approval_step_cannot_be_processed_after_action_link_expires(): void
    {
        $company = Company::query()->create(['name' => 'PT Expired Approval']);
        $division = $this->createDivision($company, 'IT');

        Approver::query()->create([
            'name'           => 'Expired Link Approver',
            'email'          => 'expired.approver@example.com',
            'title'          => 'HRBP',
            'approval_order' => 1,
            'division_id'    => $division->id,
            'is_active'      => true,
        ]);

        $request = RequestManPower::query()->create($this->basePayload([
            'company_id'    => $company->id,
            'division_id'   => $division->id,
            'email_address' => 'expired-requester@example.com',
        ]));

        $request->initializeApprovalWorkflow();
        $approval = $request->currentPendingApproval()->firstOrFail();
        $approval->forceFill([
            'action_expires_at' => now()->subMinute(),
        ])->save();

        try {
            $request->approveApprovalStep($approval, 'Approved too late.');
            $this->fail('Expired approval link was processed.');
        } catch (RuntimeException $exception) {
            $this->assertSame(
                __('rekrutmen::livewire/public-request-man-power-approval-page.notifications.link_expired'),
                $exception->getMessage(),
            );
        }

        $this->assertSame(RequestManPowerStatus::PENDING, $request->fresh()->status);
        $this->assertTrue($approval->fresh()->isPending());
    }

    public function test_request_man_power_requires_requester_email(): void
    {
        $this->expectException(ValidationException::class);

        RequestManPower::query()->create($this->basePayload([
            'email_address' => '   ',
        ]));
    }

    public function test_fulfillment_status_tracks_candidate_progress_and_filters_mpp_rows(): void
    {
        $pipeline = RekrutmenPipeline::query()->create([
            'name'        => 'Fulfillment Pipeline',
            'description' => 'Main pipeline',
        ]);

        $stage = RekrutmenStage::query()->create([
            'rekrutmen_pipeline_id' => $pipeline->id,
            'name'                  => 'Screening',
            'order_column'          => 1,
        ]);

        $pendingApproval = RequestManPower::query()->create($this->basePayload([
            'email_address'              => 'pending-approval@example.com',
            'jumlah_karyawan_dibutuhkan' => 1,
        ]));

        $onHold = $this->createRequestWithJobPosting($pipeline, [
            'email_address'              => 'on-hold@example.com',
            'jumlah_karyawan_dibutuhkan' => 1,
            'status'                     => RequestManPowerStatus::HOLD,
        ]);

        $noCandidate = $this->createRequestWithJobPosting($pipeline, [
            'email_address'              => 'no-candidate@example.com',
            'jumlah_karyawan_dibutuhkan' => 2,
        ]);

        $inProcess = $this->createRequestWithJobPosting($pipeline, [
            'email_address'              => 'in-process@example.com',
            'jumlah_karyawan_dibutuhkan' => 2,
        ]);
        $this->createApplicationForRequest($inProcess, $stage, JobApplicationStatus::IN_PROGRESS, 'candidate-in-process@example.com');

        $fulfilled = $this->createRequestWithJobPosting($pipeline, [
            'email_address'              => 'fulfilled@example.com',
            'jumlah_karyawan_dibutuhkan' => 2,
        ]);
        $this->createApplicationForRequest($fulfilled, $stage, JobApplicationStatus::HIRED, 'candidate-hired-1@example.com');
        $this->createApplicationForRequest($fulfilled, $stage, JobApplicationStatus::HIRED, 'candidate-hired-2@example.com');

        $unfulfilled = $this->createRequestWithJobPosting($pipeline, [
            'email_address'              => 'unfulfilled@example.com',
            'jumlah_karyawan_dibutuhkan' => 2,
        ]);
        $this->createApplicationForRequest($unfulfilled, $stage, JobApplicationStatus::HIRED, 'candidate-partial-hired@example.com');
        $this->createApplicationForRequest($unfulfilled, $stage, JobApplicationStatus::WITHDRAWN, 'candidate-withdrawn@example.com');

        $closed = $this->createRequestWithJobPosting($pipeline, [
            'email_address'              => 'closed@example.com',
            'jumlah_karyawan_dibutuhkan' => 1,
        ]);
        $closed->jobPosting?->update([
            'closing_date' => now()->subDay()->toDateString(),
        ]);

        $this->assertSame(RequestManPowerFulfillmentStatus::PENDING_APPROVAL, $pendingApproval->fresh('jobPosting.applications')->fulfillmentStatus());
        $this->assertSame(RequestManPowerFulfillmentStatus::ON_HOLD, $onHold->fresh('jobPosting.applications')->fulfillmentStatus());
        $this->assertSame(RequestManPowerFulfillmentStatus::NO_CANDIDATE, $noCandidate->fresh('jobPosting.applications')->fulfillmentStatus());
        $this->assertSame(RequestManPowerFulfillmentStatus::IN_PROCESS, $inProcess->fresh('jobPosting.applications')->fulfillmentStatus());
        $this->assertSame(RequestManPowerFulfillmentStatus::FULFILLED, $fulfilled->fresh('jobPosting.applications')->fulfillmentStatus());
        $this->assertSame(RequestManPowerFulfillmentStatus::UNFULFILLED, $unfulfilled->fresh('jobPosting.applications')->fulfillmentStatus());
        $this->assertSame(RequestManPowerFulfillmentStatus::CLOSED, $closed->fresh('jobPosting.applications')->fulfillmentStatus());

        $this->assertEqualsCanonicalizing(
            [$fulfilled->id],
            RequestManPower::query()->whereFulfillmentStatus(RequestManPowerFulfillmentStatus::FULFILLED)->pluck('id')->all(),
        );
        $this->assertEqualsCanonicalizing(
            [$pendingApproval->id],
            RequestManPower::query()->whereFulfillmentStatus(RequestManPowerFulfillmentStatus::PENDING_APPROVAL)->pluck('id')->all(),
        );
        $this->assertEqualsCanonicalizing(
            [$onHold->id],
            RequestManPower::query()->whereFulfillmentStatus(RequestManPowerFulfillmentStatus::ON_HOLD)->pluck('id')->all(),
        );
        $this->assertEqualsCanonicalizing(
            [$inProcess->id],
            RequestManPower::query()->whereFulfillmentStatus(RequestManPowerFulfillmentStatus::IN_PROCESS)->pluck('id')->all(),
        );
        $this->assertEqualsCanonicalizing(
            [$noCandidate->id],
            RequestManPower::query()->whereFulfillmentStatus(RequestManPowerFulfillmentStatus::NO_CANDIDATE)->pluck('id')->all(),
        );
        $this->assertEqualsCanonicalizing(
            [$unfulfilled->id],
            RequestManPower::query()->whereFulfillmentStatus(RequestManPowerFulfillmentStatus::UNFULFILLED)->pluck('id')->all(),
        );
        $this->assertEqualsCanonicalizing(
            [$closed->id],
            RequestManPower::query()->whereFulfillmentStatus(RequestManPowerFulfillmentStatus::CLOSED)->pluck('id')->all(),
        );
    }

    public function test_shared_job_posting_fulfillment_uses_total_linked_mpp_need(): void
    {
        $pipeline = RekrutmenPipeline::query()->create([
            'name'        => 'Shared Fulfillment Pipeline',
            'description' => 'Main pipeline',
        ]);

        $stage = RekrutmenStage::query()->create([
            'rekrutmen_pipeline_id' => $pipeline->id,
            'name'                  => 'Screening',
            'order_column'          => 1,
        ]);

        $firstRequest = $this->createRequestWithJobPosting($pipeline, [
            'email_address'              => 'first-shared-fulfillment@example.com',
            'jumlah_karyawan_dibutuhkan' => 2,
        ]);

        $secondRequest = RequestManPower::query()->create($this->basePayload([
            'email_address'              => 'second-shared-fulfillment@example.com',
            'jumlah_karyawan_dibutuhkan' => 3,
            'status'                     => RequestManPowerStatus::APPROVED,
            'estimasi_tanggal_join'      => now()->addMonth()->toDateString(),
            'job_posting_id'             => $firstRequest->job_posting_id,
        ]));

        foreach (range(1, 3) as $index) {
            $this->createApplicationForRequest(
                $firstRequest,
                $stage,
                JobApplicationStatus::HIRED,
                'shared-hired-'.$index.'@example.com',
            );
        }

        $this->assertSame(RequestManPowerFulfillmentStatus::UNFULFILLED, $firstRequest->fresh('jobPosting.applications')->fulfillmentStatus());
        $this->assertSame(RequestManPowerFulfillmentStatus::UNFULFILLED, $secondRequest->fresh('jobPosting.applications')->fulfillmentStatus());
        $this->assertEqualsCanonicalizing(
            [$firstRequest->id, $secondRequest->id],
            RequestManPower::query()->whereFulfillmentStatus(RequestManPowerFulfillmentStatus::UNFULFILLED)->pluck('id')->all(),
        );

        foreach (range(4, 5) as $index) {
            $this->createApplicationForRequest(
                $firstRequest,
                $stage,
                JobApplicationStatus::HIRED,
                'shared-hired-'.$index.'@example.com',
            );
        }

        $this->assertSame(RequestManPowerFulfillmentStatus::FULFILLED, $firstRequest->fresh('jobPosting.applications')->fulfillmentStatus());
        $this->assertSame(RequestManPowerFulfillmentStatus::FULFILLED, $secondRequest->fresh('jobPosting.applications')->fulfillmentStatus());
        $this->assertEqualsCanonicalizing(
            [$firstRequest->id, $secondRequest->id],
            RequestManPower::query()->whereFulfillmentStatus(RequestManPowerFulfillmentStatus::FULFILLED)->pluck('id')->all(),
        );
    }

    public function test_pending_and_hold_fulfillment_filters_match_displayed_status_when_candidates_are_full(): void
    {
        $pipeline = RekrutmenPipeline::query()->create([
            'name'        => 'Pending Hold Fulfillment Pipeline',
            'description' => 'Main pipeline',
        ]);

        $stage = RekrutmenStage::query()->create([
            'rekrutmen_pipeline_id' => $pipeline->id,
            'name'                  => 'Screening',
            'order_column'          => 1,
        ]);

        $pending = $this->createRequestWithJobPosting($pipeline, [
            'email_address'              => 'pending-full@example.com',
            'jumlah_karyawan_dibutuhkan' => 1,
            'status'                     => RequestManPowerStatus::PENDING,
        ]);
        $onHold = $this->createRequestWithJobPosting($pipeline, [
            'email_address'              => 'hold-full@example.com',
            'jumlah_karyawan_dibutuhkan' => 1,
            'status'                     => RequestManPowerStatus::HOLD,
        ]);

        $this->createApplicationForRequest($pending, $stage, JobApplicationStatus::HIRED, 'pending-full-hired@example.com');
        $this->createApplicationForRequest($onHold, $stage, JobApplicationStatus::HIRED, 'hold-full-hired@example.com');

        $this->assertSame(RequestManPowerFulfillmentStatus::PENDING_APPROVAL, $pending->fresh('jobPosting.applications')->fulfillmentStatus());
        $this->assertSame(RequestManPowerFulfillmentStatus::ON_HOLD, $onHold->fresh('jobPosting.applications')->fulfillmentStatus());
        $this->assertEqualsCanonicalizing(
            [$pending->id],
            RequestManPower::query()->whereFulfillmentStatus(RequestManPowerFulfillmentStatus::PENDING_APPROVAL)->pluck('id')->all(),
        );
        $this->assertEqualsCanonicalizing(
            [$onHold->id],
            RequestManPower::query()->whereFulfillmentStatus(RequestManPowerFulfillmentStatus::ON_HOLD)->pluck('id')->all(),
        );
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

    public function test_approved_matching_requests_are_grouped_into_existing_job_posting(): void
    {
        Notification::fake();

        RekrutmenPipeline::query()->create([
            'name' => 'Default Pipeline',
        ]);

        $company = Company::query()->create([
            'name' => 'PT Shared Placement',
        ]);
        $division = $this->createDivision($company, 'Sales');
        $firstJoinDate = now()->addDays(10)->toDateString();
        $secondJoinDate = now()->addDays(20)->toDateString();

        $firstRequest = RequestManPower::query()->create($this->basePayload([
            'company_id'                 => $company->id,
            'division_id'                => $division->id,
            'divisi'                     => $division->name,
            'email_address'              => 'first-shared@example.com',
            'posisi_dibutuhkan'          => 'Frontliner',
            'lokasi_penempatan'          => 'Cirebon Timur',
            'jumlah_karyawan_dibutuhkan' => 1,
            'estimasi_tanggal_join'      => $firstJoinDate,
        ]));
        $secondRequest = RequestManPower::query()->create($this->basePayload([
            'company_id'                 => $company->id,
            'division_id'                => $division->id,
            'divisi'                     => $division->name,
            'email_address'              => 'second-shared@example.com',
            'posisi_dibutuhkan'          => ' frontliner ',
            'lokasi_penempatan'          => ' cirebon timur ',
            'jumlah_karyawan_dibutuhkan' => 2,
            'estimasi_tanggal_join'      => $secondJoinDate,
        ]));

        $approver = User::factory()->create();

        $firstRequest->approveBy($approver->id);
        $firstPosting = $firstRequest->fresh('jobPosting')->jobPosting;
        $firstPosting->update([
            'is_published' => true,
        ]);

        $secondRequest->approveBy($approver->id);

        $this->assertSame($firstPosting->id, $secondRequest->fresh()->job_posting_id);
        $this->assertSame(1, JobPosting::query()->count());
        $this->assertSame(2, $firstPosting->requestManPowers()->count());
        $this->assertSame(3, $firstPosting->fresh()->totalNeeded());
        $this->assertSame($secondJoinDate, $firstPosting->fresh()->closing_date?->toDateString());
    }

    public function test_legacy_job_posting_save_does_not_reassign_consolidated_request_link(): void
    {
        $pipeline = RekrutmenPipeline::query()->create([
            'name' => 'Default Pipeline',
        ]);

        $request = RequestManPower::query()->create($this->basePayload([
            'email_address'     => 'legacy-link@example.com',
            'posisi_dibutuhkan' => 'Frontliner',
            'lokasi_penempatan' => 'Kroya',
            'status'            => RequestManPowerStatus::APPROVED,
        ]));

        $canonicalPosting = JobPosting::query()->create([
            'rekrutmen_pipeline_id' => $pipeline->id,
            'title'                 => 'Frontliner Kroya',
            'slug'                  => 'frontliner-kroya',
            'description'           => 'Serve customers',
            'requirements'          => 'Retail experience',
            'location'              => 'Kroya',
        ]);

        $request->forceFill([
            'job_posting_id' => $canonicalPosting->id,
        ])->saveQuietly();

        $legacyDuplicate = JobPosting::query()->create([
            'rekrutmen_pipeline_id' => $pipeline->id,
            'request_man_power_id'  => $request->id,
            'title'                 => 'Frontliner Kroya Legacy',
            'slug'                  => 'frontliner-kroya-legacy',
            'description'           => 'Legacy posting',
            'requirements'          => 'Legacy requirements',
            'location'              => 'Kroya',
        ]);

        $this->assertSame($canonicalPosting->id, $request->fresh()->job_posting_id);

        $legacyDuplicate->update([
            'description' => 'Edited legacy posting',
        ]);

        $this->assertSame($canonicalPosting->id, $request->fresh()->job_posting_id);
    }

    public function test_shared_job_posting_stays_published_while_another_matching_request_is_approved(): void
    {
        Notification::fake();

        RekrutmenPipeline::query()->create([
            'name' => 'Default Pipeline',
        ]);

        $company = Company::query()->create([
            'name' => 'PT Shared Vacancy',
        ]);
        $division = $this->createDivision($company, 'Retail');
        $joinDate = now()->addDays(10)->toDateString();

        $firstRequest = RequestManPower::query()->create($this->basePayload([
            'company_id'            => $company->id,
            'division_id'           => $division->id,
            'divisi'                => $division->name,
            'email_address'         => 'first-live@example.com',
            'posisi_dibutuhkan'     => 'Gadget Specialist',
            'lokasi_penempatan'     => 'Cirebon Kota',
            'estimasi_tanggal_join' => $joinDate,
        ]));
        $secondRequest = RequestManPower::query()->create($this->basePayload([
            'company_id'            => $company->id,
            'division_id'           => $division->id,
            'divisi'                => $division->name,
            'email_address'         => 'second-live@example.com',
            'posisi_dibutuhkan'     => 'Gadget Specialist',
            'lokasi_penempatan'     => 'Cirebon Kota',
            'estimasi_tanggal_join' => $joinDate,
        ]));

        $approver = User::factory()->create();

        $firstRequest->approveBy($approver->id);
        $secondRequest->approveBy($approver->id);

        $jobPosting = $firstRequest->fresh('jobPosting')->jobPosting;
        $jobPosting->update([
            'is_published' => true,
        ]);

        $secondRequest->rejectBy($approver->id);

        $this->assertTrue($jobPosting->fresh()->is_published);

        $firstRequest->rejectBy($approver->id);

        $this->assertFalse($jobPosting->fresh()->is_published);
    }

    public function test_soft_deleted_approved_requests_do_not_keep_shared_job_posting_published(): void
    {
        Notification::fake();

        RekrutmenPipeline::query()->create([
            'name' => 'Default Pipeline',
        ]);

        $company = Company::query()->create([
            'name' => 'PT Deleted Shared Vacancy',
        ]);
        $division = $this->createDivision($company, 'Retail');
        $joinDate = now()->addDays(10)->toDateString();

        $firstRequest = RequestManPower::query()->create($this->basePayload([
            'company_id'                 => $company->id,
            'division_id'                => $division->id,
            'divisi'                     => $division->name,
            'email_address'              => 'first-deleted-live@example.com',
            'posisi_dibutuhkan'          => 'Store Advisor',
            'lokasi_penempatan'          => 'Cirebon Kota',
            'jumlah_karyawan_dibutuhkan' => 2,
            'estimasi_tanggal_join'      => $joinDate,
        ]));
        $secondRequest = RequestManPower::query()->create($this->basePayload([
            'company_id'                 => $company->id,
            'division_id'                => $division->id,
            'divisi'                     => $division->name,
            'email_address'              => 'second-deleted-live@example.com',
            'posisi_dibutuhkan'          => 'Store Advisor',
            'lokasi_penempatan'          => 'Cirebon Kota',
            'jumlah_karyawan_dibutuhkan' => 3,
            'estimasi_tanggal_join'      => $joinDate,
        ]));

        $approver = User::factory()->create();

        $firstRequest->approveBy($approver->id);
        $secondRequest->approveBy($approver->id);

        $jobPosting = $firstRequest->fresh('jobPosting')->jobPosting;
        $jobPosting->update([
            'is_published' => true,
        ]);

        $firstRequest->delete();

        $this->assertSame(3, $jobPosting->fresh()->totalNeeded());

        $secondRequest->rejectBy($approver->id);

        $this->assertFalse($jobPosting->fresh()->is_published);
    }

    public function test_deleted_source_request_does_not_contribute_total_needed_fallback(): void
    {
        $pipeline = RekrutmenPipeline::query()->create([
            'name' => 'Default Pipeline',
        ]);

        $request = RequestManPower::query()->create($this->basePayload([
            'email_address'              => 'deleted-source-total@example.com',
            'posisi_dibutuhkan'          => 'Store Advisor',
            'lokasi_penempatan'          => 'Cirebon Kota',
            'jumlah_karyawan_dibutuhkan' => 4,
            'status'                     => RequestManPowerStatus::APPROVED,
        ]));

        $jobPosting = JobPosting::query()->create([
            'rekrutmen_pipeline_id' => $pipeline->id,
            'request_man_power_id'  => $request->id,
            'title'                 => 'Store Advisor Cirebon Kota',
            'slug'                  => 'store-advisor-cirebon-kota',
            'description'           => 'Serve customers',
            'requirements'          => 'Retail experience',
            'location'              => 'Cirebon Kota',
        ]);

        $request->delete();

        $this->assertSame(0, $jobPosting->fresh()->totalNeeded());
    }

    public function test_duplicate_link_consolidation_keeps_unmapped_division_snapshots_separate(): void
    {
        $migration = require base_path('plugins/cesa/rekrutmen/database/migrations/2026_05_02_115102_rekrutmen_add_job_posting_id_to_request_man_powers_table.php');

        $pipeline = RekrutmenPipeline::query()->create([
            'name' => 'Default Pipeline',
        ]);

        $company = Company::query()->create([
            'name' => 'PT Unmapped Division',
        ]);

        $firstRequest = RequestManPower::query()->create($this->basePayload([
            'company_id'            => $company->id,
            'email_address'         => 'unmapped-sales@example.com',
            'posisi_dibutuhkan'     => 'Frontliner',
            'lokasi_penempatan'     => 'Kroya',
            'divisi'                => 'Sales',
            'estimasi_tanggal_join' => now()->addDays(10)->toDateString(),
            'status'                => RequestManPowerStatus::APPROVED,
        ]));
        $secondRequest = RequestManPower::query()->create($this->basePayload([
            'company_id'            => $company->id,
            'email_address'         => 'unmapped-retail@example.com',
            'posisi_dibutuhkan'     => 'Frontliner',
            'lokasi_penempatan'     => 'Kroya',
            'divisi'                => 'Retail',
            'estimasi_tanggal_join' => now()->addDays(10)->toDateString(),
            'status'                => RequestManPowerStatus::APPROVED,
        ]));

        $firstRequest->forceFill([
            'division_id' => null,
            'divisi'      => 'Sales',
        ])->saveQuietly();
        $secondRequest->forceFill([
            'division_id' => null,
            'divisi'      => 'Retail',
        ])->saveQuietly();

        $firstPosting = JobPosting::query()->create([
            'rekrutmen_pipeline_id' => $pipeline->id,
            'request_man_power_id'  => $firstRequest->id,
            'title'                 => 'Frontliner Kroya Sales',
            'slug'                  => 'frontliner-kroya-sales',
            'description'           => 'Serve sales customers',
            'requirements'          => 'Retail experience',
            'location'              => 'Kroya',
        ]);
        $secondPosting = JobPosting::query()->create([
            'rekrutmen_pipeline_id' => $pipeline->id,
            'request_man_power_id'  => $secondRequest->id,
            'title'                 => 'Frontliner Kroya Retail',
            'slug'                  => 'frontliner-kroya-retail',
            'description'           => 'Serve retail customers',
            'requirements'          => 'Retail experience',
            'location'              => 'Kroya',
        ]);

        $migration->up();

        $this->assertSame($firstPosting->id, $firstRequest->fresh()->job_posting_id);
        $this->assertSame($secondPosting->id, $secondRequest->fresh()->job_posting_id);
        $this->assertFalse(JobPosting::withTrashed()->findOrFail($firstPosting->id)->trashed());
        $this->assertFalse(JobPosting::withTrashed()->findOrFail($secondPosting->id)->trashed());
    }

    public function test_duplicate_link_consolidation_moves_applications_to_canonical_posting(): void
    {
        $migration = require base_path('plugins/cesa/rekrutmen/database/migrations/2026_05_02_115102_rekrutmen_add_job_posting_id_to_request_man_powers_table.php');

        $pipeline = RekrutmenPipeline::query()->create([
            'name' => 'Default Pipeline',
        ]);
        $stage = RekrutmenStage::query()->create([
            'rekrutmen_pipeline_id' => $pipeline->id,
            'name'                  => 'Screening',
            'order_column'          => 1,
        ]);

        $company = Company::query()->create([
            'name' => 'PT Consolidated Vacancy',
        ]);
        $division = $this->createDivision($company, 'Sales');
        $joinDate = now()->addDays(10)->toDateString();

        $firstRequest = RequestManPower::query()->create($this->basePayload([
            'company_id'            => $company->id,
            'division_id'           => $division->id,
            'divisi'                => $division->name,
            'email_address'         => 'first-consolidated-app@example.com',
            'posisi_dibutuhkan'     => 'Frontliner',
            'lokasi_penempatan'     => 'Kroya',
            'estimasi_tanggal_join' => $joinDate,
            'status'                => RequestManPowerStatus::APPROVED,
        ]));
        $secondRequest = RequestManPower::query()->create($this->basePayload([
            'company_id'            => $company->id,
            'division_id'           => $division->id,
            'divisi'                => $division->name,
            'email_address'         => 'second-consolidated-app@example.com',
            'posisi_dibutuhkan'     => 'Frontliner',
            'lokasi_penempatan'     => 'Kroya',
            'estimasi_tanggal_join' => $joinDate,
            'status'                => RequestManPowerStatus::APPROVED,
        ]));

        $canonicalPosting = JobPosting::query()->create([
            'rekrutmen_pipeline_id' => $pipeline->id,
            'request_man_power_id'  => $firstRequest->id,
            'title'                 => 'Frontliner Kroya',
            'slug'                  => 'frontliner-kroya-canonical',
            'description'           => 'Serve customers',
            'requirements'          => 'Retail experience',
            'location'              => 'Kroya',
            'is_published'          => true,
        ]);
        $duplicatePosting = JobPosting::query()->create([
            'rekrutmen_pipeline_id' => $pipeline->id,
            'request_man_power_id'  => $secondRequest->id,
            'title'                 => 'Frontliner Kroya Duplicate',
            'slug'                  => 'frontliner-kroya-duplicate-with-apps',
            'description'           => 'Serve duplicate customers',
            'requirements'          => 'Retail experience',
            'location'              => 'Kroya',
            'is_published'          => false,
        ]);

        $firstRequest = $firstRequest->fresh('jobPosting');
        $secondRequest = $secondRequest->fresh('jobPosting');
        $this->assertSame($canonicalPosting->id, $firstRequest->job_posting_id);
        $this->assertSame($duplicatePosting->id, $secondRequest->job_posting_id);

        $this->createApplicationForRequest($firstRequest, $stage, JobApplicationStatus::IN_PROGRESS, 'same-consolidated-candidate@example.com');
        $duplicateApplication = $this->createApplicationForRequest($secondRequest, $stage, JobApplicationStatus::IN_PROGRESS, 'same-consolidated-candidate@example.com');

        $migration->up();

        $duplicateApplication->refresh();

        $this->assertSame($canonicalPosting->id, $secondRequest->fresh()->job_posting_id);
        $this->assertSame($canonicalPosting->id, $duplicateApplication->job_posting_id);
        $this->assertNull($duplicateApplication->active_email);
        $this->assertNull($duplicateApplication->active_whatsapp);
        $this->assertFalse(JobPosting::withTrashed()->findOrFail($duplicatePosting->id)->trashed());
        $this->assertFalse((bool) JobPosting::withTrashed()->findOrFail($duplicatePosting->id)->is_published);
    }

    public function test_duplicate_link_consolidation_maps_applications_to_canonical_pipeline_stage(): void
    {
        $migration = require base_path('plugins/cesa/rekrutmen/database/migrations/2026_05_02_115102_rekrutmen_add_job_posting_id_to_request_man_powers_table.php');

        $canonicalPipeline = RekrutmenPipeline::query()->create([
            'name' => 'Canonical Pipeline',
        ]);
        RekrutmenStage::query()->create([
            'rekrutmen_pipeline_id' => $canonicalPipeline->id,
            'name'                  => 'Canonical Screening',
            'order_column'          => 1,
        ]);
        $canonicalInterviewStage = RekrutmenStage::query()->create([
            'rekrutmen_pipeline_id' => $canonicalPipeline->id,
            'name'                  => 'Canonical Interview',
            'order_column'          => 2,
        ]);

        $duplicatePipeline = RekrutmenPipeline::query()->create([
            'name' => 'Duplicate Pipeline',
        ]);
        RekrutmenStage::query()->create([
            'rekrutmen_pipeline_id' => $duplicatePipeline->id,
            'name'                  => 'Duplicate Screening',
            'order_column'          => 1,
        ]);
        $duplicateInterviewStage = RekrutmenStage::query()->create([
            'rekrutmen_pipeline_id' => $duplicatePipeline->id,
            'name'                  => 'Duplicate Interview',
            'order_column'          => 2,
        ]);

        $company = Company::query()->create([
            'name' => 'PT Pipeline Consolidation',
        ]);
        $division = $this->createDivision($company, 'Sales');
        $joinDate = now()->addDays(10)->toDateString();

        $firstRequest = RequestManPower::query()->create($this->basePayload([
            'company_id'            => $company->id,
            'division_id'           => $division->id,
            'divisi'                => $division->name,
            'email_address'         => 'canonical-pipeline@example.com',
            'posisi_dibutuhkan'     => 'Frontliner',
            'lokasi_penempatan'     => 'Kroya',
            'estimasi_tanggal_join' => $joinDate,
            'status'                => RequestManPowerStatus::APPROVED,
        ]));
        $secondRequest = RequestManPower::query()->create($this->basePayload([
            'company_id'            => $company->id,
            'division_id'           => $division->id,
            'divisi'                => $division->name,
            'email_address'         => 'duplicate-pipeline@example.com',
            'posisi_dibutuhkan'     => 'Frontliner',
            'lokasi_penempatan'     => 'Kroya',
            'estimasi_tanggal_join' => $joinDate,
            'status'                => RequestManPowerStatus::APPROVED,
        ]));

        $canonicalPosting = JobPosting::query()->create([
            'rekrutmen_pipeline_id' => $canonicalPipeline->id,
            'request_man_power_id'  => $firstRequest->id,
            'title'                 => 'Frontliner Kroya',
            'slug'                  => 'frontliner-kroya-canonical-pipeline',
            'description'           => 'Serve customers',
            'requirements'          => 'Retail experience',
            'location'              => 'Kroya',
            'is_published'          => true,
            'closing_date'          => $joinDate,
        ]);
        $duplicatePosting = JobPosting::query()->create([
            'rekrutmen_pipeline_id' => $duplicatePipeline->id,
            'request_man_power_id'  => $secondRequest->id,
            'title'                 => 'Frontliner Kroya Duplicate',
            'slug'                  => 'frontliner-kroya-duplicate-pipeline',
            'description'           => 'Serve duplicate customers',
            'requirements'          => 'Retail experience',
            'location'              => 'Kroya',
            'is_published'          => false,
            'closing_date'          => $joinDate,
        ]);

        $secondRequest = $secondRequest->fresh('jobPosting');
        $application = $this->createApplicationForRequest(
            $secondRequest,
            $duplicateInterviewStage,
            JobApplicationStatus::IN_PROGRESS,
            'pipeline-consolidated-candidate@example.com',
        );

        $migration->up();

        $application->refresh();

        $this->assertSame($canonicalPosting->id, $secondRequest->fresh()->job_posting_id);
        $this->assertSame($canonicalPosting->id, $application->job_posting_id);
        $this->assertSame($canonicalInterviewStage->id, $application->current_stage_id);
        $this->assertNotNull($application->position);
        $this->assertFalse(JobPosting::withTrashed()->findOrFail($duplicatePosting->id)->trashed());
        $this->assertFalse((bool) JobPosting::withTrashed()->findOrFail($duplicatePosting->id)->is_published);
    }

    public function test_matching_ignores_stale_legacy_source_links_on_duplicate_postings(): void
    {
        Notification::fake();

        $pipeline = RekrutmenPipeline::query()->create([
            'name' => 'Default Pipeline',
        ]);

        $company = Company::query()->create([
            'name' => 'PT Duplicate Vacancy',
        ]);
        $division = $this->createDivision($company, 'Sales');
        $joinDate = now()->addDays(10)->toDateString();

        $sourceRequest = RequestManPower::query()->create($this->basePayload([
            'company_id'            => $company->id,
            'division_id'           => $division->id,
            'divisi'                => $division->name,
            'email_address'         => 'source-duplicate@example.com',
            'posisi_dibutuhkan'     => 'Frontliner',
            'lokasi_penempatan'     => 'Kroya',
            'estimasi_tanggal_join' => $joinDate,
        ]));

        $approver = User::factory()->create();

        $sourceRequest->approveBy($approver->id);

        $canonicalPosting = $sourceRequest->fresh('jobPosting')->jobPosting;
        $duplicatePosting = JobPosting::query()->create([
            'rekrutmen_pipeline_id' => $pipeline->id,
            'request_man_power_id'  => $sourceRequest->id,
            'title'                 => 'Frontliner Kroya Duplicate',
            'slug'                  => 'frontliner-kroya-duplicate',
            'description'           => 'Duplicate posting with applications',
            'requirements'          => 'Retail experience',
            'location'              => 'Kroya',
            'is_published'          => false,
            'closing_date'          => $joinDate,
        ]);

        $newRequest = RequestManPower::query()->create($this->basePayload([
            'company_id'            => $company->id,
            'division_id'           => $division->id,
            'divisi'                => $division->name,
            'email_address'         => 'new-duplicate@example.com',
            'posisi_dibutuhkan'     => 'Frontliner',
            'lokasi_penempatan'     => 'Kroya',
            'estimasi_tanggal_join' => $joinDate,
        ]));

        $newRequest->approveBy($approver->id);

        $this->assertSame($canonicalPosting->id, $newRequest->fresh()->job_posting_id);
        $this->assertNotSame($duplicatePosting->id, $newRequest->fresh()->job_posting_id);
    }

    public function test_reapproving_secondary_linked_request_extends_job_posting_closing_date(): void
    {
        Notification::fake();

        RekrutmenPipeline::query()->create([
            'name' => 'Default Pipeline',
        ]);

        $company = Company::query()->create([
            'name' => 'PT Extended Closing Date',
        ]);
        $division = $this->createDivision($company, 'Sales');
        $firstJoinDate = now()->addDays(10)->toDateString();
        $secondJoinDate = now()->addDays(20)->toDateString();
        $extendedJoinDate = now()->addDays(40)->toDateString();

        $firstRequest = RequestManPower::query()->create($this->basePayload([
            'company_id'            => $company->id,
            'division_id'           => $division->id,
            'divisi'                => $division->name,
            'email_address'         => 'first-extended@example.com',
            'posisi_dibutuhkan'     => 'Sales Advisor',
            'lokasi_penempatan'     => 'Cirebon Timur',
            'estimasi_tanggal_join' => $firstJoinDate,
        ]));
        $secondRequest = RequestManPower::query()->create($this->basePayload([
            'company_id'            => $company->id,
            'division_id'           => $division->id,
            'divisi'                => $division->name,
            'email_address'         => 'second-extended@example.com',
            'posisi_dibutuhkan'     => 'Sales Advisor',
            'lokasi_penempatan'     => 'Cirebon Timur',
            'estimasi_tanggal_join' => $secondJoinDate,
        ]));

        $approver = User::factory()->create();

        $firstRequest->approveBy($approver->id);
        $secondRequest->approveBy($approver->id);

        $jobPosting = $firstRequest->fresh('jobPosting')->jobPosting;

        $secondRequest->markPending($approver->id);
        $secondRequest->update([
            'estimasi_tanggal_join' => $extendedJoinDate,
        ]);
        $secondRequest->approveBy($approver->id);

        $this->assertSame($extendedJoinDate, $jobPosting->fresh()->closing_date?->toDateString());
    }

    public function test_reapproving_source_request_keeps_later_shared_job_posting_closing_date(): void
    {
        Notification::fake();

        RekrutmenPipeline::query()->create([
            'name' => 'Default Pipeline',
        ]);

        $company = Company::query()->create([
            'name' => 'PT Shared Closing Source',
        ]);
        $division = $this->createDivision($company, 'Sales');
        $sourceJoinDate = now()->addDays(10)->toDateString();
        $secondaryJoinDate = now()->addDays(45)->toDateString();

        $sourceRequest = RequestManPower::query()->create($this->basePayload([
            'company_id'            => $company->id,
            'division_id'           => $division->id,
            'divisi'                => $division->name,
            'email_address'         => 'source-shared-closing@example.com',
            'posisi_dibutuhkan'     => 'Sales Advisor',
            'lokasi_penempatan'     => 'Cirebon Timur',
            'estimasi_tanggal_join' => $sourceJoinDate,
        ]));
        $secondaryRequest = RequestManPower::query()->create($this->basePayload([
            'company_id'            => $company->id,
            'division_id'           => $division->id,
            'divisi'                => $division->name,
            'email_address'         => 'secondary-shared-closing@example.com',
            'posisi_dibutuhkan'     => 'Sales Advisor',
            'lokasi_penempatan'     => 'Cirebon Timur',
            'estimasi_tanggal_join' => $secondaryJoinDate,
        ]));

        $approver = User::factory()->create();

        $sourceRequest->approveBy($approver->id);
        $secondaryRequest->approveBy($approver->id);

        $jobPosting = $sourceRequest->fresh('jobPosting')->jobPosting;
        $this->assertSame($secondaryJoinDate, $jobPosting->fresh()->closing_date?->toDateString());

        $sourceRequest->forceFill([
            'status' => RequestManPowerStatus::PENDING,
        ])->saveQuietly();

        $sourceRequest->approveBy($approver->id);

        $this->assertSame($secondaryJoinDate, $jobPosting->fresh()->closing_date?->toDateString());
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
            'name'        => 'Pending Approver',
            'email'       => 'pending.approver@example.com',
            'title'       => 'Approver',
            'division_id' => $division->id,
            'is_active'   => true,
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

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createRequestWithJobPosting(RekrutmenPipeline $pipeline, array $overrides = []): RequestManPower
    {
        $request = RequestManPower::query()->create($this->basePayload(array_merge([
            'status'                => RequestManPowerStatus::APPROVED,
            'estimasi_tanggal_join' => now()->addMonth()->toDateString(),
        ], $overrides)));

        $jobPosting = JobPosting::query()->create([
            'request_man_power_id'  => $request->id,
            'rekrutmen_pipeline_id' => $pipeline->id,
            'title'                 => 'Fulfillment '.$request->id,
            'slug'                  => 'fulfillment-'.$request->id,
            'description'           => $request->job_description,
            'requirements'          => $request->requirements_kualifikasi,
            'location'              => $request->lokasi_penempatan,
            'is_published'          => true,
            'closing_date'          => now()->addMonth()->toDateString(),
        ]);

        $request->forceFill([
            'job_posting_id' => $jobPosting->id,
        ])->saveQuietly();

        return $request->fresh('jobPosting');
    }

    private function createApplicationForRequest(
        RequestManPower $request,
        RekrutmenStage $stage,
        JobApplicationStatus $status,
        string $email,
    ): JobApplication {
        $jobPosting = $request->jobPosting ?? $request->jobPosting()->firstOrFail();
        $phoneSuffix = str_pad((string) (abs(crc32($email)) % 100000000), 8, '0', STR_PAD_LEFT);

        return JobApplication::query()->create([
            'job_posting_id'             => $jobPosting->id,
            'current_stage_id'           => $stage->id,
            'full_name'                  => 'Candidate '.$phoneSuffix,
            'email'                      => $email,
            'gender'                     => JobApplicationGender::Male,
            'birth_date'                 => '1998-05-10',
            'marital_status'             => JobApplicationMaritalStatus::Single,
            'address_ktp'                => 'Alamat KTP',
            'address_domicile'           => 'Alamat Domisili',
            'whatsapp_number'            => '081'.$phoneSuffix,
            'active_phone'               => '082'.$phoneSuffix,
            'emergency_contact_name'     => 'Bunga',
            'emergency_contact_relation' => 'Saudara',
            'emergency_contact_phone'    => '083'.$phoneSuffix,
            'status'                     => $status,
        ]);
    }
}
