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
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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

    public function test_new_application_records_initial_history_when_created_from_admin_flow(): void
    {
        [$jobPosting, $firstStage] = $this->createPipelineFixture('Initial History');

        $application = $this->makeJobApplication($jobPosting, $firstStage, 'initial-history@example.com');

        $this->assertDatabaseHas('rekrutmen_job_application_histories', [
            'job_application_id' => $application->id,
            'from_stage_id'      => null,
            'to_stage_id'        => $firstStage->id,
            'status'             => JobApplicationStatus::IN_PROGRESS->value,
            'notes'              => __('rekrutmen::filament/resources/job-application.workflow_notes.submitted'),
        ]);
        $this->assertSame(1, $application->histories()->count());
    }

    public function test_terminal_status_application_does_not_record_initial_submission_history(): void
    {
        [$jobPosting, $firstStage] = $this->createPipelineFixture('Terminal Create');

        $application = JobApplication::query()->create([
            'job_posting_id'             => $jobPosting->id,
            'current_stage_id'           => $firstStage->id,
            'full_name'                  => 'Candidate Final',
            'email'                      => 'terminal@example.com',
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
            'status'                     => JobApplicationStatus::HIRED,
        ]);

        $this->assertSame(0, $application->histories()->count());
    }

    public function test_duplicate_email_cannot_be_created_for_the_same_job_posting(): void
    {
        [$jobPosting, $firstStage] = $this->createPipelineFixture('Duplicate Create');

        $this->makeJobApplication($jobPosting, $firstStage, 'duplicate@example.com');

        $this->expectException(ValidationException::class);

        $this->makeJobApplication($jobPosting, $firstStage, ' DUPLICATE@example.com ');
    }

    public function test_application_email_cannot_be_updated_to_duplicate_for_the_same_job_posting(): void
    {
        [$jobPosting, $firstStage] = $this->createPipelineFixture('Duplicate Update');

        $firstApplication = $this->makeJobApplication($jobPosting, $firstStage, 'first@example.com');
        $secondApplication = $this->makeJobApplication($jobPosting, $firstStage, 'second@example.com');

        $this->expectException(ValidationException::class);

        $secondApplication->update([
            'email' => ' FIRST@example.com ',
        ]);
    }

    public function test_restoring_duplicate_email_application_is_blocked(): void
    {
        [$jobPosting, $firstStage] = $this->createPipelineFixture('Duplicate Restore');

        $trashedApplication = $this->makeJobApplication($jobPosting, $firstStage, 'restore@example.com');
        $trashedApplication->delete();

        $replacementApplication = $this->makeJobApplication($jobPosting, $firstStage, 'restore@example.com');

        $this->expectException(ValidationException::class);

        $trashedApplication->restore();

        $replacementApplication->refresh();
    }

    public function test_soft_deleted_stage_is_not_counted_as_an_available_pipeline_stage(): void
    {
        [$jobPosting, $firstStage, $secondStage] = $this->createPipelineFixture('Soft Deleted Stage');

        $firstStage->delete();
        $secondStage->delete();

        $jobPosting->rekrutmenPipeline->refresh();

        $this->assertSame(0, $jobPosting->rekrutmenPipeline->activeStages()->count());
        $this->assertNull(JobApplication::resolveInitialStageIdForJobPostingId($jobPosting->id));
    }

    public function test_legacy_duplicate_record_can_be_updated_without_reclaiming_active_email(): void
    {
        [$jobPosting, $firstStage] = $this->createPipelineFixture('Legacy Duplicate');

        $this->makeJobApplication($jobPosting, $firstStage, 'legacy@example.com');

        $legacyDuplicateId = DB::table('rekrutmen_job_applications')->insertGetId([
            'job_posting_id'             => $jobPosting->id,
            'current_stage_id'           => $firstStage->id,
            'position'                   => null,
            'full_name'                  => 'Legacy Candidate',
            'gender'                     => JobApplicationGender::Male->value,
            'birth_date'                 => '1995-01-10',
            'marital_status'             => JobApplicationMaritalStatus::Single->value,
            'address_ktp'                => 'Alamat KTP',
            'address_domicile'           => 'Alamat Domisili',
            'whatsapp_number'            => '081234567890',
            'active_phone'               => '081234567891',
            'emergency_contact_name'     => 'Bunga',
            'emergency_contact_relation' => 'Saudara',
            'emergency_contact_phone'    => '081234567892',
            'email'                      => 'LEGACY@example.com',
            'active_email'               => null,
            'photo_path'                 => null,
            'resume_path'                => null,
            'status'                     => JobApplicationStatus::IN_PROGRESS->value,
            'created_at'                 => now(),
            'updated_at'                 => now(),
        ]);

        $legacyDuplicate = JobApplication::query()->findOrFail($legacyDuplicateId);

        $legacyDuplicate->update([
            'full_name' => 'Legacy Candidate Updated',
        ]);

        $legacyDuplicate->refresh();

        $this->assertSame('LEGACY CANDIDATE UPDATED', $legacyDuplicate->full_name);
        $this->assertNull(
            DB::table('rekrutmen_job_applications')
                ->where('id', $legacyDuplicateId)
                ->value('active_email')
        );
    }

    public function test_surviving_legacy_duplicate_reclaims_active_email_when_owner_is_deleted(): void
    {
        [$jobPosting, $firstStage] = $this->createPipelineFixture('Legacy Reclaim');

        $retainedApplication = $this->makeJobApplication($jobPosting, $firstStage, 'legacy-reclaim@example.com');

        $legacyDuplicateId = DB::table('rekrutmen_job_applications')->insertGetId([
            'job_posting_id'             => $jobPosting->id,
            'current_stage_id'           => $firstStage->id,
            'position'                   => null,
            'full_name'                  => 'Legacy Candidate',
            'gender'                     => JobApplicationGender::Male->value,
            'birth_date'                 => '1995-01-10',
            'marital_status'             => JobApplicationMaritalStatus::Single->value,
            'address_ktp'                => 'Alamat KTP',
            'address_domicile'           => 'Alamat Domisili',
            'whatsapp_number'            => '081234567890',
            'active_phone'               => '081234567891',
            'emergency_contact_name'     => 'Bunga',
            'emergency_contact_relation' => 'Saudara',
            'emergency_contact_phone'    => '081234567892',
            'email'                      => 'LEGACY-RECLAIM@example.com',
            'active_email'               => null,
            'photo_path'                 => null,
            'resume_path'                => null,
            'status'                     => JobApplicationStatus::IN_PROGRESS->value,
            'created_at'                 => now(),
            'updated_at'                 => now(),
        ]);

        $retainedApplication->delete();

        $this->assertSame(
            'legacy-reclaim@example.com',
            DB::table('rekrutmen_job_applications')
                ->where('id', $legacyDuplicateId)
                ->value('active_email')
        );

        $this->expectException(ValidationException::class);

        $this->makeJobApplication($jobPosting, $firstStage, 'legacy-reclaim@example.com');
    }

    public function test_surviving_legacy_duplicate_reclaims_active_email_when_owner_changes_email(): void
    {
        [$jobPosting, $firstStage] = $this->createPipelineFixture('Legacy Email Change');

        $retainedApplication = $this->makeJobApplication($jobPosting, $firstStage, 'legacy-email-change@example.com');

        $legacyDuplicateId = DB::table('rekrutmen_job_applications')->insertGetId([
            'job_posting_id'             => $jobPosting->id,
            'current_stage_id'           => $firstStage->id,
            'position'                   => null,
            'full_name'                  => 'Legacy Candidate',
            'gender'                     => JobApplicationGender::Male->value,
            'birth_date'                 => '1995-01-10',
            'marital_status'             => JobApplicationMaritalStatus::Single->value,
            'address_ktp'                => 'Alamat KTP',
            'address_domicile'           => 'Alamat Domisili',
            'whatsapp_number'            => '081234567890',
            'active_phone'               => '081234567891',
            'emergency_contact_name'     => 'Bunga',
            'emergency_contact_relation' => 'Saudara',
            'emergency_contact_phone'    => '081234567892',
            'email'                      => 'LEGACY-EMAIL-CHANGE@example.com',
            'active_email'               => null,
            'photo_path'                 => null,
            'resume_path'                => null,
            'status'                     => JobApplicationStatus::IN_PROGRESS->value,
            'created_at'                 => now(),
            'updated_at'                 => now(),
        ]);

        $retainedApplication->update([
            'email' => 'replacement@example.com',
        ]);

        $this->assertSame(
            'legacy-email-change@example.com',
            DB::table('rekrutmen_job_applications')
                ->where('id', $legacyDuplicateId)
                ->value('active_email')
        );

        $this->expectException(ValidationException::class);

        $this->makeJobApplication($jobPosting, $firstStage, 'legacy-email-change@example.com');
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
