<?php

namespace Cesa\Rekrutmen\Tests\Feature;

use Cesa\Rekrutmen\Enums\RequestManPowerApprovalStatus;
use Cesa\Rekrutmen\Enums\RequestManPowerStatus;
use Cesa\Rekrutmen\Enums\StatusKebutuhan;
use Cesa\Rekrutmen\Livewire\PublicRequestManPowerProgressPage;
use Cesa\Rekrutmen\Models\RequestManPower;
use Cesa\Rekrutmen\Tests\RekrutmenTestCase;
use Livewire\Livewire;

class PublicRequestManPowerProgressLookupTest extends RekrutmenTestCase
{
    public function test_public_manpower_progress_lookup_form_is_available_without_response_token(): void
    {
        $response = $this->get('/man-power/progress');

        $response
            ->assertOk()
            ->assertSee('Cek progress request man power')
            ->assertSee('Email Pengaju')
            ->assertDontSee('ID Tracking (opsional)');

        expect(substr_count($response->getContent(), 'x-data="{ expanded: true }"'))
            ->toBeGreaterThanOrEqual(1);
    }

    public function test_public_manpower_progress_lookup_lists_requests_for_email(): void
    {
        $first = $this->createRequestManPower([
            'email_address'      => 'requester@example.com',
            'posisi_dibutuhkan'  => 'Software Engineer',
            'status_response_id' => 'tracking-token-001',
        ]);

        $second = $this->createRequestManPower([
            'email_address'      => 'requester@example.com',
            'posisi_dibutuhkan'  => 'Finance Staff',
            'status_response_id' => 'tracking-token-002',
        ]);

        $this->createRequestManPower([
            'email_address'      => 'other@example.com',
            'posisi_dibutuhkan'  => 'HR Staff',
            'status_response_id' => 'tracking-token-003',
        ]);

        Livewire::test(PublicRequestManPowerProgressPage::class)
            ->set('lookupEmail', 'requester@example.com')
            ->call('lookup')
            ->assertSee('Software Engineer')
            ->assertSee('Finance Staff')
            ->assertDontSee('HR Staff')
            ->assertSee(route('rekrutmen.public.request-man-power.progress', [
                'response' => $first->status_response_id,
            ], false))
            ->assertSee(route('rekrutmen.public.request-man-power.progress', [
                'response' => $second->status_response_id,
            ], false));
    }

    public function test_public_manpower_progress_token_url_still_renders_request_status(): void
    {
        $request = $this->createRequestManPower([
            'posisi_dibutuhkan'  => 'Backend Engineer',
            'status_response_id' => 'tracking-token-789',
        ]);

        $request->approvals()->create([
            'approver_name'      => 'Approval Manager',
            'approver_email'     => 'approval.manager@example.com',
            'approver_title'     => 'Manager',
            'step_order'         => 1,
            'status'             => RequestManPowerApprovalStatus::PENDING,
            'action_token'       => 'progress-approval-token',
            'action_expires_at'  => now()->addDay(),
        ]);

        $response = $this->get('/man-power/progress/'.$request->status_response_id);

        $response
            ->assertOk()
            ->assertSee('Backend Engineer')
            ->assertSee('Status saat ini');

        expect(substr_count($response->getContent(), 'x-data="{ expanded: true }"'))
            ->toBeGreaterThanOrEqual(2);
    }

    private function createRequestManPower(array $attributes = []): RequestManPower
    {
        return RequestManPower::query()->create(array_merge([
            'email_address'              => 'requester@example.com',
            'nama_pengaju'               => 'Andi Saputra',
            'posisi_pengaju'             => 'HR Manager',
            'tanggal_pengajuan'          => '2026-04-01',
            'posisi_dibutuhkan'          => 'Software Engineer',
            'lokasi_penempatan'          => 'Jakarta',
            'status_kebutuhan'           => StatusKebutuhan::NEW_HIRING,
            'divisi'                     => 'IT',
            'level_pekerjaan'            => 'Staff',
            'nama_karyawan_replacement'  => null,
            'jumlah_karyawan_dibutuhkan' => 1,
            'estimasi_tanggal_join'      => '2026-05-01',
            'requirements_kualifikasi'   => 'PHP, Laravel, SQL',
            'job_description'            => 'Develop internal systems',
            'keterangan'                 => 'Urgent hiring',
            'status'                     => RequestManPowerStatus::PENDING,
            'approved_by'                => null,
        ], $attributes));
    }
}
