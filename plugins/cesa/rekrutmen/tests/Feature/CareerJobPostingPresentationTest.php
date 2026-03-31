<?php

namespace Cesa\Rekrutmen\Tests\Feature;

use Cesa\Rekrutmen\Http\Controllers\Api\CareerController;
use Cesa\Rekrutmen\Models\JobPosting;
use Cesa\Rekrutmen\Tests\RekrutmenTestCase;
use Illuminate\Support\Facades\Storage;

class CareerJobPostingPresentationTest extends RekrutmenTestCase
{
    public function test_job_listing_and_detail_include_thumbnail_url(): void
    {
        Storage::fake('public');

        Storage::disk('public')->put('rekrutmen/job-postings/backend-thumb.jpg', 'image');

        $jobPosting = JobPosting::query()->create([
            'title'          => 'Backend Developer',
            'slug'           => 'backend-developer-thumbnail',
            'description'    => 'Build APIs and internal tools.',
            'requirements'   => 'Laravel and SQL',
            'location'       => 'Jakarta',
            'thumbnail_path' => 'rekrutmen/job-postings/backend-thumb.jpg',
            'is_published'   => true,
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
}
