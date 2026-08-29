<?php

namespace Cesa\Rekrutmen\Tests\Feature;

use Cesa\Rekrutmen\Models\JobApplication;
use Cesa\Rekrutmen\Models\JobPosting;
use Cesa\Rekrutmen\Models\RekrutmenPipeline;
use Cesa\Rekrutmen\Models\RekrutmenStage;
use Cesa\Rekrutmen\Tests\RekrutmenTestCase;
use Webkul\Security\Models\User;

class RekrutmenSpaApplicationsTest extends RekrutmenTestCase
{
    public function test_can_fetch_applications_via_spa_api(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user);

        $pipeline = RekrutmenPipeline::firstOrCreate(['id' => 1], ['name' => 'Default Pipeline']);
        $stage = RekrutmenStage::firstOrCreate([
            'id'                    => 1,
            'rekrutmen_pipeline_id' => $pipeline->id,
        ], [
            'name'         => 'Screening CV',
            'order_column' => 1,
        ]);

        $posting = JobPosting::create([
            'title'                 => 'Backend Developer Test',
            'slug'                  => 'backend-dev-test-'.uniqid(),
            'rekrutmen_pipeline_id' => $pipeline->id,
            'is_published'          => true,
        ]);

        $app = JobApplication::create([
            'job_posting_id'   => $posting->id,
            'full_name'        => 'Budi Santoso',
            'email'            => 'budi@example.com',
            'current_stage_id' => $stage->id,
            'status'           => 'in_progress',
        ]);

        $response = $this->getJson('/rekrutmen/api/applications');

        $response->assertOk();
        $response->assertJsonStructure([
            'stages',
            'applications',
            'total',
        ]);

        $this->assertNotEmpty($response->json('applications'));
    }

    public function test_can_fetch_applications_filtered_by_job_id(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user);

        $pipeline = RekrutmenPipeline::firstOrCreate(['id' => 1], ['name' => 'Default Pipeline']);
        $stage = RekrutmenStage::firstOrCreate([
            'id'                    => 1,
            'rekrutmen_pipeline_id' => $pipeline->id,
        ], [
            'name'         => 'Screening CV',
            'order_column' => 1,
        ]);

        $posting = JobPosting::create([
            'title'                 => 'Web App Developer Cirebon',
            'slug'                  => 'web-app-developer-cirebon-'.uniqid(),
            'location'              => 'Cirebon',
            'rekrutmen_pipeline_id' => $pipeline->id,
            'is_published'          => true,
        ]);

        JobApplication::create([
            'job_posting_id'   => $posting->id,
            'full_name'        => 'Candidate Cirebon',
            'email'            => 'cirebon@example.com',
            'address_domicile' => 'Cirebon',
            'current_stage_id' => $stage->id,
            'status'           => 'in_progress',
        ]);

        $response = $this->getJson("/rekrutmen/api/applications?job_id={$posting->id}");

        $response->assertOk();
        $this->assertCount(1, $response->json('applications'));
        $this->assertEquals($posting->id, $response->json('active_job.id'));
        $this->assertEquals('Web App Developer Cirebon', $response->json('active_job.title'));
        $this->assertNotNull($response->json('applications.0.ai_match_score'));
    }
}
