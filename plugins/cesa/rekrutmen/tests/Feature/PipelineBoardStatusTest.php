<?php

namespace Cesa\Rekrutmen\Tests\Feature;

use Cesa\Rekrutmen\Enums\JobApplicationGender;
use Cesa\Rekrutmen\Enums\JobApplicationMaritalStatus;
use Cesa\Rekrutmen\Enums\JobApplicationStatus;
use Cesa\Rekrutmen\Filament\Resources\JobApplicationResource\Pages\PipelineBoard;
use Cesa\Rekrutmen\Models\JobApplication;
use Cesa\Rekrutmen\Models\JobApplicationHistory;
use Cesa\Rekrutmen\Models\JobPosting;
use Cesa\Rekrutmen\Models\RekrutmenPipeline;
use Cesa\Rekrutmen\Models\RekrutmenStage;
use Cesa\Rekrutmen\Tests\RekrutmenTestCase;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Webkul\Security\Models\User;

class PipelineBoardStatusTest extends RekrutmenTestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');

        $this->user = User::factory()->create([
            'is_active' => true,
        ]);

        $this->actingAs($this->user);

        Permission::findOrCreate('view_any_rekrutmen_job::application', 'web');

        $this->user->givePermissionTo('view_any_rekrutmen_job::application');

        $this->registerBoardRoutes();
    }

    public function test_pipeline_board_marks_rejected_candidates_in_their_stage(): void
    {
        $pipeline = $this->createPipeline('Pipeline Board Status');
        $stage = RekrutmenStage::query()->create([
            'rekrutmen_pipeline_id' => $pipeline->id,
            'name'                  => 'Interview HR',
            'order_column'          => 1,
        ]);

        $jobPosting = $this->createJobPosting($pipeline, 'UI Engineer', 'ui-engineer-pipeline-board');

        $this->createJobApplication($jobPosting, $stage, 'active-candidate@example.com', 'Candidate Active');

        $rejectedCandidate = $this->createJobApplication(
            $jobPosting,
            $stage,
            'rejected-candidate@example.com',
            'Candidate Rejected',
        );

        $rejectedCandidate->markAsRejected('Belum sesuai kebutuhan', $this->user->id);

        Livewire::test(PipelineBoard::class, ['activeJobPostingId' => $jobPosting->id])
            ->assertSee('CANDIDATE REJECTED')
            ->assertSee(__('rekrutmen::enums/job-application-status.rejected'))
            ->assertSee(__('rekrutmen::filament/resources/job-application.board.card.status_context.rejected', [
                'stage' => $stage->name,
            ]));
    }

    public function test_pipeline_board_can_record_activity_inline_and_move_candidate(): void
    {
        $pipeline = $this->createPipeline('Pipeline Board Activity');
        $firstStage = RekrutmenStage::query()->create([
            'rekrutmen_pipeline_id' => $pipeline->id,
            'name'                  => 'Screening CV',
            'order_column'          => 1,
        ]);
        $secondStage = RekrutmenStage::query()->create([
            'rekrutmen_pipeline_id' => $pipeline->id,
            'name'                  => 'Interview User',
            'order_column'          => 2,
        ]);

        $jobPosting = $this->createJobPosting($pipeline, 'Backend Engineer', 'backend-engineer-pipeline-activity');
        $candidate = $this->createJobApplication(
            $jobPosting,
            $firstStage,
            'board-activity@example.com',
            'Candidate Board Activity',
        );

        Livewire::test(PipelineBoard::class, ['activeJobPostingId' => $jobPosting->id])
            ->call('mountAction', 'record_activity', [], ['recordKey' => $candidate->id])
            ->set('mountedActions.0.data.activity_date', '2026-04-15')
            ->set('mountedActions.0.data.result', 'passed')
            ->set('mountedActions.0.data.notes', 'Lolos screening dan lanjut ke user interview.')
            ->call('callMountedAction');

        $candidate->refresh();

        $this->assertSame($secondStage->id, $candidate->current_stage_id);

        $batchHistory = JobApplicationHistory::query()
            ->where('job_application_id', $candidate->id)
            ->whereNotNull('activity_group_id')
            ->first();

        $this->assertNotNull($batchHistory);
        $this->assertSame($firstStage->activityKey(), $batchHistory->activity_type);
        $this->assertSame('passed', $batchHistory->result?->value);
    }

    public function test_pipeline_board_blocks_cross_stage_drag_without_activity(): void
    {
        $pipeline = $this->createPipeline('Pipeline Board Drag Guard');
        $firstStage = RekrutmenStage::query()->create([
            'rekrutmen_pipeline_id' => $pipeline->id,
            'name'                  => 'Screening CV',
            'order_column'          => 1,
        ]);
        $secondStage = RekrutmenStage::query()->create([
            'rekrutmen_pipeline_id' => $pipeline->id,
            'name'                  => 'Interview User',
            'order_column'          => 2,
        ]);
        $thirdStage = RekrutmenStage::query()->create([
            'rekrutmen_pipeline_id' => $pipeline->id,
            'name'                  => 'Offering',
            'order_column'          => 3,
        ]);

        $jobPosting = $this->createJobPosting($pipeline, 'Data Analyst', 'data-analyst-pipeline-drag');
        $candidate = $this->createJobApplication(
            $jobPosting,
            $firstStage,
            'board-drag@example.com',
            'Candidate Board Drag',
        );

        Livewire::test(PipelineBoard::class, ['activeJobPostingId' => $jobPosting->id])
            ->call('moveCard', (string) $candidate->id, (string) $thirdStage->id, null, null);

        $candidate->refresh();

        $this->assertSame($firstStage->id, $candidate->current_stage_id);
        $this->assertSame(1, JobApplicationHistory::query()->where('job_application_id', $candidate->id)->count());
    }

    public function test_pipeline_board_drag_to_next_stage_records_passed_activity(): void
    {
        $pipeline = $this->createPipeline('Pipeline Board Step Drag');
        $firstStage = RekrutmenStage::query()->create([
            'rekrutmen_pipeline_id' => $pipeline->id,
            'name'                  => 'Screening CV',
            'order_column'          => 1,
        ]);
        $secondStage = RekrutmenStage::query()->create([
            'rekrutmen_pipeline_id' => $pipeline->id,
            'name'                  => 'Interview User',
            'order_column'          => 2,
        ]);

        $jobPosting = $this->createJobPosting($pipeline, 'QA Engineer', 'qa-engineer-pipeline-drag');
        $candidate = $this->createJobApplication(
            $jobPosting,
            $firstStage,
            'board-next-stage@example.com',
            'Candidate Board Next Stage',
        );

        Livewire::test(PipelineBoard::class, ['activeJobPostingId' => $jobPosting->id])
            ->call('moveCard', (string) $candidate->id, (string) $secondStage->id, null, null);

        $candidate->refresh();

        $this->assertSame($secondStage->id, $candidate->current_stage_id);

        $batchHistory = JobApplicationHistory::query()
            ->where('job_application_id', $candidate->id)
            ->whereNotNull('activity_group_id')
            ->latest('id')
            ->first();

        $this->assertNotNull($batchHistory);
        $this->assertSame($firstStage->activityKey(), $batchHistory->activity_type);
        $this->assertSame('passed', $batchHistory->result?->value);
    }

    public function test_pipeline_board_dragging_to_hired_stage_does_not_auto_accept_candidate(): void
    {
        $pipeline = $this->createPipeline('Pipeline Board Hired Stage Drag');
        $firstStage = RekrutmenStage::query()->create([
            'rekrutmen_pipeline_id' => $pipeline->id,
            'name'                  => 'Interview User',
            'order_column'          => 1,
        ]);
        $hiredStage = RekrutmenStage::query()->create([
            'rekrutmen_pipeline_id' => $pipeline->id,
            'name'                  => 'Hired',
            'order_column'          => 2,
        ]);

        $jobPosting = $this->createJobPosting($pipeline, 'Product Manager', 'product-manager-hired-stage-drag');
        $candidate = $this->createJobApplication(
            $jobPosting,
            $firstStage,
            'board-hired-stage@example.com',
            'Candidate Hired Stage',
        );

        Livewire::test(PipelineBoard::class, ['activeJobPostingId' => $jobPosting->id])
            ->call('moveCard', (string) $candidate->id, (string) $hiredStage->id, null, null);

        $candidate->refresh();

        $this->assertSame($hiredStage->id, $candidate->current_stage_id);
        $this->assertSame(JobApplicationStatus::IN_PROGRESS, $candidate->status);
    }

    public function test_stage_activity_key_is_derived_from_stage_name(): void
    {
        $screeningStage = RekrutmenStage::query()->create([
            'rekrutmen_pipeline_id' => $this->createPipeline('Activity Key Pipeline')->id,
            'name'                  => 'Screening CV',
            'order_column'          => 1,
        ]);

        $this->assertSame('screening_cv', $screeningStage->activityKey());
        $this->assertSame('Screening CV', $screeningStage->activityLabel());
    }

    private function createPipeline(string $name): RekrutmenPipeline
    {
        return RekrutmenPipeline::query()->create([
            'name'        => $name,
            'description' => 'pipeline board status test',
        ]);
    }

    private function createJobPosting(RekrutmenPipeline $pipeline, string $title, string $slug): JobPosting
    {
        return JobPosting::query()->create([
            'rekrutmen_pipeline_id' => $pipeline->id,
            'title'                 => $title,
            'slug'                  => $slug,
            'description'           => 'Build APIs',
            'requirements'          => 'Laravel',
            'location'              => 'Jakarta',
            'is_published'          => true,
        ]);
    }

    private function createJobApplication(
        JobPosting $jobPosting,
        RekrutmenStage $stage,
        string $email,
        string $fullName,
    ): JobApplication {
        $defaultWhatsappNumber = '08'.str_pad((string) (abs(crc32($email)) % 1000000000), 9, '0', STR_PAD_LEFT);

        return JobApplication::query()->create([
            'job_posting_id'             => $jobPosting->id,
            'current_stage_id'           => $stage->id,
            'full_name'                  => $fullName,
            'email'                      => $email,
            'gender'                     => JobApplicationGender::Male,
            'birth_date'                 => '1995-01-10',
            'marital_status'             => JobApplicationMaritalStatus::Single,
            'address_ktp'                => 'Alamat KTP',
            'address_domicile'           => 'Alamat Domisili',
            'whatsapp_number'            => $defaultWhatsappNumber,
            'active_phone'               => '081234567891',
            'emergency_contact_name'     => 'Bunga',
            'emergency_contact_relation' => 'Saudara',
            'emergency_contact_phone'    => '081234567892',
            'source'                     => 'jobstreet',
            'status'                     => JobApplicationStatus::IN_PROGRESS,
        ]);
    }

    private function registerBoardRoutes(): void
    {
        foreach ([
            'filament.admin.resources.job-applications.index',
            'filament.admin.resources.job-applications.board',
        ] as $routeName) {
            if (Route::has($routeName)) {
                continue;
            }

            Route::get('/testing/'.str_replace('.', '/', $routeName), fn () => 'ok')
                ->name($routeName);
        }

        if (! Route::has('filament.admin.resources.job-applications.edit')) {
            Route::get('/testing/filament/admin/resources/job-applications/{record}/edit', fn () => 'ok')
                ->name('filament.admin.resources.job-applications.edit');
        }
    }
}
