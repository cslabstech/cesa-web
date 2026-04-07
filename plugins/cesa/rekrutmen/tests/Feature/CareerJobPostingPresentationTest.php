<?php

namespace Cesa\Rekrutmen\Tests\Feature;

use Cesa\Rekrutmen\Http\Controllers\Api\CareerController;
use Cesa\Rekrutmen\Models\JobPosting;
use Cesa\Rekrutmen\Models\RekrutmenPipeline;
use Cesa\Rekrutmen\Models\RekrutmenStage;
use Cesa\Rekrutmen\Tests\RekrutmenTestCase;
use Illuminate\Support\Facades\Storage;

class CareerJobPostingPresentationTest extends RekrutmenTestCase
{
    public function test_job_listing_and_detail_include_thumbnail_url(): void
    {
        Storage::fake('public');

        Storage::disk('public')->put('rekrutmen/job-postings/backend-thumb.jpg', 'image');

        $jobPosting = $this->createReadyJobPosting([
            'title'          => 'Backend Developer',
            'slug'           => 'backend-developer-thumbnail',
            'description'    => 'Build APIs and internal tools.',
            'requirements'   => 'Laravel and SQL',
            'location'       => 'Jakarta',
            'thumbnail_path' => 'rekrutmen/job-postings/backend-thumb.jpg',
        ]);

        $indexPayload = app(CareerController::class)->index()->getData(true);
        $detailPayload = app(CareerController::class)->show($jobPosting->slug)->getData(true);

        $this->assertStringContainsString(
            'rekrutmen/job-postings/backend-thumb.jpg',
            $indexPayload['data'][0]['thumbnail_url'] ?? ''
        );
        $this->assertStringContainsString(
            'rekrutmen/job-postings/backend-thumb.jpg',
            $detailPayload['data']['thumbnail_url'] ?? ''
        );
    }

    public function test_expired_job_posting_is_hidden_from_listing_and_detail(): void
    {
        $jobPosting = $this->createReadyJobPosting([
            'title'        => 'Expired Backend Developer',
            'slug'         => 'expired-backend-developer',
            'description'  => 'Build APIs and internal tools.',
            'requirements' => 'Laravel and SQL',
            'location'     => 'Jakarta',
            'closing_date' => now()->subDay()->toDateString(),
        ]);

        $indexPayload = app(CareerController::class)->index()->getData(true);
        $detailResponse = app(CareerController::class)->show($jobPosting->slug);

        $this->assertFalse(collect($indexPayload['data'])->contains(
            fn (array $job): bool => ($job['slug'] ?? null) === $jobPosting->slug
        ));
        $this->assertSame(404, $detailResponse->getStatusCode());
    }

    public function test_job_posting_remains_visible_through_its_closing_date(): void
    {
        $jobPosting = $this->createReadyJobPosting([
            'title'        => 'Closing Today Backend Developer',
            'slug'         => 'closing-today-backend-developer',
            'description'  => 'Build APIs and internal tools.',
            'requirements' => 'Laravel and SQL',
            'location'     => 'Jakarta',
            'closing_date' => today()->toDateString(),
        ]);

        $indexPayload = app(CareerController::class)->index()->getData(true);
        $detailResponse = app(CareerController::class)->show($jobPosting->slug);

        $this->assertTrue(collect($indexPayload['data'])->contains(
            fn (array $job): bool => ($job['slug'] ?? null) === $jobPosting->slug
        ));
        $this->assertSame(200, $detailResponse->getStatusCode());
    }

    public function test_stage_less_job_posting_is_hidden_from_listing_and_detail(): void
    {
        $pipeline = RekrutmenPipeline::query()->create([
            'name' => 'Stage Less Pipeline',
        ]);

        $jobPosting = JobPosting::query()->create([
            'rekrutmen_pipeline_id' => $pipeline->id,
            'title'                 => 'Stage Less Backend Developer',
            'slug'                  => 'stage-less-backend-developer',
            'description'           => 'Build APIs and internal tools.',
            'requirements'          => 'Laravel and SQL',
            'location'              => 'Jakarta',
            'is_published'          => true,
        ]);

        $indexPayload = app(CareerController::class)->index()->getData(true);
        $detailResponse = app(CareerController::class)->show($jobPosting->slug);

        $this->assertFalse(collect($indexPayload['data'])->contains(
            fn (array $job): bool => ($job['slug'] ?? null) === $jobPosting->slug
        ));
        $this->assertSame(404, $detailResponse->getStatusCode());
    }

    private function createReadyJobPosting(array $attributes): JobPosting
    {
        $pipeline = RekrutmenPipeline::query()->create([
            'name' => 'Presentation Pipeline '.str()->lower(str()->random(5)),
        ]);

        RekrutmenStage::query()->create([
            'rekrutmen_pipeline_id' => $pipeline->id,
            'name'                  => 'Screening',
            'order_column'          => 1,
        ]);

        return JobPosting::query()->create([
            'rekrutmen_pipeline_id' => $pipeline->id,
            'title'                 => 'Backend Developer',
            'slug'                  => 'backend-developer',
            'description'           => 'Build APIs',
            'requirements'          => 'Laravel',
            'location'              => 'Jakarta',
            'is_published'          => true,
            ...$attributes,
        ]);
    }
}
