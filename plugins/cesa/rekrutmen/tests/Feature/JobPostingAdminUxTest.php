<?php

namespace Cesa\Rekrutmen\Tests\Feature;

use Cesa\Rekrutmen\Enums\RequestManPowerStatus;
use Cesa\Rekrutmen\Enums\StatusKebutuhan;
use Cesa\Rekrutmen\Filament\Resources\JobPostingResource;
use Cesa\Rekrutmen\Models\RekrutmenPipeline;
use Cesa\Rekrutmen\Models\RequestManPower;
use Cesa\Rekrutmen\Tests\RekrutmenTestCase;
use Webkul\Support\Models\Company;

class JobPostingAdminUxTest extends RekrutmenTestCase
{
    public function test_job_posting_autofill_data_can_be_derived_from_manpower_request(): void
    {
        $pipeline = RekrutmenPipeline::query()->create([
            'name' => 'Preferred Pipeline',
        ]);

        $company = Company::query()->create([
            'name' => 'PT CESA',
        ]);

        $requestManPower = RequestManPower::query()->create([
            'company_id'                 => $company->id,
            'email_address'              => 'autofill@example.com',
            'nama_pengaju'               => 'Autofill Requester',
            'posisi_pengaju'             => 'HR Manager',
            'tanggal_pengajuan'          => now()->toDateString(),
            'posisi_dibutuhkan'          => 'Product Designer',
            'lokasi_penempatan'          => 'Jakarta',
            'status_kebutuhan'           => StatusKebutuhan::NEW_HIRING,
            'divisi'                     => 'Product',
            'level_pekerjaan'            => 'Staff',
            'jumlah_karyawan_dibutuhkan' => 2,
            'estimasi_tanggal_join'      => '2026-06-15',
            'requirements_kualifikasi'   => 'Figma and portfolio',
            'job_description'            => 'Design product experiences',
            'status'                     => RequestManPowerStatus::PENDING,
        ]);

        $requestManPower->createJobPostingIfMissing()->update([
            'rekrutmen_pipeline_id' => $pipeline->id,
        ]);

        $autofillData = JobPostingResource::resolveAutofillDataFromRequestManPower($requestManPower->fresh(['jobPosting']));

        $this->assertSame('Product Designer Jakarta', $autofillData['title']);
        $this->assertSame('product-designer-jakarta', $autofillData['slug']);
        $this->assertSame('Jakarta', $autofillData['location']);
        $this->assertSame('Design product experiences', $autofillData['description']);
        $this->assertSame('Figma and portfolio', $autofillData['requirements']);
        $this->assertSame('2026-06-15', $autofillData['closing_date']);
        $this->assertSame($pipeline->id, $autofillData['rekrutmen_pipeline_id']);
    }
}
