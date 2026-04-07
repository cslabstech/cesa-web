<?php

namespace Cesa\Rekrutmen\Tests\Feature;

use Cesa\Rekrutmen\Livewire\PublicRequestManPowerForm;
use Cesa\Rekrutmen\Models\RequestManPower;
use Cesa\Rekrutmen\Tests\RekrutmenTestCase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

class PublicRequestManPowerFormTest extends RekrutmenTestCase
{
    public function test_public_request_man_power_form_uses_plain_textareas_for_long_text_fields(): void
    {
        app()->setLocale('en');

        $response = $this->get('/man-power');

        $fieldLabels = [
            __('rekrutmen::livewire/public-request-man-power-form.fields.nama_pengaju'),
            __('rekrutmen::livewire/public-request-man-power-form.fields.posisi_dibutuhkan'),
            __('rekrutmen::livewire/public-request-man-power-form.fields.requirements_kualifikasi'),
            __('rekrutmen::livewire/public-request-man-power-form.fields.job_description'),
        ];

        $response
            ->assertOk()
            ->assertDontSee('fi-fo-rich-editor', false);

        foreach ($fieldLabels as $fieldLabel) {
            $response->assertSee(e($fieldLabel), false);
        }
    }

    public function test_public_request_man_power_submission_falls_back_to_today_when_submission_date_state_is_missing(): void
    {
        Notification::fake();

        Livewire::test(PublicRequestManPowerForm::class)
            ->set('data.nama_pengaju', 'Andi Saputra')
            ->set('data.email_address', 'andi@example.com')
            ->set('data.posisi_pengaju', 'HR Manager')
            ->set('data.divisi', 'IT')
            ->set('data.badan_usaha', 'PT Cesa Indonesia')
            ->set('data.status_kebutuhan', 'New Hiring')
            ->set('data.posisi_dibutuhkan', 'Software Engineer')
            ->set('data.level_pekerjaan', 'Staff')
            ->set('data.jumlah_karyawan_dibutuhkan', 1)
            ->set('data.lokasi_penempatan', 'Jakarta')
            ->set('data.estimasi_tanggal_join', '2026-04-01')
            ->set('data.job_description', 'Develop internal systems')
            ->set('data.requirements_kualifikasi', 'PHP, Laravel, SQL')
            ->set('data.keterangan', 'Urgent hiring')
            ->set('data.tanggal_pengajuan', null)
            ->call('submit')
            ->assertHasNoErrors();

        $request = RequestManPower::query()->first();

        $this->assertNotNull($request);
        $this->assertSame(now()->toDateString(), $request->tanggal_pengajuan?->toDateString());
    }

    public function test_public_request_man_power_validation_dispatches_feedback_events(): void
    {
        Livewire::test(PublicRequestManPowerForm::class)
            ->set('data.nama_pengaju', 'Andi Saputra')
            ->set('data.email_address', 'andi@example.com')
            ->set('data.posisi_pengaju', 'HR Manager')
            ->set('data.divisi', 'IT')
            ->set('data.badan_usaha', 'PT Cesa Indonesia')
            ->set('data.status_kebutuhan', 'Replacement')
            ->set('data.posisi_dibutuhkan', 'Software Engineer')
            ->set('data.level_pekerjaan', 'Staff')
            ->set('data.jumlah_karyawan_dibutuhkan', 1)
            ->set('data.lokasi_penempatan', 'Jakarta')
            ->set('data.estimasi_tanggal_join', '2026-04-01')
            ->set('data.job_description', 'Develop internal systems')
            ->set('data.requirements_kualifikasi', 'PHP, Laravel, SQL')
            ->set('data.keterangan', 'Urgent hiring')
            ->call('submit')
            ->assertHasErrors([
                'data.nama_karyawan_replacement',
            ])
            ->assertDispatched('form-errors-presented')
            ->assertDispatched('form-processing-finished');
    }

    public function test_public_request_man_power_submission_ignores_client_supplied_submission_date(): void
    {
        Notification::fake();

        Livewire::test(PublicRequestManPowerForm::class)
            ->set('data.nama_pengaju', 'Andi Saputra')
            ->set('data.email_address', 'andi@example.com')
            ->set('data.posisi_pengaju', 'HR Manager')
            ->set('data.divisi', 'IT')
            ->set('data.badan_usaha', 'PT Cesa Indonesia')
            ->set('data.status_kebutuhan', 'New Hiring')
            ->set('data.posisi_dibutuhkan', 'Software Engineer')
            ->set('data.level_pekerjaan', 'Staff')
            ->set('data.jumlah_karyawan_dibutuhkan', 1)
            ->set('data.lokasi_penempatan', 'Jakarta')
            ->set('data.estimasi_tanggal_join', '2026-04-01')
            ->set('data.job_description', 'Develop internal systems')
            ->set('data.requirements_kualifikasi', 'PHP, Laravel, SQL')
            ->set('data.keterangan', 'Urgent hiring')
            ->set('data.tanggal_pengajuan', '2000-01-01')
            ->call('submit')
            ->assertHasNoErrors();

        $request = RequestManPower::query()->first();

        $this->assertNotNull($request);
        $this->assertSame(now()->toDateString(), $request->tanggal_pengajuan?->toDateString());
    }
}
