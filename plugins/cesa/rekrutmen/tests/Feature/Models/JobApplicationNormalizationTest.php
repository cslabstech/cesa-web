<?php

namespace Cesa\Rekrutmen\Tests\Feature\Models;

use Cesa\Rekrutmen\Enums\JobApplicationGender;
use Cesa\Rekrutmen\Enums\JobApplicationMaritalStatus;
use Cesa\Rekrutmen\Enums\JobApplicationStatus;
use Cesa\Rekrutmen\Models\JobApplication;
use Cesa\Rekrutmen\Models\JobPosting;
use Cesa\Rekrutmen\Tests\RekrutmenTestCase;

class JobApplicationNormalizationTest extends RekrutmenTestCase
{
    public function test_transactional_input_is_normalized_before_persistence(): void
    {
        $jobPosting = JobPosting::query()->create([
            'title'        => 'Software Engineer',
            'slug'         => 'software-engineer-'.str()->lower(str()->random(6)),
            'description'  => 'Build systems',
            'requirements' => 'PHP, Laravel',
            'location'     => 'Jakarta',
            'is_published' => true,
        ]);

        $application = JobApplication::query()->create([
            'job_posting_id'             => $jobPosting->id,
            'full_name'                  => '  Budi   Santoso  ',
            'email'                      => '  BUDI.SANTOSO@Example.COM  ',
            'gender'                     => JobApplicationGender::Male,
            'birth_date'                 => '1995-01-10',
            'marital_status'             => JobApplicationMaritalStatus::Single,
            'address_ktp'                => '  Jl. Mawar   No. 10 , Jakarta  ',
            'address_domicile'           => "  Bekasi \n Selatan  ",
            'whatsapp_number'            => '  +62 812-0000-1111 ',
            'active_phone'               => ' (021) 555-000 ',
            'emergency_contact_name'     => ' bunga ',
            'emergency_contact_relation' => ' adik kandung ',
            'emergency_contact_phone'    => '08 12-3456-7890 ',
            'status'                     => JobApplicationStatus::IN_PROGRESS,
        ]);

        $application->refresh();

        $this->assertSame('BUDI SANTOSO', $application->full_name);
        $this->assertSame('budi.santoso@example.com', $application->email);
        $this->assertSame('JL. MAWAR NO. 10 , JAKARTA', $application->address_ktp);
        $this->assertSame('BEKASI SELATAN', $application->address_domicile);
        $this->assertSame('6281200001111', $application->whatsapp_number);
        $this->assertSame('6221555000', $application->active_phone);
        $this->assertSame('BUNGA', $application->emergency_contact_name);
        $this->assertSame('ADIK KANDUNG', $application->emergency_contact_relation);
        $this->assertSame('6281234567890', $application->emergency_contact_phone);
    }
}
