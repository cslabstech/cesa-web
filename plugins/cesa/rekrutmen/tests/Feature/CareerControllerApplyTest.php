<?php

namespace Cesa\Rekrutmen\Tests\Feature;

use Cesa\Rekrutmen\Models\JobApplication;
use Cesa\Rekrutmen\Models\JobPosting;
use Cesa\Rekrutmen\Models\RekrutmenPipeline;
use Cesa\Rekrutmen\Models\RekrutmenStage;
use Cesa\Rekrutmen\Tests\RekrutmenTestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CareerControllerApplyTest extends RekrutmenTestCase
{
    public function test_public_apply_stores_the_new_candidate_profile_fields(): void
    {
        Storage::fake('local');

        config()->set('filesystems.default', 'local');
        config()->set('filament.default_filesystem_disk', 'local');

        $pipeline = RekrutmenPipeline::query()->create([
            'name' => 'Hiring Pipeline',
        ]);

        $firstStage = RekrutmenStage::query()->create([
            'rekrutmen_pipeline_id' => $pipeline->id,
            'name'                  => 'Screening CV',
            'order_column'          => 1,
        ]);

        $jobPosting = JobPosting::query()->create([
            'rekrutmen_pipeline_id' => $pipeline->id,
            'title'                 => 'Backend Developer',
            'slug'                  => 'backend-developer',
            'description'           => 'Build APIs',
            'requirements'          => 'Laravel',
            'location'              => 'Jakarta',
            'is_published'          => true,
        ]);

        $response = $this->post("/api/jobs/{$jobPosting->slug}/apply", [
            'full_name'                  => 'Budi Santoso',
            'email'                      => 'budi@example.com',
            'gender'                     => 'male',
            'birth_date'                 => '1995-01-10',
            'marital_status'             => 'single',
            'address_ktp'                => 'Jl. KTP No. 1, Jakarta',
            'address_domicile'           => 'Jl. Domisili No. 2, Bekasi',
            'whatsapp_number'            => '081200000001',
            'active_phone'               => '081200000002',
            'emergency_contact_name'     => 'Bunga',
            'emergency_contact_relation' => 'Adik Kandung',
            'emergency_contact_phone'    => '081200000003',
            'photo'                      => UploadedFile::fake()->image('photo.jpg'),
            'resume'                     => UploadedFile::fake()->create('resume.pdf', 120, 'application/pdf'),
        ]);

        $response->assertCreated();

        $application = JobApplication::query()->where('email', 'budi@example.com')->first();

        $this->assertNotNull($application);
        $this->assertSame($jobPosting->id, $application->job_posting_id);
        $this->assertSame($firstStage->id, $application->current_stage_id);
        $this->assertSame('BUDI SANTOSO', $application->full_name);
        $this->assertSame('male', $application->gender?->value);
        $this->assertSame('single', $application->marital_status?->value);
        $this->assertSame('6281200000001', $application->whatsapp_number);
        $this->assertSame('6281200000002', $application->active_phone);
        $this->assertSame('BUNGA', $application->emergency_contact_name);
        $this->assertSame('ADIK KANDUNG', $application->emergency_contact_relation);
        $this->assertSame('6281200000003', $application->emergency_contact_phone);
        $this->assertNotNull($application->photo_path);
        $this->assertNotNull($application->resume_path);

        Storage::disk('local')->assertExists((string) $application->photo_path);
        Storage::disk('local')->assertExists((string) $application->resume_path);
    }

    public function test_public_apply_requires_photo_and_resume(): void
    {
        $jobPosting = JobPosting::query()->create([
            'title'        => 'Backend Developer',
            'slug'         => 'backend-developer-validation',
            'description'  => 'Build APIs',
            'requirements' => 'Laravel',
            'location'     => 'Jakarta',
            'is_published' => true,
        ]);

        $response = $this->postJson("/api/jobs/{$jobPosting->slug}/apply", [
            'full_name'                  => 'Budi Santoso',
            'email'                      => 'budi@example.com',
            'gender'                     => 'male',
            'birth_date'                 => '1995-01-10',
            'marital_status'             => 'single',
            'address_ktp'                => 'Jl. KTP No. 1, Jakarta',
            'address_domicile'           => 'Jl. Domisili No. 2, Bekasi',
            'whatsapp_number'            => '081200000001',
            'active_phone'               => '081200000002',
            'emergency_contact_name'     => 'Bunga',
            'emergency_contact_relation' => 'Adik Kandung',
            'emergency_contact_phone'    => '081200000003',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'photo',
                'resume',
            ]);
    }
}
