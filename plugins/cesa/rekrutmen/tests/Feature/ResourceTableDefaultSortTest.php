<?php

namespace Cesa\Rekrutmen\Tests\Feature;

use Cesa\Rekrutmen\Enums\JobApplicationGender;
use Cesa\Rekrutmen\Enums\JobApplicationMaritalStatus;
use Cesa\Rekrutmen\Enums\JobApplicationStatus;
use Cesa\Rekrutmen\Enums\RequestManPowerStatus;
use Cesa\Rekrutmen\Enums\StatusKebutuhan;
use Cesa\Rekrutmen\Filament\Resources\JobApplicationResource\Pages\ListJobApplications;
use Cesa\Rekrutmen\Filament\Resources\JobPostingResource\Pages\ListJobPostings;
use Cesa\Rekrutmen\Filament\Resources\RekrutmenPipelineResource\Pages\ListRekrutmenPipelines;
use Cesa\Rekrutmen\Filament\Resources\RequestManPowerResource\Pages\ListRequestManPowers;
use Cesa\Rekrutmen\Models\JobApplication;
use Cesa\Rekrutmen\Models\JobPosting;
use Cesa\Rekrutmen\Models\RekrutmenPipeline;
use Cesa\Rekrutmen\Models\RekrutmenStage;
use Cesa\Rekrutmen\Models\RequestManPower;
use Cesa\Rekrutmen\Tests\RekrutmenTestCase;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

class ResourceTableDefaultSortTest extends RekrutmenTestCase
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

        foreach ([
            'view_any_rekrutmen_request::man::power',
            'view_any_rekrutmen_rekrutmen::pipeline',
            'view_any_rekrutmen_job::posting',
            'view_any_rekrutmen_job::application',
        ] as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        $this->user->givePermissionTo([
            'view_any_rekrutmen_request::man::power',
            'view_any_rekrutmen_rekrutmen::pipeline',
            'view_any_rekrutmen_job::posting',
            'view_any_rekrutmen_job::application',
        ]);

        $this->registerListPageRoutes();
    }

    public function test_request_man_power_table_defaults_to_latest_records_first(): void
    {
        $old = $this->createRequestManPower('old-request@example.com', now()->subWeek(), 'Request Lama');
        $recent = $this->createRequestManPower('recent-request@example.com', now(), 'Request Baru');

        Livewire::test(ListRequestManPowers::class)
            ->assertCanSeeTableRecords([$recent, $old], inOrder: true);
    }

    public function test_rekrutmen_pipeline_table_defaults_to_latest_records_first(): void
    {
        $old = $this->createPipeline('Pipeline Lama', now()->subWeek());
        $recent = $this->createPipeline('Pipeline Baru', now());

        Livewire::test(ListRekrutmenPipelines::class)
            ->assertCanSeeTableRecords([$recent, $old], inOrder: true);
    }

    public function test_job_posting_table_defaults_to_latest_records_first(): void
    {
        $oldPipeline = $this->createPipeline('Job Pipeline Lama', now()->subWeeks(2));
        $recentPipeline = $this->createPipeline('Job Pipeline Baru', now()->subWeek());

        $old = $this->createJobPosting($oldPipeline, 'Backend Developer Lama', 'backend-developer-lama', now()->subWeek());
        $recent = $this->createJobPosting($recentPipeline, 'Backend Developer Baru', 'backend-developer-baru', now());

        Livewire::test(ListJobPostings::class)
            ->assertCanSeeTableRecords([$recent, $old], inOrder: true);
    }

    public function test_job_application_table_defaults_to_latest_records_first(): void
    {
        $pipeline = $this->createPipeline('Application Pipeline', now()->subMonth());
        $stage = RekrutmenStage::query()->create([
            'rekrutmen_pipeline_id' => $pipeline->id,
            'name'                  => 'CV Screening',
            'order_column'          => 1,
        ]);

        $jobPosting = $this->createJobPosting($pipeline, 'QA Engineer', 'qa-engineer-default-sort', now()->subMonth());

        $old = $this->createJobApplication($jobPosting, $stage, 'old-candidate@example.com', 'Candidate Lama', now()->subWeek());
        $recent = $this->createJobApplication($jobPosting, $stage, 'recent-candidate@example.com', 'Candidate Baru', now());

        Livewire::test(ListJobApplications::class)
            ->assertCanSeeTableRecords([$recent, $old], inOrder: true);
    }

    private function createRequestManPower(string $email, \DateTimeInterface $createdAt, string $name): RequestManPower
    {
        $company = Company::query()->create([
            'name' => 'PT CESA',
        ]);

        $request = RequestManPower::query()->create([
            'company_id'                   => $company->id,
            'email_address'                => $email,
            'nama_pengaju'                 => $name,
            'posisi_pengaju'               => 'HR Manager',
            'tanggal_pengajuan'            => now()->toDateString(),
            'posisi_dibutuhkan'            => 'Software Engineer',
            'lokasi_penempatan'            => 'Jakarta',
            'status_kebutuhan'             => StatusKebutuhan::NEW_HIRING,
            'divisi'                       => 'IT',
            'level_pekerjaan'              => 'Staff',
            'jumlah_karyawan_dibutuhkan'   => 1,
            'estimasi_tanggal_join'        => now()->addWeek()->toDateString(),
            'requirements_kualifikasi'     => 'Laravel',
            'job_description'              => 'Build internal apps',
            'keterangan'                   => 'default sort test',
            'status'                       => RequestManPowerStatus::PENDING,
        ]);

        $request->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->saveQuietly();

        return $request->fresh();
    }

    private function createPipeline(string $name, \DateTimeInterface $createdAt): RekrutmenPipeline
    {
        $pipeline = RekrutmenPipeline::query()->create([
            'name'        => $name,
            'description' => 'default sort test',
        ]);

        $pipeline->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->saveQuietly();

        return $pipeline->fresh();
    }

    private function createJobPosting(RekrutmenPipeline $pipeline, string $title, string $slug, \DateTimeInterface $createdAt): JobPosting
    {
        $jobPosting = JobPosting::query()->create([
            'rekrutmen_pipeline_id' => $pipeline->id,
            'title'                 => $title,
            'slug'                  => $slug,
            'description'           => 'Build APIs',
            'requirements'          => 'Laravel',
            'location'              => 'Jakarta',
            'is_published'          => true,
        ]);

        $jobPosting->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->saveQuietly();

        return $jobPosting->fresh();
    }

    private function createJobApplication(
        JobPosting $jobPosting,
        RekrutmenStage $stage,
        string $email,
        string $fullName,
        \DateTimeInterface $createdAt,
    ): JobApplication {
        $application = JobApplication::query()->create([
            'job_posting_id'             => $jobPosting->id,
            'current_stage_id'           => $stage->id,
            'full_name'                  => $fullName,
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

        $application->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->saveQuietly();

        return $application->fresh();
    }

    private function registerListPageRoutes(): void
    {
        foreach ([
            'filament.admin.resources.request-man-powers.index',
            'filament.admin.resources.rekrutmen-pipelines.index',
            'filament.admin.resources.job-postings.index',
            'filament.admin.resources.job-applications.index',
            'filament.admin.resources.job-applications.board',
        ] as $routeName) {
            if (Route::has($routeName)) {
                continue;
            }

            Route::get('/testing/'.str_replace('.', '/', $routeName), fn () => 'ok')
                ->name($routeName);
        }

    }
}
