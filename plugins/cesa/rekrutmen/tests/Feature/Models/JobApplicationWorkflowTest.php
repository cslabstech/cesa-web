<?php

namespace Cesa\Rekrutmen\Tests\Feature\Models;

use Cesa\Rekrutmen\Enums\JobApplicationGender;
use Cesa\Rekrutmen\Enums\JobApplicationMaritalStatus;
use Cesa\Rekrutmen\Enums\JobApplicationStatus;
use Cesa\Rekrutmen\Models\JobApplication;
use Cesa\Rekrutmen\Models\JobPosting;
use Cesa\Rekrutmen\Models\RekrutmenPipeline;
use Cesa\Rekrutmen\Models\RekrutmenStage;
use Cesa\Rekrutmen\Tests\RekrutmenTestCase;
use InvalidArgumentException;

class JobApplicationWorkflowTest extends RekrutmenTestCase
{
    public function test_transition_to_stage_records_history_and_validates_pipeline(): void
    {
        [$jobPosting, $firstStage, $secondStage] = $this->createPipelineFixture('Main');
        [, $foreignStage] = $this->createPipelineFixture('Foreign');

        $application = $this->makeJobApplication($jobPosting, $firstStage);

        $application->transitionToStage($secondStage->id, 'Move to interview');

        $application->refresh();

        $this->assertSame($secondStage->id, $application->current_stage_id);
        $this->assertNotNull($application->position);
        $this->assertDatabaseHas('rekrutmen_job_application_histories', [
            'job_application_id' => $application->id,
            'from_stage_id'      => $firstStage->id,
            'to_stage_id'        => $secondStage->id,
            'status'             => JobApplicationStatus::IN_PROGRESS->value,
            'notes'              => 'Move to interview',
        ]);

        $this->expectException(InvalidArgumentException::class);

        $application->transitionToStage($foreignStage->id);
    }

    public function test_marking_candidate_as_hired_and_rejected_records_history(): void
    {
        [$jobPosting, $firstStage] = $this->createPipelineFixture('Decision');

        $hiredApplication = $this->makeJobApplication($jobPosting, $firstStage, 'hired@example.com');
        $hiredApplication->markAsHired('Accepted');
        $hiredApplication->refresh();

        $this->assertSame(JobApplicationStatus::HIRED, $hiredApplication->status);
        $this->assertDatabaseHas('rekrutmen_job_application_histories', [
            'job_application_id' => $hiredApplication->id,
            'from_stage_id'      => $firstStage->id,
            'to_stage_id'        => $firstStage->id,
            'status'             => JobApplicationStatus::HIRED->value,
            'notes'              => 'Accepted',
        ]);

        $rejectedApplication = $this->makeJobApplication($jobPosting, $firstStage, 'rejected@example.com');
        $rejectedApplication->markAsRejected('Rejected');
        $rejectedApplication->refresh();

        $this->assertSame(JobApplicationStatus::REJECTED, $rejectedApplication->status);
        $this->assertDatabaseHas('rekrutmen_job_application_histories', [
            'job_application_id' => $rejectedApplication->id,
            'from_stage_id'      => $firstStage->id,
            'to_stage_id'        => $firstStage->id,
            'status'             => JobApplicationStatus::REJECTED->value,
            'notes'              => 'Rejected',
        ]);
    }

    public function test_marking_candidate_as_hired_requires_notes(): void
    {
        [$jobPosting, $firstStage] = $this->createPipelineFixture('Required Notes');

        $hiredApplication = $this->makeJobApplication($jobPosting, $firstStage, 'required-hired@example.com');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(__('rekrutmen::filament/resources/job-application.workflow_errors.decision_note_required'));
        $hiredApplication->markAsHired();
    }

    public function test_marking_candidate_as_rejected_requires_notes(): void
    {
        [$jobPosting, $firstStage] = $this->createPipelineFixture('Required Notes Reject');

        $rejectedApplication = $this->makeJobApplication($jobPosting, $firstStage, 'required-rejected@example.com');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(__('rekrutmen::filament/resources/job-application.workflow_errors.decision_note_required'));
        $rejectedApplication->markAsRejected();
    }

    public function test_sync_current_stage_to_job_posting_uses_first_stage_of_target_pipeline(): void
    {
        [$firstJobPosting, $firstStage] = $this->createPipelineFixture('Alpha');
        [$secondJobPosting, $targetStage] = $this->createPipelineFixture('Beta');

        $application = $this->makeJobApplication($firstJobPosting, $firstStage);

        $application->update([
            'job_posting_id' => $secondJobPosting->id,
        ]);

        $application->syncCurrentStageToJobPosting(notes: 'Sync stage');
        $application->refresh();

        $this->assertSame($targetStage->id, $application->current_stage_id);
        $this->assertDatabaseHas('rekrutmen_job_application_histories', [
            'job_application_id' => $application->id,
            'from_stage_id'      => $firstStage->id,
            'to_stage_id'        => $targetStage->id,
            'status'             => JobApplicationStatus::IN_PROGRESS->value,
            'notes'              => 'Sync stage',
        ]);
    }

    public function test_new_application_gets_position_for_board_ordering(): void
    {
        [$jobPosting, $firstStage] = $this->createPipelineFixture('Position');

        $firstApplication = $this->makeJobApplication($jobPosting, $firstStage, 'first@example.com');
        $secondApplication = $this->makeJobApplication($jobPosting, $firstStage, 'second@example.com');

        $this->assertNotNull($firstApplication->position);
        $this->assertNotNull($secondApplication->position);
        $this->assertTrue((float) $secondApplication->position > (float) $firstApplication->position);
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
            'slug'                  => 'developer-'.str()->slug($suffix).'-'.str()->lower(str()->random(4)),
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
