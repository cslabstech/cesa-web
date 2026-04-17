<?php

namespace Cesa\Rekrutmen\Tests\Feature;

use Cesa\Rekrutmen\Enums\RequestManPowerApprovalStatus;
use Cesa\Rekrutmen\Enums\RequestManPowerStatus;
use Cesa\Rekrutmen\Enums\StatusKebutuhan;
use Cesa\Rekrutmen\Models\Approver;
use Cesa\Rekrutmen\Models\Division;
use Cesa\Rekrutmen\Models\RekrutmenPipeline;
use Cesa\Rekrutmen\Models\RequestManPower;
use Cesa\Rekrutmen\Models\RequestManPowerApprovalRequestedNotification;
use Cesa\Rekrutmen\Models\RequestManPowerStatusChangedNotification;
use Cesa\Rekrutmen\Tests\RekrutmenTestCase;
use Illuminate\Support\Facades\Notification;
use Webkul\Support\Models\Company;

class PublicRequestManPowerApprovalPageTest extends RekrutmenTestCase
{
    public function test_public_approval_route_requires_a_valid_signature(): void
    {
        $request = $this->createRequestWithApprovers();
        $request->sendApprovalRequestNotifications();

        $approval = $request->currentPendingApproval()->firstOrFail();

        $this->get($approval->buildApprovalUrl())
            ->assertOk()
            ->assertSee(__('rekrutmen::livewire/public-request-man-power-approval-page.page_title'));

        $this->get(route('rekrutmen.public.request-man-power.approval', [
            'approval' => $approval->getKey(),
            'token'    => $approval->action_token,
        ]))->assertForbidden();
    }

    public function test_public_approval_page_processes_approvals_sequentially_until_final_approval(): void
    {
        Notification::fake();

        RekrutmenPipeline::query()->create([
            'name' => 'Default Pipeline',
        ]);

        $request = $this->createRequestWithApprovers(emailAddress: 'requester@example.com');

        $request->sendApprovalRequestNotifications();

        Notification::assertSentOnDemandTimes(RequestManPowerApprovalRequestedNotification::class, 1);
        Notification::assertSentOnDemand(RequestManPowerApprovalRequestedNotification::class, function (
            RequestManPowerApprovalRequestedNotification $notification,
            array $channels,
            object $notifiable
        ): bool {
            return ($notifiable->routes['mail'] ?? null) === 'first.approver@example.com';
        });

        $firstApproval = $request->currentPendingApproval()->firstOrFail();

        $request->approveApprovalStep($firstApproval, 'Checked by first approver');

        $request->refresh();
        $request->load(['approvals', 'currentPendingApproval']);

        $this->assertSame(RequestManPowerStatus::PENDING, $request->status);
        $this->assertSame('second.approver@example.com', $request->currentPendingApproval?->approver_email);
        $this->assertSame(RequestManPowerApprovalStatus::APPROVED, $request->approvals->firstWhere('step_order', 1)?->status);
        $this->assertSame('Checked by first approver', $request->approvals->firstWhere('step_order', 1)?->notes);

        Notification::assertSentOnDemandTimes(RequestManPowerApprovalRequestedNotification::class, 2);
        Notification::assertSentOnDemand(RequestManPowerApprovalRequestedNotification::class, function (
            RequestManPowerApprovalRequestedNotification $notification,
            array $channels,
            object $notifiable
        ): bool {
            return ($notifiable->routes['mail'] ?? null) === 'second.approver@example.com';
        });

        $secondApproval = $request->currentPendingApproval()->firstOrFail();

        $request->approveApprovalStep($secondApproval, 'Final approval complete');

        $request->refresh();
        $request->load(['approvals', 'jobPosting']);

        $this->assertSame(RequestManPowerStatus::APPROVED, $request->status);
        $this->assertNotNull($request->jobPosting);
        $this->assertFalse($request->jobPosting->is_published);
        $this->assertSame(RequestManPowerApprovalStatus::APPROVED, $request->approvals->firstWhere('step_order', 2)?->status);
        $this->assertNull($request->currentPendingApproval()->first());

        Notification::assertSentOnDemand(RequestManPowerStatusChangedNotification::class, function (
            RequestManPowerStatusChangedNotification $notification,
            array $channels,
            object $notifiable
        ): bool {
            return ($notifiable->routes['mail'] ?? null) === 'requester@example.com';
        });
    }

    public function test_public_approval_page_can_reject_the_request_and_stop_the_chain(): void
    {
        Notification::fake();

        $request = $this->createRequestWithApprovers(emailAddress: 'reject-requester@example.com');
        $request->sendApprovalRequestNotifications();

        $approval = $request->currentPendingApproval()->firstOrFail();

        $request->rejectApprovalStep($approval, 'Need revision');

        $request->refresh();
        $request->load('approvals');

        $this->assertSame(RequestManPowerStatus::REJECTED, $request->status);
        $this->assertSame(RequestManPowerApprovalStatus::REJECTED, $request->approvals->firstWhere('step_order', 1)?->status);
        $this->assertSame(RequestManPowerApprovalStatus::WAITING, $request->approvals->firstWhere('step_order', 2)?->status);
        $this->assertNull($request->currentPendingApproval()->first());

        Notification::assertSentOnDemandTimes(RequestManPowerApprovalRequestedNotification::class, 1);
        Notification::assertSentOnDemand(RequestManPowerStatusChangedNotification::class, function (
            RequestManPowerStatusChangedNotification $notification,
            array $channels,
            object $notifiable
        ): bool {
            return ($notifiable->routes['mail'] ?? null) === 'reject-requester@example.com';
        });
    }

    private function createRequestWithApprovers(?string $emailAddress = null): RequestManPower
    {
        $company = Company::query()->create([
            'name' => 'PT Cesa Approval Flow',
        ]);

        $division = Division::query()->create([
            'name'       => 'IT',
            'company_id' => $company->getKey(),
        ]);

        Approver::query()->create([
            'name'           => 'First Approver',
            'email'          => 'first.approver@example.com',
            'title'          => 'HRBP',
            'approval_order' => 1,
            'division_id'    => $division->getKey(),
            'is_active'      => true,
        ]);

        Approver::query()->create([
            'name'           => 'Second Approver',
            'email'          => 'second.approver@example.com',
            'title'          => 'Division Head',
            'approval_order' => 2,
            'division_id'    => $division->getKey(),
            'is_active'      => true,
        ]);

        return RequestManPower::query()->create([
            'company_id'                 => $company->getKey(),
            'division_id'                => $division->getKey(),
            'email_address'              => $emailAddress,
            'nama_pengaju'               => 'Andi Saputra',
            'posisi_pengaju'             => 'HR Manager',
            'tanggal_pengajuan'          => '2026-03-02',
            'posisi_dibutuhkan'          => 'Software Engineer',
            'lokasi_penempatan'          => 'Jakarta',
            'status_kebutuhan'           => StatusKebutuhan::NEW_HIRING,
            'divisi'                     => 'IT',
            'level_pekerjaan'            => 'Staff',
            'nama_karyawan_replacement'  => null,
            'jumlah_karyawan_dibutuhkan' => 1,
            'estimasi_tanggal_join'      => '2026-04-01',
            'requirements_kualifikasi'   => 'PHP, Laravel, SQL',
            'job_description'            => 'Develop internal systems',
            'keterangan'                 => 'Urgent hiring',
            'status'                     => RequestManPowerStatus::PENDING,
            'approved_by'                => null,
        ]);
    }
}
