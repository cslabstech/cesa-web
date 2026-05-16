<?php

namespace Cesa\Rekrutmen\Tests\Feature;

use Cesa\Rekrutmen\Enums\ActivityEntryResult;
use Cesa\Rekrutmen\Enums\JobApplicationGender;
use Cesa\Rekrutmen\Enums\JobApplicationMaritalStatus;
use Cesa\Rekrutmen\Enums\JobApplicationStatus;
use Cesa\Rekrutmen\Filament\Resources\JobApplicationResource\Pages\ViewJobApplication;
use Cesa\Rekrutmen\Filament\Resources\JobApplicationResource\RelationManagers\HistoriesRelationManager;
use Cesa\Rekrutmen\Models\JobApplication;
use Cesa\Rekrutmen\Models\JobApplicationHistory;
use Cesa\Rekrutmen\Models\JobPosting;
use Cesa\Rekrutmen\Models\RekrutmenPipeline;
use Cesa\Rekrutmen\Models\RekrutmenStage;
use Cesa\Rekrutmen\Tests\RekrutmenTestCase;
use Filament\Facades\Filament;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Webkul\Security\Enums\PermissionType;
use Webkul\Security\Models\User;

class JobApplicationHistoryCorrectionTest extends RekrutmenTestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');

        $this->user = User::factory()->create([
            'is_active'           => true,
            'resource_permission' => PermissionType::GLOBAL,
        ]);

        Permission::findOrCreate('view_any_rekrutmen_job::application', 'web');
        Permission::findOrCreate('view_rekrutmen_job::application', 'web');
        Permission::findOrCreate('update_rekrutmen_job::application', 'web');

        $this->user->givePermissionTo([
            'view_any_rekrutmen_job::application',
            'view_rekrutmen_job::application',
            'update_rekrutmen_job::application',
        ]);

        $this->actingAs($this->user);

        $this->registerJobApplicationRoutes();
    }

    public function test_job_application_view_hides_inline_record_activity_action(): void
    {
        $page = app(ViewJobApplication::class);
        $getHeaderActions = new \ReflectionMethod($page, 'getHeaderActions');
        $getHeaderActions->setAccessible(true);

        $actions = $getHeaderActions->invoke($page);

        $this->assertFalse(
            collect($actions)->contains(fn (mixed $action): bool => method_exists($action, 'getName')
                && $action->getName() === 'record_activity')
        );

        $this->assertTrue(
            collect($actions)->contains(fn (mixed $action): bool => method_exists($action, 'getName')
                && $action->getName() === 'pass_current_stage')
        );
    }

    public function test_job_application_history_activity_date_can_be_corrected_from_relation_manager(): void
    {
        [$jobPosting, $screeningStage, $hiredStage] = $this->createPipelineFixture();
        $candidate = $this->createJobApplication($jobPosting, $hiredStage);

        $history = $candidate->histories()->create([
            'from_stage_id' => $screeningStage->id,
            'to_stage_id'   => $hiredStage->id,
            'status'        => JobApplicationStatus::HIRED,
            'notes'         => 'Dicatat saat sistem mulai berjalan.',
            'performed_by'  => $this->user->id,
            'created_at'    => Carbon::parse('2026-04-27 09:00:00'),
            'updated_at'    => Carbon::parse('2026-04-27 09:00:00'),
        ]);
        $recordedAt = $history->created_at?->toDateString();

        Livewire::test(HistoriesRelationManager::class, [
            'ownerRecord' => $candidate,
            'pageClass'   => ViewJobApplication::class,
        ])
            ->callTableAction('edit', $history, [
                'activity_date' => '2026-04-02',
                'notes'         => 'Tanggal dikoreksi sesuai tanggal hired aktual.',
            ]);

        $history->refresh();

        $this->assertSame('2026-04-02', $history->activity_date?->toDateString());
        $this->assertSame('Tanggal dikoreksi sesuai tanggal hired aktual.', $history->notes);
        $this->assertSame($recordedAt, $history->created_at?->toDateString());
    }

    public function test_correcting_batch_history_date_keeps_group_activity_date_consistent(): void
    {
        [$jobPosting, $screeningStage] = $this->createPipelineFixture();
        $firstCandidate = $this->createJobApplication($jobPosting, $screeningStage, 'candidate-one@example.com', 'Candidate One');
        $secondCandidate = $this->createJobApplication($jobPosting, $screeningStage, 'candidate-two@example.com', 'Candidate Two');
        $groupId = (string) str()->uuid();

        $firstHistory = $this->createBatchHistory($firstCandidate, $screeningStage, $groupId, 'Catatan kandidat pertama.');
        $secondHistory = $this->createBatchHistory($secondCandidate, $screeningStage, $groupId, 'Catatan kandidat kedua.');

        Livewire::test(HistoriesRelationManager::class, [
            'ownerRecord' => $firstCandidate,
            'pageClass'   => ViewJobApplication::class,
        ])
            ->callTableAction('edit', $firstHistory, [
                'activity_date' => '2026-04-02',
                'notes'         => 'Catatan kandidat pertama dikoreksi.',
            ]);

        $firstHistory->refresh();
        $secondHistory->refresh();

        $this->assertSame('2026-04-02', $firstHistory->activity_date?->toDateString());
        $this->assertSame('2026-04-02', $secondHistory->activity_date?->toDateString());
        $this->assertSame(JobApplication::generateBatchActivityTitle($screeningStage->name, '2026-04-02'), $firstHistory->activity_title);
        $this->assertSame(JobApplication::generateBatchActivityTitle($screeningStage->name, '2026-04-02'), $secondHistory->activity_title);
        $this->assertSame('Catatan kandidat pertama dikoreksi.', $firstHistory->notes);
        $this->assertSame('Catatan kandidat kedua.', $secondHistory->notes);
    }

    /**
     * @return array{0: JobPosting, 1: RekrutmenStage, 2: RekrutmenStage}
     */
    private function createPipelineFixture(): array
    {
        $pipeline = RekrutmenPipeline::query()->create([
            'name' => 'History Correction Pipeline '.str()->lower(str()->random(5)),
        ]);

        $screeningStage = RekrutmenStage::query()->create([
            'rekrutmen_pipeline_id' => $pipeline->id,
            'name'                  => 'Screening',
            'order_column'          => 1,
        ]);

        $hiredStage = RekrutmenStage::query()->create([
            'rekrutmen_pipeline_id' => $pipeline->id,
            'name'                  => 'Hired',
            'order_column'          => 2,
        ]);

        $jobPosting = JobPosting::query()->create([
            'rekrutmen_pipeline_id' => $pipeline->id,
            'title'                 => 'Backend Engineer',
            'slug'                  => 'backend-engineer-history-correction-'.str()->lower(str()->random(5)),
            'description'           => 'Build APIs',
            'requirements'          => 'Laravel',
            'location'              => 'Jakarta',
            'is_published'          => true,
        ]);

        return [$jobPosting, $screeningStage, $hiredStage];
    }

    private function createJobApplication(
        JobPosting $jobPosting,
        RekrutmenStage $stage,
        string $email = 'candidate-history@example.com',
        string $fullName = 'Candidate History'
    ): JobApplication {
        $phoneSuffix = substr((string) abs(crc32($email)), 0, 10);

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
            'whatsapp_number'            => '08'.$phoneSuffix,
            'active_phone'               => '09'.$phoneSuffix,
            'emergency_contact_name'     => 'Bunga',
            'emergency_contact_relation' => 'Saudara',
            'emergency_contact_phone'    => '07'.$phoneSuffix,
            'status'                     => JobApplicationStatus::IN_PROGRESS,
        ]);
    }

    private function createBatchHistory(
        JobApplication $candidate,
        RekrutmenStage $stage,
        string $groupId,
        string $notes
    ): JobApplicationHistory {
        return $candidate->histories()->create([
            'from_stage_id'     => $stage->id,
            'to_stage_id'       => $stage->id,
            'activity_type'     => $stage->activityKey(),
            'activity_date'     => '2026-04-27',
            'result'            => ActivityEntryResult::PASSED,
            'activity_title'    => JobApplication::generateBatchActivityTitle($stage->name, '2026-04-27'),
            'activity_group_id' => $groupId,
            'status'            => JobApplicationStatus::IN_PROGRESS,
            'notes'             => $notes,
            'performed_by'      => $this->user->id,
            'created_at'        => Carbon::parse('2026-04-27 09:00:00'),
            'updated_at'        => Carbon::parse('2026-04-27 09:00:00'),
        ]);
    }

    private function registerJobApplicationRoutes(): void
    {
        if (! Route::has('filament.admin.resources.job-applications.index')) {
            Route::get('/testing/filament/admin/resources/job-applications', fn () => 'ok')
                ->name('filament.admin.resources.job-applications.index');
        }

        if (! Route::has('filament.admin.resources.job-applications.edit')) {
            Route::get('/testing/filament/admin/resources/job-applications/{record}/edit', fn () => 'ok')
                ->name('filament.admin.resources.job-applications.edit');
        }

        if (! Route::has('filament.admin.resources.job-applications.view')) {
            Route::get('/testing/filament/admin/resources/job-applications/{record}', fn () => 'ok')
                ->name('filament.admin.resources.job-applications.view');
        }
    }
}
