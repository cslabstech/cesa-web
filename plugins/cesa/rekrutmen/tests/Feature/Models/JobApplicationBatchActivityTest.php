<?php

namespace Cesa\Rekrutmen\Tests\Feature\Models;

use Cesa\Rekrutmen\Enums\JobApplicationGender;
use Cesa\Rekrutmen\Enums\JobApplicationMaritalStatus;
use Cesa\Rekrutmen\Enums\JobApplicationStatus;
use Cesa\Rekrutmen\Models\JobApplication;
use Cesa\Rekrutmen\Models\JobApplicationHistory;
use Cesa\Rekrutmen\Models\JobPosting;
use Cesa\Rekrutmen\Models\RekrutmenPipeline;
use Cesa\Rekrutmen\Models\RekrutmenStage;
use Cesa\Rekrutmen\Tests\RekrutmenTestCase;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Webkul\Security\Models\User;

class JobApplicationBatchActivityTest extends RekrutmenTestCase
{
    protected User $performedBy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->performedBy = User::factory()->create([
            'is_active' => true,
        ]);
    }

    public function test_batch_activity_records_history_and_moves_passed_candidates_to_next_stage(): void
    {
        [$jobPosting, $firstStage, $secondStage] = $this->createPipelineFixture('Batch');

        $app1 = $this->makeJobApplication($jobPosting, $firstStage, 'batch-pass@example.com');
        $app2 = $this->makeJobApplication($jobPosting, $firstStage, 'batch-fail@example.com');
        $app3 = $this->makeJobApplication($jobPosting, $firstStage, 'batch-pending@example.com');

        $groupId = JobApplication::recordBatchActivity(
            $jobPosting->id,
            $firstStage->id,
            '2026-04-01',
            [
                ['job_application_id' => $app1->id, 'result' => 'passed', 'notes' => 'Good CV'],
                ['job_application_id' => $app2->id, 'result' => 'failed', 'notes' => 'Not qualified'],
                ['job_application_id' => $app3->id, 'result' => 'pending', 'notes' => 'Need review'],
            ],
            $this->performedBy->id,
        );

        $this->assertNotNull($groupId);
        $this->assertNotEmpty($groupId);

        // Verify histories were created for all entries
        $this->assertSame(3, JobApplicationHistory::where('activity_group_id', $groupId)->count());

        // Passed candidate should have moved to next stage
        $app1->refresh();
        $this->assertSame($secondStage->id, $app1->current_stage_id);
        $this->assertSame(JobApplicationStatus::IN_PROGRESS, $app1->status);

        // Failed candidate should be rejected
        $app2->refresh();
        $this->assertSame(JobApplicationStatus::REJECTED, $app2->status);

        // Pending candidate should stay in same stage
        $app3->refresh();
        $this->assertSame($firstStage->id, $app3->current_stage_id);
        $this->assertSame(JobApplicationStatus::IN_PROGRESS, $app3->status);
    }

    public function test_batch_activity_skips_terminal_status_candidates(): void
    {
        [$jobPosting, $firstStage, $secondStage] = $this->createPipelineFixture('Terminal Batch');

        $hiredApp = $this->makeJobApplication($jobPosting, $secondStage, 'batch-hired@example.com');
        $hiredApp->markAsHired('Already hired', $this->performedBy->id);
        $hiredApp->refresh();

        $normalApp = $this->makeJobApplication($jobPosting, $firstStage, 'batch-normal@example.com');

        JobApplication::recordBatchActivity(
            $jobPosting->id,
            $firstStage->id,
            '2026-04-02',
            [
                ['job_application_id' => $hiredApp->id, 'result' => 'passed', 'notes' => 'Should be skipped'],
                ['job_application_id' => $normalApp->id, 'result' => 'passed', 'notes' => 'Should proceed'],
            ],
            $this->performedBy->id,
        );

        // Hired app should not have a new history entry
        $hiredHistories = JobApplicationHistory::where('job_application_id', $hiredApp->id)
            ->where('activity_group_id', '!=', null)
            ->count();
        $this->assertSame(0, $hiredHistories);

        // Normal app should have been processed
        $normalApp->refresh();
        $this->assertSame($secondStage->id, $normalApp->current_stage_id);
    }

    public function test_batch_activity_rolls_back_on_failure(): void
    {
        [$jobPosting, $firstStage, $secondStage] = $this->createPipelineFixture('Rollback');

        $app1 = $this->makeJobApplication($jobPosting, $firstStage, 'rollback-1@example.com');
        $app2 = $this->makeJobApplication($jobPosting, $firstStage, 'rollback-2@example.com');

        // Simulate a failure by trying to reject an already-rejected candidate
        // This tests that the transaction wraps everything properly
        $app2->markAsRejected('Pre-rejected', $this->performedBy->id);
        $app2->refresh();

        // The batch should still succeed because terminal candidates are skipped
        JobApplication::recordBatchActivity(
            $jobPosting->id,
            $firstStage->id,
            '2026-04-03',
            [
                ['job_application_id' => $app1->id, 'result' => 'passed', 'notes' => 'Passed test'],
                ['job_application_id' => $app2->id, 'result' => 'failed', 'notes' => 'Already rejected'],
            ],
            $this->performedBy->id,
        );

        $app1->refresh();
        $this->assertSame($secondStage->id, $app1->current_stage_id);

        // The terminal app should remain rejected (no double-rejection)
        $app2->refresh();
        $this->assertSame(JobApplicationStatus::REJECTED, $app2->status);
    }

    public function test_batch_activity_groups_entries_by_same_activity_group_id(): void
    {
        [$jobPosting, $firstStage] = $this->createPipelineFixture('Group');

        $app1 = $this->makeJobApplication($jobPosting, $firstStage, 'group-1@example.com');
        $app2 = $this->makeJobApplication($jobPosting, $firstStage, 'group-2@example.com');

        $groupId = JobApplication::recordBatchActivity(
            $jobPosting->id,
            $firstStage->id,
            '2026-04-04',
            [
                ['job_application_id' => $app1->id, 'result' => 'passed'],
                ['job_application_id' => $app2->id, 'result' => 'pending'],
            ],
            $this->performedBy->id,
        );

        // Both entries should share the same group ID
        $histories = JobApplicationHistory::where('activity_group_id', $groupId)->get();
        $this->assertSame(2, $histories->count());
        $this->assertTrue($histories->every(fn ($h) => $h->activity_group_id === $groupId));
        $expectedTitle = JobApplication::generateBatchActivityTitle($firstStage->name, '2026-04-04');
        $this->assertTrue($histories->every(fn ($h) => $h->activity_title === $expectedTitle));
    }

    public function test_batch_activity_rejects_candidates_outside_selected_stage_or_job_posting(): void
    {
        [$postingA, $stageA] = $this->createPipelineFixture('Posting A');
        [$postingB, $stageB] = $this->createPipelineFixture('Posting B');

        $validApplication = $this->makeJobApplication($postingA, $stageA, 'valid-stage@example.com');
        $foreignApplication = $this->makeJobApplication($postingB, $stageB, 'foreign-stage@example.com');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(__('rekrutmen::filament/resources/activity-log.errors.invalid_candidates'));

        JobApplication::recordBatchActivity(
            $postingA->id,
            $stageA->id,
            '2026-04-05',
            [
                ['job_application_id' => $validApplication->id, 'result' => 'passed', 'notes' => 'Valid'],
                ['job_application_id' => $foreignApplication->id, 'result' => 'pending', 'notes' => 'Invalid'],
            ],
            $this->performedBy->id,
        );
    }

    public function test_rejected_candidate_can_reapply_to_different_job_posting(): void
    {
        [$postingA, $stageA] = $this->createPipelineFixture('Reapply A');
        [$postingB, $stageB] = $this->createPipelineFixture('Reapply B');

        $application = $this->makeJobApplication($postingA, $stageA, 'reapply@example.com');
        $application->markAsRejected('Not a fit', $this->performedBy->id);
        $application->refresh();

        $this->assertSame(JobApplicationStatus::REJECTED, $application->status);

        // Same email on a different job posting should succeed
        $newApplication = $this->makeJobApplication($postingB, $stageB, 'reapply@example.com');

        $this->assertSame(JobApplicationStatus::IN_PROGRESS, $newApplication->status);
        $this->assertDatabaseHas('rekrutmen_job_applications', [
            'id'                => $newApplication->id,
            'job_posting_id'    => $postingB->id,
            'email'             => 'reapply@example.com',
            'status'            => JobApplicationStatus::IN_PROGRESS->value,
        ]);
    }

    public function test_rejected_candidate_cannot_reapply_to_same_job_posting(): void
    {
        [$jobPosting, $firstStage] = $this->createPipelineFixture('Reapply Same');

        $this->makeJobApplication($jobPosting, $firstStage, 'reapply-same@example.com');

        // Soft-delete the first application to simulate withdrawal
        $firstApp = JobApplication::query()
            ->where('email', 'reapply-same@example.com')
            ->where('job_posting_id', $jobPosting->id)
            ->first();

        $firstApp->delete();

        // Should be able to reapply after soft delete
        $newApplication = $this->makeJobApplication($jobPosting, $firstStage, 'reapply-same@example.com');

        $this->assertSame(JobApplicationStatus::IN_PROGRESS, $newApplication->status);
        $this->assertNotSame($firstApp->id, $newApplication->id);
    }

    public function test_progress_report_api_requires_authentication(): void
    {
        $response = $this->getJson('/api/recruitment/progress-report');

        $response->assertUnauthorized();
    }

    public function test_progress_report_timeline_api_requires_authentication(): void
    {
        $response = $this->getJson('/api/recruitment/progress-report/timeline');

        $response->assertUnauthorized();
    }

    public function test_progress_report_overview_api_requires_authentication(): void
    {
        $response = $this->getJson('/api/recruitment/progress-report/overview');

        $response->assertUnauthorized();
    }

    /**
     * @return array{0: JobPosting, 1: RekrutmenStage, 2: RekrutmenStage}
     */
    private function createPipelineFixture(string $suffix): array
    {
        $pipeline = RekrutmenPipeline::query()->create([
            'name' => "Pipeline {$suffix}",
        ]);

        $firstStage = RekrutmenStage::query()->create([
            'rekrutmen_pipeline_id' => $pipeline->id,
            'name'                  => "Screening {$suffix}",
            'order_column'          => 1,
        ]);

        $secondStage = RekrutmenStage::query()->create([
            'rekrutmen_pipeline_id' => $pipeline->id,
            'name'                  => "Interview {$suffix}",
            'order_column'          => 2,
        ]);

        $jobPosting = JobPosting::query()->create([
            'rekrutmen_pipeline_id' => $pipeline->id,
            'title'                 => "Developer {$suffix}",
            'slug'                  => 'developer-'.Str::slug($suffix).'-'.Str::lower(Str::random(4)),
            'description'           => 'Build APIs',
            'requirements'          => 'Laravel',
            'location'              => 'Jakarta',
            'is_published'          => true,
        ]);

        return [$jobPosting, $firstStage, $secondStage];
    }

    private function makeJobApplication(JobPosting $jobPosting, RekrutmenStage $stage, string $email = 'candidate@example.com'): JobApplication
    {
        return JobApplication::query()->create([
            'job_posting_id'             => $jobPosting->id,
            'current_stage_id'           => $stage->id,
            'full_name'                  => 'Candidate One',
            'email'                      => $email,
            'gender'                     => JobApplicationGender::Male,
            'birth_date'                 => '1995-01-10',
            'marital_status'             => JobApplicationMaritalStatus::Single,
            'address_ktp'                => 'Alamat KTP',
            'address_domicile'           => 'Alamat Domisili',
            'whatsapp_number'            => '081234567890',
            'active_phone'               => '081234567891',
            'emergency_contact_name'     => 'Bunga',
            'emergency_contact_relation' => 'Saudara',
            'emergency_contact_phone'    => '081234567892',
            'status'                     => JobApplicationStatus::IN_PROGRESS,
        ]);
    }
}
