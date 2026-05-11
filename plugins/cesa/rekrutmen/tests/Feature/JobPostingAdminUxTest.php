<?php

namespace Cesa\Rekrutmen\Tests\Feature;

use Cesa\Rekrutmen\Enums\JobApplicationGender;
use Cesa\Rekrutmen\Enums\JobApplicationMaritalStatus;
use Cesa\Rekrutmen\Enums\JobApplicationStatus;
use Cesa\Rekrutmen\Enums\RequestManPowerStatus;
use Cesa\Rekrutmen\Enums\StatusKebutuhan;
use Cesa\Rekrutmen\Filament\Resources\JobPostingResource;
use Cesa\Rekrutmen\Filament\Resources\JobPostingResource\Pages\EditJobPosting;
use Cesa\Rekrutmen\Filament\Resources\JobPostingResource\RelationManagers\RequestManPowersRelationManager;
use Cesa\Rekrutmen\Models\JobApplication;
use Cesa\Rekrutmen\Models\JobPosting;
use Cesa\Rekrutmen\Models\RekrutmenPipeline;
use Cesa\Rekrutmen\Models\RekrutmenStage;
use Cesa\Rekrutmen\Models\RequestManPower;
use Cesa\Rekrutmen\Tests\RekrutmenTestCase;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

class JobPostingAdminUxTest extends RekrutmenTestCase
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
            'view_any_rekrutmen_job::posting',
            'view_rekrutmen_job::posting',
            'update_rekrutmen_job::posting',
        ] as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        $this->user->givePermissionTo([
            'view_any_rekrutmen_job::posting',
            'view_rekrutmen_job::posting',
            'update_rekrutmen_job::posting',
        ]);

        $this->registerJobPostingPageRoutes();
    }

    public function test_job_posting_autofill_data_can_be_derived_from_manpower_request(): void
    {
        $pipeline = RekrutmenPipeline::query()->create([
            'name' => 'Preferred Pipeline',
        ]);

        $company = Company::query()->create([
            'name' => 'PT CESA',
        ]);

        $requestManPower = RequestManPower::query()->create([
            'company_id'                 => $company->id,
            'email_address'              => 'autofill@example.com',
            'nama_pengaju'               => 'Autofill Requester',
            'posisi_pengaju'             => 'HR Manager',
            'tanggal_pengajuan'          => now()->toDateString(),
            'posisi_dibutuhkan'          => 'Product Designer',
            'lokasi_penempatan'          => 'Jakarta',
            'status_kebutuhan'           => StatusKebutuhan::NEW_HIRING,
            'divisi'                     => 'Product',
            'level_pekerjaan'            => 'Staff',
            'jumlah_karyawan_dibutuhkan' => 2,
            'estimasi_tanggal_join'      => '2026-06-15',
            'requirements_kualifikasi'   => 'Figma and portfolio',
            'job_description'            => 'Design product experiences',
            'status'                     => RequestManPowerStatus::PENDING,
        ]);

        $requestManPower->createJobPostingIfMissing()->update([
            'rekrutmen_pipeline_id' => $pipeline->id,
        ]);

        $autofillData = JobPostingResource::resolveAutofillDataFromRequestManPower($requestManPower->fresh(['jobPosting']));

        $this->assertSame('Product Designer Jakarta', $autofillData['title']);
        $this->assertSame('product-designer-jakarta', $autofillData['slug']);
        $this->assertSame('Jakarta', $autofillData['location']);
        $this->assertSame('Design product experiences', $autofillData['description']);
        $this->assertSame('Figma and portfolio', $autofillData['requirements']);
        $this->assertSame('2026-06-15', $autofillData['closing_date']);
        $this->assertSame($pipeline->id, $autofillData['rekrutmen_pipeline_id']);

        $optionLabel = JobPostingResource::formatRequestManPowerOptionLabel($requestManPower->fresh('jobPosting'));
        $tableSummary = JobPostingResource::formatRequestManPowerTableSummary($requestManPower->fresh());
        $postingContext = JobPostingResource::formatJobPostingContext($requestManPower->fresh('jobPosting')->jobPosting);

        $this->assertStringContainsString('MPP #'.$requestManPower->id, $optionLabel);
        $this->assertStringContainsString('Product Designer', $optionLabel);
        $this->assertStringContainsString('Jakarta', $optionLabel);
        $this->assertStringContainsString('2 orang', $optionLabel);
        $this->assertStringContainsString('Lowongan #'.$requestManPower->jobPosting->id, $optionLabel);
        $this->assertStringContainsString('MPP #'.$requestManPower->id, $tableSummary);
        $this->assertStringContainsString('Product Designer', $tableSummary);
        $this->assertStringContainsString('Jakarta | 2 orang', $tableSummary);
        $this->assertStringNotContainsString('Lowongan #'.$requestManPower->jobPosting->id, $tableSummary);
        $this->assertStringContainsString('Lowongan #'.$requestManPower->jobPosting->id, $postingContext);
        $this->assertStringContainsString('Jakarta', $postingContext);
        $this->assertStringContainsString('1 MPP', $postingContext);
        $this->assertStringContainsString('Total kebutuhan 2 orang', $postingContext);
    }

    public function test_job_posting_edit_context_exposes_all_linked_manpower_requests(): void
    {
        $pipeline = RekrutmenPipeline::query()->create([
            'name' => 'Shared Pipeline',
        ]);

        $company = Company::query()->create([
            'name' => 'PT Shared MPP',
        ]);

        $firstRequest = RequestManPower::query()->create([
            'company_id'                 => $company->id,
            'email_address'              => 'first-linked@example.com',
            'nama_pengaju'               => 'First Requester',
            'posisi_pengaju'             => 'HR Manager',
            'tanggal_pengajuan'          => now()->toDateString(),
            'posisi_dibutuhkan'          => 'Sales Advisor',
            'lokasi_penempatan'          => 'Cirebon',
            'status_kebutuhan'           => StatusKebutuhan::NEW_HIRING,
            'divisi'                     => 'Sales',
            'level_pekerjaan'            => 'Staff',
            'jumlah_karyawan_dibutuhkan' => 2,
            'estimasi_tanggal_join'      => now()->addWeek()->toDateString(),
            'requirements_kualifikasi'   => 'Sales experience',
            'job_description'            => 'Serve customers',
            'status'                     => RequestManPowerStatus::APPROVED,
        ]);

        $jobPosting = JobPosting::query()->create([
            'request_man_power_id'  => $firstRequest->id,
            'rekrutmen_pipeline_id' => $pipeline->id,
            'title'                 => 'Sales Advisor Cirebon',
            'slug'                  => 'sales-advisor-cirebon',
            'description'           => 'Serve customers',
            'requirements'          => 'Sales experience',
            'location'              => 'Cirebon',
            'is_published'          => true,
            'closing_date'          => now()->addMonth()->toDateString(),
        ]);

        $firstRequest->forceFill([
            'job_posting_id' => $jobPosting->id,
        ])->saveQuietly();

        $secondRequest = RequestManPower::query()->create([
            'company_id'                 => $company->id,
            'email_address'              => 'second-linked@example.com',
            'nama_pengaju'               => 'Second Requester',
            'posisi_pengaju'             => 'HR Manager',
            'tanggal_pengajuan'          => now()->toDateString(),
            'posisi_dibutuhkan'          => 'Sales Advisor',
            'lokasi_penempatan'          => 'Cirebon',
            'status_kebutuhan'           => StatusKebutuhan::NEW_HIRING,
            'divisi'                     => 'Sales',
            'level_pekerjaan'            => 'Staff',
            'jumlah_karyawan_dibutuhkan' => 3,
            'estimasi_tanggal_join'      => now()->addWeeks(2)->toDateString(),
            'requirements_kualifikasi'   => 'Sales experience',
            'job_description'            => 'Serve customers',
            'status'                     => RequestManPowerStatus::HOLD,
            'job_posting_id'             => $jobPosting->id,
        ]);

        $overview = JobPostingResource::formatLinkedRequestManPowersOverview($jobPosting->fresh());

        $this->assertContains(RequestManPowersRelationManager::class, JobPostingResource::getRelations());
        $this->assertStringContainsString('2 MPP', $overview);
        $this->assertStringContainsString('Total kebutuhan 5 orang', $overview);
        $this->assertStringContainsString('MPP #'.$firstRequest->id, $overview);
        $this->assertStringContainsString('MPP #'.$secondRequest->id, $overview);
    }

    public function test_job_posting_edit_selection_can_add_replace_and_detach_linked_manpower_requests(): void
    {
        $pipeline = RekrutmenPipeline::query()->create([
            'name' => 'Editable MPP Pipeline',
        ]);

        $company = Company::query()->create([
            'name' => 'PT Editable MPP',
        ]);

        $firstRequest = $this->createRequestManPower($company, [
            'email_address'              => 'first-editable@example.com',
            'jumlah_karyawan_dibutuhkan' => 2,
        ]);
        $secondRequest = $this->createRequestManPower($company, [
            'email_address'              => 'second-editable@example.com',
            'jumlah_karyawan_dibutuhkan' => 3,
        ]);
        $jobPosting = $this->createJobPosting($pipeline, $firstRequest);

        $this->assertSame(
            [$firstRequest->id],
            JobPostingResource::resolveEditableLinkedRequestManPowerIds($jobPosting->fresh(['requestManPowers', 'requestManPower']))
        );
        $this->assertArrayHasKey(
            $secondRequest->id,
            JobPostingResource::resolveEditableRequestManPowerOptions($jobPosting->fresh())
        );

        JobPostingResource::syncLinkedRequestManPowers($jobPosting->fresh(), [
            $firstRequest->id,
            $secondRequest->id,
        ]);

        $this->assertSame($jobPosting->id, $firstRequest->fresh()->job_posting_id);
        $this->assertSame($jobPosting->id, $secondRequest->fresh()->job_posting_id);
        $this->assertSame($firstRequest->id, $jobPosting->fresh()->request_man_power_id);
        $this->assertSame(5, $jobPosting->fresh()->totalNeeded());

        JobPostingResource::syncLinkedRequestManPowers($jobPosting->fresh(), [
            $secondRequest->id,
        ]);

        $this->assertNull($firstRequest->fresh()->job_posting_id);
        $this->assertSame($jobPosting->id, $secondRequest->fresh()->job_posting_id);
        $this->assertSame($secondRequest->id, $jobPosting->fresh()->request_man_power_id);
        $this->assertSame(3, $jobPosting->fresh()->totalNeeded());

        JobPostingResource::syncLinkedRequestManPowers($jobPosting->fresh(), []);

        $this->assertNull($secondRequest->fresh()->job_posting_id);
        $this->assertNull($jobPosting->fresh()->request_man_power_id);
        $this->assertSame(0, $jobPosting->fresh()->requestManPowers()->count());

        $detachedOverview = JobPostingResource::formatLinkedRequestManPowersOverview($jobPosting->fresh(['requestManPowers']));

        $this->assertStringContainsString('0 MPP', $detachedOverview);
        $this->assertStringContainsString('Total kebutuhan 0 orang', $detachedOverview);
    }

    public function test_job_posting_edit_page_saves_linked_manpower_request_selection(): void
    {
        $pipeline = RekrutmenPipeline::query()->create([
            'name' => 'Livewire Editable MPP Pipeline',
        ]);

        $company = Company::query()->create([
            'name' => 'PT Livewire Editable MPP',
        ]);

        $currentRequest = $this->createRequestManPower($company, [
            'email_address'              => 'current-livewire-edit@example.com',
            'jumlah_karyawan_dibutuhkan' => 2,
        ]);
        $replacementRequest = $this->createRequestManPower($company, [
            'email_address'              => 'replacement-livewire-edit@example.com',
            'jumlah_karyawan_dibutuhkan' => 3,
        ]);
        $jobPosting = $this->createJobPosting($pipeline, $currentRequest);

        Livewire::test(EditJobPosting::class, ['record' => $jobPosting->id])
            ->fillForm([
                JobPostingResource::LINKED_REQUEST_MAN_POWER_IDS_FIELD => [$replacementRequest->id],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNull($currentRequest->fresh()->job_posting_id);
        $this->assertSame($jobPosting->id, $replacementRequest->fresh()->job_posting_id);
        $this->assertSame($replacementRequest->id, $jobPosting->fresh()->request_man_power_id);
    }

    public function test_job_posting_edit_keeps_current_soft_deleted_manpower_request_selectable(): void
    {
        $pipeline = RekrutmenPipeline::query()->create([
            'name' => 'Soft Deleted Linked MPP Pipeline',
        ]);

        $company = Company::query()->create([
            'name' => 'PT Soft Deleted Linked MPP',
        ]);

        $currentRequest = $this->createRequestManPower($company, [
            'email_address' => 'current-soft-deleted-linked@example.com',
        ]);
        $unrelatedDeletedRequest = $this->createRequestManPower($company, [
            'email_address' => 'unrelated-soft-deleted-linked@example.com',
        ]);
        $jobPosting = $this->createJobPosting($pipeline, $currentRequest);

        $currentRequest->delete();
        $unrelatedDeletedRequest->delete();

        $options = JobPostingResource::resolveEditableRequestManPowerOptions($jobPosting->fresh());

        $this->assertArrayHasKey($currentRequest->id, $options);
        $this->assertArrayNotHasKey($unrelatedDeletedRequest->id, $options);

        Livewire::test(EditJobPosting::class, ['record' => $jobPosting->id])
            ->fillForm([
                'title' => 'Updated Soft Deleted Linked MPP',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Updated Soft Deleted Linked MPP', $jobPosting->fresh()->title);
        $this->assertSame($currentRequest->id, $jobPosting->fresh()->request_man_power_id);
    }

    public function test_job_posting_linked_manpower_edit_rejects_total_need_below_hired_candidates(): void
    {
        $pipeline = RekrutmenPipeline::query()->create([
            'name' => 'Headcount Guard Pipeline',
        ]);
        $stage = RekrutmenStage::query()->create([
            'rekrutmen_pipeline_id' => $pipeline->id,
            'name'                  => 'Screening',
            'order_column'          => 1,
        ]);

        $company = Company::query()->create([
            'name' => 'PT Headcount Guard',
        ]);

        $currentRequest = $this->createRequestManPower($company, [
            'email_address'              => 'current-headcount@example.com',
            'jumlah_karyawan_dibutuhkan' => 2,
        ]);
        $smallerRequest = $this->createRequestManPower($company, [
            'email_address'              => 'smaller-headcount@example.com',
            'jumlah_karyawan_dibutuhkan' => 1,
        ]);
        $jobPosting = $this->createJobPosting($pipeline, $currentRequest);

        $this->createHiredApplication($jobPosting, $stage, 'hired-one@example.com', 'Hired One');
        $this->createHiredApplication($jobPosting, $stage, 'hired-two@example.com', 'Hired Two');

        try {
            JobPostingResource::syncLinkedRequestManPowers($jobPosting->fresh(), [
                $smallerRequest->id,
            ]);

            $this->fail('The job posting accepted a lower MPP headcount than its hired candidates.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(JobPostingResource::LINKED_REQUEST_MAN_POWER_IDS_FIELD, $exception->errors());
        }

        $this->assertSame($jobPosting->id, $currentRequest->fresh()->job_posting_id);
        $this->assertNull($smallerRequest->fresh()->job_posting_id);
        $this->assertSame($currentRequest->id, $jobPosting->fresh()->request_man_power_id);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createRequestManPower(Company $company, array $overrides = []): RequestManPower
    {
        return RequestManPower::query()->create([
            'company_id'                 => $company->id,
            'email_address'              => 'request-'.uniqid().'@example.com',
            'nama_pengaju'               => 'HR Requester',
            'posisi_pengaju'             => 'HR Manager',
            'tanggal_pengajuan'          => now()->toDateString(),
            'posisi_dibutuhkan'          => 'Sales Advisor',
            'lokasi_penempatan'          => 'Cirebon',
            'status_kebutuhan'           => StatusKebutuhan::NEW_HIRING,
            'divisi'                     => 'Sales',
            'level_pekerjaan'            => 'Staff',
            'jumlah_karyawan_dibutuhkan' => 1,
            'estimasi_tanggal_join'      => now()->addWeek()->toDateString(),
            'requirements_kualifikasi'   => 'Sales experience',
            'job_description'            => 'Serve customers',
            'status'                     => RequestManPowerStatus::APPROVED,
            ...$overrides,
        ]);
    }

    private function createJobPosting(RekrutmenPipeline $pipeline, RequestManPower $requestManPower): JobPosting
    {
        $jobPosting = JobPosting::query()->create([
            'request_man_power_id'  => $requestManPower->id,
            'rekrutmen_pipeline_id' => $pipeline->id,
            'title'                 => 'Sales Advisor Cirebon '.$requestManPower->id,
            'slug'                  => 'sales-advisor-cirebon-'.$requestManPower->id,
            'description'           => 'Serve customers',
            'requirements'          => 'Sales experience',
            'location'              => 'Cirebon',
            'is_published'          => true,
            'closing_date'          => now()->addMonth()->toDateString(),
        ]);

        $requestManPower->forceFill([
            'job_posting_id' => $jobPosting->id,
        ])->saveQuietly();

        return $jobPosting->fresh();
    }

    private function createHiredApplication(
        JobPosting $jobPosting,
        RekrutmenStage $stage,
        string $email,
        string $fullName,
    ): JobApplication {
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
            'whatsapp_number'            => $this->uniquePhoneNumberFor($email, '081'),
            'active_phone'               => $this->uniquePhoneNumberFor($email, '082'),
            'emergency_contact_name'     => 'Bunga',
            'emergency_contact_relation' => 'Saudara',
            'emergency_contact_phone'    => $this->uniquePhoneNumberFor($email, '083'),
            'status'                     => JobApplicationStatus::HIRED,
        ]);
    }

    private function uniquePhoneNumberFor(string $value, string $prefix): string
    {
        $suffix = str_pad((string) (abs(crc32($value)) % 100000000), 8, '0', STR_PAD_LEFT);

        return $prefix.$suffix;
    }

    private function registerJobPostingPageRoutes(): void
    {
        foreach ([
            'filament.admin.resources.job-postings.index',
            'filament.admin.resources.job-postings.edit',
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
