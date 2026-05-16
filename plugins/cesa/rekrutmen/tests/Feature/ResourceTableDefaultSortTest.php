<?php

namespace Cesa\Rekrutmen\Tests\Feature;

use Cesa\Rekrutmen\Enums\JobApplicationGender;
use Cesa\Rekrutmen\Enums\JobApplicationMaritalStatus;
use Cesa\Rekrutmen\Enums\JobApplicationStatus;
use Cesa\Rekrutmen\Enums\RequestManPowerStatus;
use Cesa\Rekrutmen\Enums\StatusKebutuhan;
use Cesa\Rekrutmen\Filament\Resources\JobApplicationResource;
use Cesa\Rekrutmen\Filament\Resources\JobApplicationResource\Pages\ListJobApplications;
use Cesa\Rekrutmen\Filament\Resources\JobPostingResource;
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

    public function test_request_man_power_table_prioritizes_compact_columns(): void
    {
        $request = $this->createRequestManPower('compact-request@example.com', now(), 'Compact Requester');

        $component = Livewire::test(ListRequestManPowers::class)
            ->assertCanSeeTableRecords([$request])
            ->assertTableColumnVisible('posisi_dibutuhkan')
            ->assertTableColumnVisible('jumlah_karyawan_dibutuhkan')
            ->assertTableColumnVisible('fulfillment_status')
            ->assertTableColumnVisible('tanggal_pengajuan')
            ->assertTableColumnVisible('status')
            ->assertTableColumnHasDescription('fulfillment_status', '0/1 diterima | 0 proses | 0 kandidat', $request)
            ->assertTableColumnHasDescription('posisi_dibutuhkan', 'MPP #'.$request->id.' | Jakarta | '.StatusKebutuhan::NEW_HIRING->getLabel().' | Compact Requester', $request);

        $this->assertTrue($component->instance()->isTableColumnToggledHidden('jobPosting.title'));
        $this->assertTrue($component->instance()->isTableColumnToggledHidden('division.name'));
        $this->assertTrue($component->instance()->isTableColumnToggledHidden('status_kebutuhan'));
        $this->assertTrue($component->instance()->isTableColumnToggledHidden('id'));
        $this->assertTrue($component->instance()->isTableColumnToggledHidden('nama_pengaju'));
        $this->assertTrue($component->instance()->isTableColumnToggledHidden('nama_karyawan_replacement'));
        $this->assertTrue($component->instance()->isTableColumnToggledHidden('currentPendingApproval.approver_name'));
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

    public function test_job_posting_table_prioritizes_compact_columns(): void
    {
        $pipeline = $this->createPipeline('Compact Job Pipeline', now());
        $request = $this->createRequestManPower('compact-linked-request@example.com', now(), 'Compact Requester');
        $jobPosting = $this->createJobPosting($pipeline, 'Compact Frontliner', 'compact-frontliner', now());

        $request->forceFill([
            'job_posting_id' => $jobPosting->id,
        ])->saveQuietly();

        $component = Livewire::test(ListJobPostings::class)
            ->assertCanSeeTableRecords([$jobPosting])
            ->assertTableColumnVisible('title')
            ->assertTableColumnVisible('requested_headcount_sum')
            ->assertTableColumnVisible('applications_count')
            ->assertTableColumnVisible('is_published')
            ->assertTableColumnVisible('closing_date')
            ->assertTableColumnHasDescription('title', JobPostingResource::formatJobPostingContext($jobPosting->fresh(['requestManPowers', 'rekrutmenPipeline'])), $jobPosting);

        $component->assertTableColumnHidden('rekrutmenPipeline.name');
        $this->assertTrue($component->instance()->isTableColumnToggledHidden('request_man_powers_summary'));
        $this->assertTrue($component->instance()->isTableColumnToggledHidden('id'));
        $this->assertTrue($component->instance()->isTableColumnToggledHidden('thumbnail_path'));
        $this->assertTrue($component->instance()->isTableColumnToggledHidden('location'));
        $this->assertTrue($component->instance()->isTableColumnToggledHidden('request_man_powers_count'));
    }

    public function test_job_posting_table_filters_by_linked_manpower_request(): void
    {
        $pipeline = $this->createPipeline('Filtered Job Pipeline', now());
        $linkedRequest = $this->createRequestManPower('linked-request@example.com', now(), 'Linked Request');
        $otherRequest = $this->createRequestManPower('other-request@example.com', now(), 'Other Request');

        $linkedPosting = $this->createJobPosting($pipeline, 'Linked Frontliner', 'linked-frontliner', now());
        $otherPosting = $this->createJobPosting($pipeline, 'Other Frontliner', 'other-frontliner', now());

        $linkedRequest->forceFill([
            'job_posting_id' => $linkedPosting->id,
        ])->saveQuietly();
        $otherRequest->forceFill([
            'job_posting_id' => $otherPosting->id,
        ])->saveQuietly();

        Livewire::test(ListJobPostings::class)
            ->filterTable('request_man_power_link_id', $linkedRequest->id)
            ->assertCanSeeTableRecords([$linkedPosting])
            ->assertCanNotSeeTableRecords([$otherPosting]);
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

    public function test_job_application_table_exposes_one_click_candidate_contact_copy_action(): void
    {
        app()->setLocale('id');

        $pipeline = $this->createPipeline('Copy Candidate Pipeline', now());
        $stage = RekrutmenStage::query()->create([
            'rekrutmen_pipeline_id' => $pipeline->id,
            'name'                  => 'CV Screening',
            'order_column'          => 1,
        ]);
        $jobPosting = $this->createJobPosting($pipeline, 'Store Crew Bandung', 'store-crew-bandung', now());
        $application = $this->createJobApplication($jobPosting, $stage, 'copy-candidate@example.com', 'Budi Santoso', now());

        $application->forceFill([
            'whatsapp_number' => '6281573789248',
            'active_whatsapp' => '6281573789248',
        ])->saveQuietly();

        $application = $application->fresh('jobPosting');

        $this->assertSame(
            'BUDI SANTOSO Store Crew Bandung 6281573789248',
            JobApplicationResource::formatCandidateContactClipboardText($application),
        );

        Livewire::test(ListJobApplications::class)
            ->assertCanSeeTableRecords([$application])
            ->assertTableActionExists('copy_candidate_contact', record: $application)
            ->assertTableActionDoesNotExist('mark_rejected', record: $application)
            ->assertTableActionHasLabel('copy_candidate_contact', 'Copy', record: $application)
            ->assertTableActionHasIcon('copy_candidate_contact', 'heroicon-o-clipboard-document', record: $application);
    }

    private function createRequestManPower(string $email, \DateTimeInterface $createdAt, string $name): RequestManPower
    {
        $company = Company::query()->create([
            'name' => 'PT CESA',
        ]);

        $request = RequestManPower::query()->create([
            'company_id'                 => $company->id,
            'email_address'              => $email,
            'nama_pengaju'               => $name,
            'posisi_pengaju'             => 'HR Manager',
            'tanggal_pengajuan'          => now()->toDateString(),
            'posisi_dibutuhkan'          => 'Software Engineer',
            'lokasi_penempatan'          => 'Jakarta',
            'status_kebutuhan'           => StatusKebutuhan::NEW_HIRING,
            'divisi'                     => 'IT',
            'level_pekerjaan'            => 'Staff',
            'jumlah_karyawan_dibutuhkan' => 1,
            'estimasi_tanggal_join'      => now()->addWeek()->toDateString(),
            'requirements_kualifikasi'   => 'Laravel',
            'job_description'            => 'Build internal apps',
            'keterangan'                 => 'default sort test',
            'status'                     => RequestManPowerStatus::PENDING,
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
            'whatsapp_number'            => $this->uniquePhoneNumberFor($email, '081'),
            'active_phone'               => $this->uniquePhoneNumberFor($email, '082'),
            'emergency_contact_name'     => 'Bunga',
            'emergency_contact_relation' => 'Saudara',
            'emergency_contact_phone'    => $this->uniquePhoneNumberFor($email, '083'),
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
            'filament.admin.resources.request-man-powers.view',
            'filament.admin.resources.rekrutmen-pipelines.index',
            'filament.admin.rekrutmen.configurations',
            'filament.admin.rekrutmen.configurations.resources.rekrutmen-pipelines.index',
            'filament.admin.resources.job-postings.index',
            'filament.admin.resources.job-postings.edit',
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

    private function uniquePhoneNumberFor(string $value, string $prefix): string
    {
        $suffix = str_pad((string) (abs(crc32($value)) % 100000000), 8, '0', STR_PAD_LEFT);

        return $prefix.$suffix;
    }
}
