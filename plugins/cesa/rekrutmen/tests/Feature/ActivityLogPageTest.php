<?php

namespace Cesa\Rekrutmen\Tests\Feature;

use Cesa\Rekrutmen\Enums\JobApplicationGender;
use Cesa\Rekrutmen\Enums\JobApplicationMaritalStatus;
use Cesa\Rekrutmen\Enums\JobApplicationStatus;
use Cesa\Rekrutmen\Filament\Resources\ActivityLogResource\Pages\CreateActivityLog;
use Cesa\Rekrutmen\Filament\Resources\ActivityLogResource\Pages\ListActivityLogs;
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

class ActivityLogPageTest extends RekrutmenTestCase
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

        Permission::findOrCreate('view_any_cesa::rekrutmen::models::job::application::history', 'web');
        Permission::findOrCreate('create_cesa::rekrutmen::models::job::application::history', 'web');
        Permission::findOrCreate('delete_cesa::rekrutmen::models::job::application::history', 'web');

        $this->user->givePermissionTo([
            'view_any_cesa::rekrutmen::models::job::application::history',
            'create_cesa::rekrutmen::models::job::application::history',
            'delete_cesa::rekrutmen::models::job::application::history',
        ]);

        $this->registerActivityLogRoutes();
    }

    public function test_create_activity_page_records_batch_activity_with_minimal_input(): void
    {
        $pipeline = RekrutmenPipeline::query()->create([
            'name' => 'Activity Page Pipeline',
        ]);

        $firstStage = RekrutmenStage::query()->create([
            'rekrutmen_pipeline_id' => $pipeline->id,
            'name'                  => 'CV Screening',
            'order_column'          => 1,
        ]);

        $secondStage = RekrutmenStage::query()->create([
            'rekrutmen_pipeline_id' => $pipeline->id,
            'name'                  => 'Interview',
            'order_column'          => 2,
        ]);

        $jobPosting = JobPosting::query()->create([
            'rekrutmen_pipeline_id' => $pipeline->id,
            'title'                 => 'Backend Engineer',
            'slug'                  => 'backend-engineer-activity-page',
            'description'           => 'Build APIs',
            'requirements'          => 'Laravel',
            'location'              => 'Jakarta',
            'is_published'          => true,
        ]);

        $candidate = JobApplication::query()->create([
            'job_posting_id'             => $jobPosting->id,
            'current_stage_id'           => $firstStage->id,
            'full_name'                  => 'Candidate Activity',
            'email'                      => 'candidate-activity@example.com',
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

        Livewire::test(CreateActivityLog::class)
            ->set('data.job_posting_id', $jobPosting->id)
            ->set('data.stage_id', $firstStage->id)
            ->set('data.activity_date', '2026-04-05')
            ->set('data.candidates', [
                [
                    'job_application_id' => $candidate->id,
                    'result'             => 'passed',
                    'notes'              => 'Lanjut ke interview',
                ],
            ])
            ->call('create');

        $candidate->refresh();

        $this->assertSame($secondStage->id, $candidate->current_stage_id);
        $this->assertDatabaseCount('rekrutmen_job_application_histories', 2);
        $this->assertDatabaseHas('rekrutmen_job_application_histories', [
            'job_application_id' => $candidate->id,
            'from_stage_id'      => $firstStage->id,
            'to_stage_id'        => $secondStage->id,
            'activity_type'      => $firstStage->activityKey(),
            'activity_title'     => JobApplication::generateBatchActivityTitle($firstStage->name, '2026-04-05'),
            'result'             => 'passed',
        ]);
    }

    public function test_list_activity_page_renders_grouped_activity_without_view_route(): void
    {
        $pipeline = RekrutmenPipeline::query()->create([
            'name' => 'Activity List Pipeline',
        ]);

        $stage = RekrutmenStage::query()->create([
            'rekrutmen_pipeline_id' => $pipeline->id,
            'name'                  => 'Interview',
            'order_column'          => 1,
        ]);

        $jobPosting = JobPosting::query()->create([
            'rekrutmen_pipeline_id' => $pipeline->id,
            'title'                 => 'QA Engineer',
            'slug'                  => 'qa-engineer-activity-list',
            'description'           => 'Test systems',
            'requirements'          => 'Automation',
            'location'              => 'Jakarta',
            'is_published'          => true,
        ]);

        $candidate = JobApplication::query()->create([
            'job_posting_id'             => $jobPosting->id,
            'current_stage_id'           => $stage->id,
            'full_name'                  => 'Candidate List',
            'email'                      => 'candidate-list@example.com',
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

        $groupId = JobApplication::recordBatchActivity(
            $jobPosting->id,
            $stage->id,
            '2026-04-06',
            [
                ['job_application_id' => $candidate->id, 'result' => 'pending', 'notes' => 'Menunggu keputusan'],
            ],
            $this->user->id,
        );

        $activityTitle = JobApplicationHistory::query()
            ->where('activity_group_id', $groupId)
            ->value('activity_title');

        Livewire::test(ListActivityLogs::class)
            ->assertSee($activityTitle)
            ->assertSee('1 Orang');
    }

    public function test_list_activity_page_filters_and_deletes_grouped_activities(): void
    {
        $firstPipeline = RekrutmenPipeline::query()->create([
            'name' => 'Activity Filter Pipeline A',
        ]);

        $firstStage = RekrutmenStage::query()->create([
            'rekrutmen_pipeline_id' => $firstPipeline->id,
            'name'                  => 'Interview A',
            'order_column'          => 1,
        ]);

        $firstPosting = JobPosting::query()->create([
            'rekrutmen_pipeline_id' => $firstPipeline->id,
            'title'                 => 'Designer A',
            'slug'                  => 'designer-a-activity-filter',
            'description'           => 'Design systems',
            'requirements'          => 'Figma',
            'location'              => 'Jakarta',
            'is_published'          => true,
        ]);

        $secondPipeline = RekrutmenPipeline::query()->create([
            'name' => 'Activity Filter Pipeline B',
        ]);

        $secondStage = RekrutmenStage::query()->create([
            'rekrutmen_pipeline_id' => $secondPipeline->id,
            'name'                  => 'Interview B',
            'order_column'          => 1,
        ]);

        $secondPosting = JobPosting::query()->create([
            'rekrutmen_pipeline_id' => $secondPipeline->id,
            'title'                 => 'Developer B',
            'slug'                  => 'developer-b-activity-filter',
            'description'           => 'Build APIs',
            'requirements'          => 'Laravel',
            'location'              => 'Bandung',
            'is_published'          => true,
        ]);

        $firstCandidate = JobApplication::query()->create([
            'job_posting_id'             => $firstPosting->id,
            'current_stage_id'           => $firstStage->id,
            'full_name'                  => 'Candidate Filter A',
            'email'                      => 'candidate-filter-a@example.com',
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

        $secondCandidate = JobApplication::query()->create([
            'job_posting_id'             => $secondPosting->id,
            'current_stage_id'           => $secondStage->id,
            'full_name'                  => 'Candidate Filter B',
            'email'                      => 'candidate-filter-b@example.com',
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

        $firstGroupId = JobApplication::recordBatchActivity(
            $firstPosting->id,
            $firstStage->id,
            '2026-04-06',
            [
                ['job_application_id' => $firstCandidate->id, 'result' => 'pending', 'notes' => 'Screening A'],
            ],
            $this->user->id,
        );

        JobApplication::recordBatchActivity(
            $secondPosting->id,
            $secondStage->id,
            '2026-04-08',
            [
                ['job_application_id' => $secondCandidate->id, 'result' => 'pending', 'notes' => 'Interview B'],
            ],
            $this->user->id,
        );

        $firstTitle = JobApplicationHistory::query()
            ->where('activity_group_id', $firstGroupId)
            ->value('activity_title');

        Livewire::test(ListActivityLogs::class)
            ->set('jobPostingId', $firstPosting->id)
            ->set('stageId', $firstStage->id)
            ->set('dateFrom', '2026-04-01')
            ->set('dateTo', '2026-04-07')
            ->assertSee($firstTitle)
            ->call('deleteActivity', $firstGroupId)
            ->assertDontSee($firstTitle);

        $this->assertDatabaseMissing('rekrutmen_job_application_histories', [
            'activity_group_id' => $firstGroupId,
        ]);
    }

    private function registerActivityLogRoutes(): void
    {
        foreach ([
            'filament.admin.resources.activity-logs.index',
            'filament.admin.resources.activity-logs.create',
        ] as $routeName) {
            if (Route::has($routeName)) {
                continue;
            }

            Route::get('/testing/'.str_replace('.', '/', $routeName), fn () => 'ok')
                ->name($routeName);
        }
    }
}
