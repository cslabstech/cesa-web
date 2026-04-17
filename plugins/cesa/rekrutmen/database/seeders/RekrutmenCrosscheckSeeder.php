<?php

namespace Cesa\Rekrutmen\Database\Seeders;

use Carbon\CarbonImmutable;
use Cesa\Rekrutmen\Enums\ActivityEntryResult;
use Cesa\Rekrutmen\Enums\ActivityType;
use Cesa\Rekrutmen\Enums\JobApplicationGender;
use Cesa\Rekrutmen\Enums\JobApplicationMaritalStatus;
use Cesa\Rekrutmen\Enums\JobApplicationStatus;
use Cesa\Rekrutmen\Enums\RequestManPowerApprovalStatus;
use Cesa\Rekrutmen\Enums\RequestManPowerStatus;
use Cesa\Rekrutmen\Enums\StatusKebutuhan;
use Cesa\Rekrutmen\Models\JobApplication;
use Cesa\Rekrutmen\Models\RekrutmenPipeline;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;
use Webkul\Support\Models\Company;

class RekrutmenCrosscheckSeeder extends Seeder
{
    public const SAMPLE_EMAIL_DOMAIN = 'crosscheck.rekrutmen.test';

    public const SAMPLE_POSTING_SLUG_PREFIX = 'crosscheck-';

    public const REQUEST_COUNT = 6;

    public const JOB_POSTING_COUNT = 4;

    public const JOB_APPLICATION_COUNT = 13;

    public const DELETED_JOB_APPLICATION_COUNT = 1;

    public const BATCH_ACTIVITY_GROUP_COUNT = 13;

    public const DIVISION_COUNT = 5;

    public const APPROVER_COUNT = 10;

    public const APPROVAL_COUNT = 12;

    private CarbonImmutable $today;

    /**
     * @var array<int, int>
     */
    private array $stagePositionCounters = [];

    /**
     * @var Collection<string, int>
     */
    private Collection $divisionIds;

    /**
     * @var array<string, array{first: int, second: int}>
     */
    private array $approvalStepApprovers = [];

    /**
     * @var array<int, string>
     */
    private array $stageNamesById = [];

    public function run(): void
    {
        $this->call([
            DatabaseSeeder::class,
        ]);

        $this->today = now()->startOfDay()->toImmutable();

        DB::transaction(function (): void {
            $this->purgeExistingSampleDataset();

            $companies = $this->seedCompanies();
            $this->divisionIds = $this->seedDivisions($companies);
            $users = $this->seedUsers((int) $companies->get('tech'));
            $this->approvalStepApprovers = $this->seedApprovers($companies, $this->divisionIds);
            $stages = $this->resolveStages();

            $this->seedBackendEngineerScenario($companies, $users, $stages);
            $this->seedSalesExecutiveScenario($companies, $users, $stages);
            $this->seedWarehouseSupervisorScenario($companies, $users, $stages);
            $this->seedInternalAuditorScenario($companies, $users, $stages);
            $this->seedPendingFinanceRequest($companies, $users);
            $this->seedRejectedQaRequest($companies, $users);
        });
    }

    private function purgeExistingSampleDataset(): void
    {
        $requestIds = DB::table('rekrutmen_request_man_powers')
            ->where('email_address', 'like', '%@'.self::SAMPLE_EMAIL_DOMAIN)
            ->pluck('id');

        $postingIds = DB::table('rekrutmen_job_postings')
            ->where('slug', 'like', self::SAMPLE_POSTING_SLUG_PREFIX.'%')
            ->pluck('id');

        $applicationIds = DB::table('rekrutmen_job_applications')
            ->where('email', 'like', '%@'.self::SAMPLE_EMAIL_DOMAIN)
            ->orWhereIn('job_posting_id', $postingIds)
            ->pluck('id');

        if ($applicationIds->isNotEmpty()) {
            DB::table('rekrutmen_job_application_histories')
                ->whereIn('job_application_id', $applicationIds)
                ->delete();

            DB::table('rekrutmen_job_applications')
                ->whereIn('id', $applicationIds)
                ->delete();
        }

        if ($postingIds->isNotEmpty()) {
            DB::table('rekrutmen_job_postings')
                ->whereIn('id', $postingIds)
                ->delete();
        }

        if ($requestIds->isNotEmpty()) {
            DB::table('rekrutmen_request_man_powers')
                ->whereIn('id', $requestIds)
                ->delete();
        }
    }

    /**
     * @return Collection<string, int>
     */
    private function seedCompanies(): Collection
    {
        return collect([
            'tech'       => 'PT Crosscheck Technology',
            'retail'     => 'PT Crosscheck Retail Nusantara',
            'operations' => 'PT Crosscheck Operasional',
            'audit'      => 'PT Crosscheck Assurance',
            'finance'    => 'PT Crosscheck Finance',
        ])->mapWithKeys(function (string $name, string $key): array {
            $company = Company::query()->firstOrCreate(
                ['name' => $name],
                ['is_active' => true],
            );

            if (! $company->is_active) {
                $company->update(['is_active' => true]);
            }

            return [$key => (int) $company->getKey()];
        });
    }

    /**
     * @param  Collection<string, int>  $companies
     * @return Collection<string, int>
     */
    private function seedDivisions(Collection $companies): Collection
    {
        $timestamp = $this->today->setTime(8, 15);

        return collect([
            'tech'       => ['company_key' => 'tech', 'name' => 'Technology'],
            'retail'     => ['company_key' => 'retail', 'name' => 'Commercial'],
            'operations' => ['company_key' => 'operations', 'name' => 'Operations'],
            'audit'      => ['company_key' => 'audit', 'name' => 'Audit'],
            'finance'    => ['company_key' => 'finance', 'name' => 'Finance'],
        ])->mapWithKeys(function (array $definition, string $key) use ($companies, $timestamp): array {
            $companyId = (int) $companies->get($definition['company_key']);
            $divisionName = $definition['name'];

            DB::table('rekrutmen_divisions')->updateOrInsert(
                [
                    'company_id' => $companyId,
                    'name'       => $divisionName,
                ],
                [
                    'is_active'  => true,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                    'deleted_at' => null,
                ],
            );

            $divisionId = DB::table('rekrutmen_divisions')
                ->where('company_id', $companyId)
                ->where('name', $divisionName)
                ->value('id');

            if (! is_numeric($divisionId)) {
                throw new RuntimeException("Unable to resolve division id for [{$divisionName}].");
            }

            return [$key => (int) $divisionId];
        });
    }

    /**
     * @param  Collection<string, int>  $companies
     * @param  Collection<string, int>  $divisions
     * @return array<string, array{first: int, second: int}>
     */
    private function seedApprovers(Collection $companies, Collection $divisions): array
    {
        $timestamp = $this->today->setTime(8, 30);

        return collect([
            'tech' => [
                'company_key'  => 'tech',
                'division_key' => 'tech',
                'first'        => [
                    'name'  => 'Tech HRBP Crosscheck',
                    'email' => 'tech.hrbp@'.self::SAMPLE_EMAIL_DOMAIN,
                    'title' => 'HR Business Partner',
                ],
                'second'       => [
                    'name'  => 'Tech Director Crosscheck',
                    'email' => 'tech.director@'.self::SAMPLE_EMAIL_DOMAIN,
                    'title' => 'Director of Technology',
                ],
            ],
            'retail' => [
                'company_key'  => 'retail',
                'division_key' => 'retail',
                'first'        => [
                    'name'  => 'Retail HRBP Crosscheck',
                    'email' => 'retail.hrbp@'.self::SAMPLE_EMAIL_DOMAIN,
                    'title' => 'HR Business Partner',
                ],
                'second'       => [
                    'name'  => 'Retail Director Crosscheck',
                    'email' => 'retail.director@'.self::SAMPLE_EMAIL_DOMAIN,
                    'title' => 'Commercial Director',
                ],
            ],
            'operations' => [
                'company_key'  => 'operations',
                'division_key' => 'operations',
                'first'        => [
                    'name'  => 'Operations HRBP Crosscheck',
                    'email' => 'operations.hrbp@'.self::SAMPLE_EMAIL_DOMAIN,
                    'title' => 'HR Business Partner',
                ],
                'second'       => [
                    'name'  => 'Operations Director Crosscheck',
                    'email' => 'operations.director@'.self::SAMPLE_EMAIL_DOMAIN,
                    'title' => 'Operations Director',
                ],
            ],
            'audit' => [
                'company_key'  => 'audit',
                'division_key' => 'audit',
                'first'        => [
                    'name'  => 'Audit HRBP Crosscheck',
                    'email' => 'audit.hrbp@'.self::SAMPLE_EMAIL_DOMAIN,
                    'title' => 'HR Business Partner',
                ],
                'second'       => [
                    'name'  => 'Audit Director Crosscheck',
                    'email' => 'audit.director@'.self::SAMPLE_EMAIL_DOMAIN,
                    'title' => 'Head of Internal Audit',
                ],
            ],
            'finance' => [
                'company_key'  => 'finance',
                'division_key' => 'finance',
                'first'        => [
                    'name'  => 'Finance HRBP Crosscheck',
                    'email' => 'finance.hrbp@'.self::SAMPLE_EMAIL_DOMAIN,
                    'title' => 'HR Business Partner',
                ],
                'second'       => [
                    'name'  => 'Finance Director Crosscheck',
                    'email' => 'finance.director@'.self::SAMPLE_EMAIL_DOMAIN,
                    'title' => 'Finance Director',
                ],
            ],
        ])->mapWithKeys(function (array $definition, string $key) use ($companies, $divisions, $timestamp): array {
            $companyId = (int) $companies->get($definition['company_key']);
            $divisionId = (int) $divisions->get($definition['division_key']);
            $divisionName = DB::table('rekrutmen_divisions')
                ->where('id', $divisionId)
                ->value('name');

            if (! is_string($divisionName) || $divisionName === '') {
                throw new RuntimeException("Unable to resolve division snapshot for approval scope [{$key}].");
            }

            $approverIds = [];

            foreach (['first', 'second'] as $index => $slot) {
                $approver = $definition[$slot];

                DB::table('rekrutmen_approvers')->updateOrInsert(
                    ['email' => $approver['email']],
                    [
                        'name'           => $approver['name'],
                        'phone'          => null,
                        'title'          => $approver['title'],
                        'company_id'     => $companyId,
                        'division_id'    => $divisionId,
                        'approval_order' => $index + 1,
                        'divisi'         => $divisionName,
                        'is_active'      => true,
                        'created_by'     => null,
                        'created_at'     => $timestamp,
                        'updated_at'     => $timestamp,
                        'deleted_at'     => null,
                    ],
                );

                $approverId = DB::table('rekrutmen_approvers')
                    ->where('email', $approver['email'])
                    ->value('id');

                if (! is_numeric($approverId)) {
                    throw new RuntimeException("Unable to resolve approver id for [{$approver['email']}].");
                }

                $approverIds[$slot] = (int) $approverId;
            }

            return [$key => [
                'first'  => $approverIds['first'],
                'second' => $approverIds['second'],
            ]];
        })->all();
    }

    /**
     * @return array{approver_id: int, recruiter_id: int}
     */
    private function seedUsers(int $defaultCompanyId): array
    {
        $approverId = $this->upsertUser(
            name: 'Crosscheck HR Approver',
            email: 'approver@'.self::SAMPLE_EMAIL_DOMAIN,
            defaultCompanyId: $defaultCompanyId,
        );

        $recruiterId = $this->upsertUser(
            name: 'Crosscheck Recruiter',
            email: 'recruiter@'.self::SAMPLE_EMAIL_DOMAIN,
            defaultCompanyId: $defaultCompanyId,
        );

        return [
            'approver_id'  => $approverId,
            'recruiter_id' => $recruiterId,
        ];
    }

    private function upsertUser(string $name, string $email, int $defaultCompanyId): int
    {
        $timestamp = $this->today->setTime(8, 0);
        $existingUser = DB::table('users')
            ->where('email', $email)
            ->first();

        $payload = [
            'name'                => $name,
            'email'               => $email,
            'email_verified_at'   => $existingUser?->email_verified_at ?? $timestamp,
            'updated_at'          => $timestamp,
        ];

        if (Schema::hasColumn('users', 'language')) {
            $payload['language'] = 'id';
        }

        if (Schema::hasColumn('users', 'default_company_id')) {
            $payload['default_company_id'] = $defaultCompanyId;
        }

        if (Schema::hasColumn('users', 'resource_permission')) {
            $payload['resource_permission'] = 'individual';
        }

        if (Schema::hasColumn('users', 'is_active')) {
            $payload['is_active'] = true;
        }

        if (Schema::hasColumn('users', 'deleted_at')) {
            $payload['deleted_at'] = null;
        }

        if ($existingUser) {
            DB::table('users')
                ->where('id', $existingUser->id)
                ->update($payload);

            return (int) $existingUser->id;
        }

        return (int) DB::table('users')->insertGetId([
            ...$payload,
            'password'       => Hash::make('rekrutmen-crosscheck'),
            'remember_token' => Str::random(10),
            'created_at'     => $timestamp,
        ]);
    }

    /**
     * @return array<string, int>
     */
    private function resolveStages(): array
    {
        $pipeline = RekrutmenPipeline::query()
            ->where('name', 'Default Recruitment Pipeline')
            ->first();

        if (! $pipeline) {
            throw new RuntimeException('Default Recruitment Pipeline is required before seeding crosscheck data.');
        }

        $stages = $pipeline->stages()
            ->get()
            ->keyBy('name');

        $requiredStages = [
            'Screening CV',
            'Interview HR',
            'Interview User',
            'Offering',
            'Hired',
        ];

        foreach ($requiredStages as $stageName) {
            if (! $stages->has($stageName)) {
                throw new RuntimeException("Stage [{$stageName}] is required before seeding crosscheck data.");
            }
        }

        $this->stageNamesById = $stages
            ->mapWithKeys(fn (object $stage): array => [(int) $stage->id => (string) $stage->name])
            ->all();

        return $stages
            ->mapWithKeys(fn (object $stage): array => [(string) $stage->name => (int) $stage->id])
            ->all();
    }

    /**
     * @param  Collection<string, int>  $companies
     * @param  array{approver_id: int, recruiter_id: int}  $users
     * @param  array<string, int>  $stages
     */
    private function seedBackendEngineerScenario(Collection $companies, array $users, array $stages): void
    {
        $requestId = $this->upsertRequest(
            statusResponseId: '0f8430f1-34c3-46e6-9b4e-000000000001',
            companyId: (int) $companies->get('tech'),
            divisionId: (int) $this->divisionIds->get('tech'),
            emailAddress: 'backend-request@'.self::SAMPLE_EMAIL_DOMAIN,
            namaPengaju: 'RANI WIDYA',
            posisiPengaju: 'HR BUSINESS PARTNER',
            tanggalPengajuan: $this->date(81),
            posisiDibutuhkan: 'Backend Engineer',
            lokasiPenempatan: 'Jakarta',
            statusKebutuhan: StatusKebutuhan::NEW_HIRING,
            divisi: 'Technology',
            levelPekerjaan: 'Staff',
            namaKaryawanReplacement: null,
            jumlahKaryawanDibutuhkan: 2,
            estimasiTanggalJoin: $this->date(47),
            requirementsKualifikasi: 'Menguasai Laravel, API design, dan query optimization.',
            jobDescription: 'Membangun dan memelihara layanan backend untuk aplikasi operasional.',
            keterangan: 'Crosscheck Januari untuk hiring kebutuhan backend utama.',
            status: RequestManPowerStatus::APPROVED,
            approvedBy: $users['approver_id'],
            createdAt: $this->timestamp(81, 9),
            updatedAt: $this->timestamp(79, 14),
        );

        $techApprovers = $this->approvalStepApprovers['tech'];

        $this->upsertApproval(
            requestId: $requestId,
            approverId: $techApprovers['first'],
            stepOrder: 1,
            status: RequestManPowerApprovalStatus::APPROVED,
            actionToken: '9c100000-0000-4000-8000-000000000001',
            actionExpiresAt: $this->timestamp(74, 23, 59),
            notifiedAt: $this->timestamp(81, 10),
            actedAt: $this->timestamp(80, 12),
            notes: 'Approval HRBP Technology selesai.',
            actedByUserId: $users['approver_id'],
            createdAt: $this->timestamp(81, 9),
            updatedAt: $this->timestamp(80, 12),
        );
        $this->upsertApproval(
            requestId: $requestId,
            approverId: $techApprovers['second'],
            stepOrder: 2,
            status: RequestManPowerApprovalStatus::APPROVED,
            actionToken: '9c100000-0000-4000-8000-000000000002',
            actionExpiresAt: $this->timestamp(72, 23, 59),
            notifiedAt: $this->timestamp(80, 13),
            actedAt: $this->timestamp(79, 14),
            notes: 'Approval final Technology diterbitkan.',
            actedByUserId: $users['approver_id'],
            createdAt: $this->timestamp(80, 13),
            updatedAt: $this->timestamp(79, 14),
        );

        $postingId = $this->upsertPosting(
            requestId: $requestId,
            slug: self::SAMPLE_POSTING_SLUG_PREFIX.'backend-engineer-jakarta',
            pipelineId: $this->resolvePipelineIdFromStage($stages['Screening CV']),
            title: 'Backend Engineer Jakarta',
            description: 'Posisi backend untuk menjaga stabilitas API, queue, dan integrasi ERP.',
            requirements: 'Laravel, MySQL, Redis, Git, dan pemahaman clean architecture.',
            location: 'Jakarta',
            thumbnailPath: 'rekrutmen/job-postings/crosscheck/backend-engineer-jakarta.jpg',
            isPublished: false,
            closingDate: $this->date(20),
            createdAt: $this->timestamp(79, 15),
            updatedAt: $this->timestamp(20, 18),
        );

        $screeningGroupId = '7b200000-0000-4000-8000-000000000001';
        $hrGroupId = '7b200000-0000-4000-8000-000000000002';
        $userGroupId = '7b200000-0000-4000-8000-000000000003';
        $offeringGroupId = '7b200000-0000-4000-8000-000000000004';

        $alyaId = $this->upsertApplication(
            jobPostingId: $postingId,
            currentStageId: $stages['Hired'],
            fullName: 'ALYA RAHMAWATI',
            email: 'alya.rahmawati@'.self::SAMPLE_EMAIL_DOMAIN,
            gender: JobApplicationGender::Female,
            birthDate: '1997-03-18',
            maritalStatus: JobApplicationMaritalStatus::Single,
            addressKtp: 'JL. MELATI NO. 12 JAKARTA',
            addressDomicile: 'JL. ANGGREK NO. 5 JAKARTA',
            whatsappNumber: '628111111111',
            activePhone: '628111111111',
            emergencyContactName: 'RAHMA WIDYA',
            emergencyContactRelation: 'IBU',
            emergencyContactPhone: '628121111111',
            photoPath: 'rekrutmen/photos/crosscheck/alya-rahmawati.jpg',
            resumePath: 'rekrutmen/cv/crosscheck/alya-rahmawati.pdf',
            source: 'career-site',
            status: JobApplicationStatus::HIRED,
            createdAt: $this->timestamp(76, 10),
            updatedAt: $this->timestamp(51, 16),
        );

        $this->upsertInitialHistory($alyaId, $stages['Screening CV'], 'Lamaran masuk melalui career site.', $users['recruiter_id'], $this->timestamp(76, 10));
        $this->upsertBatchHistory($alyaId, $stages['Screening CV'], ActivityType::SCREENING, ActivityEntryResult::PASSED, JobApplicationStatus::IN_PROGRESS, $screeningGroupId, 'Skor screening 86, lanjut HR interview.', $users['recruiter_id'], $this->timestamp(72, 14));
        $this->upsertStageTransitionHistory($alyaId, $stages['Screening CV'], $stages['Interview HR'], 'Lolos screening dan dijadwalkan Interview HR.', $users['recruiter_id'], $this->timestamp(72, 15));
        $this->upsertBatchHistory($alyaId, $stages['Interview HR'], ActivityType::INTERVIEW_HRD, ActivityEntryResult::PASSED, JobApplicationStatus::IN_PROGRESS, $hrGroupId, 'Komunikasi kuat dan sesuai budaya kerja.', $users['recruiter_id'], $this->timestamp(66, 15));
        $this->upsertStageTransitionHistory($alyaId, $stages['Interview HR'], $stages['Interview User'], 'Lolos Interview HR dan naik ke user interview.', $users['recruiter_id'], $this->timestamp(66, 16));
        $this->upsertBatchHistory($alyaId, $stages['Interview User'], ActivityType::INTERVIEW_USER, ActivityEntryResult::PASSED, JobApplicationStatus::IN_PROGRESS, $userGroupId, 'User menyetujui kandidat untuk tahap offering.', $users['recruiter_id'], $this->timestamp(59, 14));
        $this->upsertStageTransitionHistory($alyaId, $stages['Interview User'], $stages['Offering'], 'Lolos user interview dan masuk tahap offering.', $users['recruiter_id'], $this->timestamp(59, 15));
        $this->upsertBatchHistory($alyaId, $stages['Offering'], ActivityType::OFFERING, ActivityEntryResult::PASSED, JobApplicationStatus::IN_PROGRESS, $offeringGroupId, 'Offering diterima kandidat.', $users['recruiter_id'], $this->timestamp(55, 13));
        $this->upsertStageTransitionHistory($alyaId, $stages['Offering'], $stages['Hired'], 'Offering selesai dan kandidat masuk tahap hired.', $users['recruiter_id'], $this->timestamp(55, 14));
        $this->upsertDecisionHistory($alyaId, $stages['Hired'], $stages['Hired'], JobApplicationStatus::HIRED, 'Kandidat menerima offering dan dijadwalkan join.', $users['recruiter_id'], $this->timestamp(51, 16));

        $bimaId = $this->upsertApplication(
            jobPostingId: $postingId,
            currentStageId: $stages['Interview HR'],
            fullName: 'BIMA SAPUTRA',
            email: 'bima.saputra@'.self::SAMPLE_EMAIL_DOMAIN,
            gender: JobApplicationGender::Male,
            birthDate: '1994-08-02',
            maritalStatus: JobApplicationMaritalStatus::Married,
            addressKtp: 'JL. KENANGA NO. 7 BEKASI',
            addressDomicile: 'JL. KENANGA NO. 7 BEKASI',
            whatsappNumber: '628122222222',
            activePhone: '628122222222',
            emergencyContactName: 'MIRA SAPUTRA',
            emergencyContactRelation: 'ISTRI',
            emergencyContactPhone: '628132222222',
            photoPath: 'rekrutmen/photos/crosscheck/bima-saputra.jpg',
            resumePath: 'rekrutmen/cv/crosscheck/bima-saputra.pdf',
            source: 'linkedin',
            status: JobApplicationStatus::REJECTED,
            createdAt: $this->timestamp(75, 9),
            updatedAt: $this->timestamp(66, 17),
        );

        $this->upsertInitialHistory($bimaId, $stages['Screening CV'], 'Lamaran masuk melalui career site.', $users['recruiter_id'], $this->timestamp(75, 9));
        $this->upsertBatchHistory($bimaId, $stages['Screening CV'], ActivityType::SCREENING, ActivityEntryResult::PASSED, JobApplicationStatus::IN_PROGRESS, $screeningGroupId, 'CV memenuhi minimum requirement.', $users['recruiter_id'], $this->timestamp(72, 14));
        $this->upsertStageTransitionHistory($bimaId, $stages['Screening CV'], $stages['Interview HR'], 'Lolos screening dan dijadwalkan Interview HR.', $users['recruiter_id'], $this->timestamp(72, 15));
        $this->upsertBatchHistory($bimaId, $stages['Interview HR'], ActivityType::INTERVIEW_HRD, ActivityEntryResult::FAILED, JobApplicationStatus::IN_PROGRESS, $hrGroupId, 'Ekspektasi kompensasi tidak sesuai budget.', $users['recruiter_id'], $this->timestamp(66, 15));
        $this->upsertDecisionHistory($bimaId, $stages['Interview HR'], $stages['Interview HR'], JobApplicationStatus::REJECTED, 'Ditolak setelah Interview HR.', $users['recruiter_id'], $this->timestamp(66, 17));

        $citraId = $this->upsertApplication(
            jobPostingId: $postingId,
            currentStageId: $stages['Screening CV'],
            fullName: 'CITRA LESTARI',
            email: 'citra.lestari@'.self::SAMPLE_EMAIL_DOMAIN,
            gender: JobApplicationGender::Female,
            birthDate: '1998-11-11',
            maritalStatus: JobApplicationMaritalStatus::Single,
            addressKtp: 'JL. FLAMBOYAN NO. 3 TANGERANG',
            addressDomicile: 'JL. FLAMBOYAN NO. 3 TANGERANG',
            whatsappNumber: '628133333333',
            activePhone: '628133333333',
            emergencyContactName: 'DINA LESTARI',
            emergencyContactRelation: 'KAKAK',
            emergencyContactPhone: '628143333333',
            photoPath: 'rekrutmen/photos/crosscheck/citra-lestari.jpg',
            resumePath: 'rekrutmen/cv/crosscheck/citra-lestari.pdf',
            source: 'employee-referral',
            status: JobApplicationStatus::REJECTED,
            createdAt: $this->timestamp(74, 11),
            updatedAt: $this->timestamp(72, 18),
        );

        $this->upsertInitialHistory($citraId, $stages['Screening CV'], 'Lamaran masuk melalui career site.', $users['recruiter_id'], $this->timestamp(74, 11));
        $this->upsertBatchHistory($citraId, $stages['Screening CV'], ActivityType::SCREENING, ActivityEntryResult::FAILED, JobApplicationStatus::IN_PROGRESS, $screeningGroupId, 'Pengalaman backend belum memenuhi kebutuhan.', $users['recruiter_id'], $this->timestamp(72, 14));
        $this->upsertDecisionHistory($citraId, $stages['Screening CV'], $stages['Screening CV'], JobApplicationStatus::REJECTED, 'Ditolak di tahap screening CV.', $users['recruiter_id'], $this->timestamp(72, 18));

        $deniId = $this->upsertApplication(
            jobPostingId: $postingId,
            currentStageId: $stages['Screening CV'],
            fullName: 'DENI PRATAMA',
            email: 'deni.pratama@'.self::SAMPLE_EMAIL_DOMAIN,
            gender: JobApplicationGender::Male,
            birthDate: '1999-04-10',
            maritalStatus: JobApplicationMaritalStatus::Single,
            addressKtp: 'JL. TERATAI NO. 14 DEPOK',
            addressDomicile: 'JL. TERATAI NO. 14 DEPOK',
            whatsappNumber: '628144444444',
            activePhone: '628144444444',
            emergencyContactName: 'YULI PRATAMA',
            emergencyContactRelation: 'AYAH',
            emergencyContactPhone: '628154444444',
            photoPath: 'rekrutmen/photos/crosscheck/deni-pratama.jpg',
            resumePath: 'rekrutmen/cv/crosscheck/deni-pratama.pdf',
            source: 'github',
            status: JobApplicationStatus::IN_PROGRESS,
            createdAt: $this->timestamp(68, 10),
            updatedAt: $this->timestamp(67, 16),
        );

        $this->upsertInitialHistory($deniId, $stages['Screening CV'], 'Lamaran masuk melalui career site.', $users['recruiter_id'], $this->timestamp(68, 10));
        $this->upsertBatchHistory($deniId, $stages['Screening CV'], ActivityType::SCREENING, ActivityEntryResult::PENDING, JobApplicationStatus::IN_PROGRESS, '7b200000-0000-4000-8000-000000000005', 'Menunggu review user karena portofolio belum lengkap.', $users['recruiter_id'], $this->timestamp(67, 16));

        $ekaId = $this->upsertApplication(
            jobPostingId: $postingId,
            currentStageId: $stages['Screening CV'],
            fullName: 'EKA PUTRI',
            email: 'eka.putri@'.self::SAMPLE_EMAIL_DOMAIN,
            gender: JobApplicationGender::Female,
            birthDate: '1996-06-21',
            maritalStatus: JobApplicationMaritalStatus::Married,
            addressKtp: 'JL. MAWAR NO. 22 BOGOR',
            addressDomicile: 'JL. MAWAR NO. 22 BOGOR',
            whatsappNumber: '628155555555',
            activePhone: '628155555555',
            emergencyContactName: 'ANDI PUTRA',
            emergencyContactRelation: 'SUAMI',
            emergencyContactPhone: '628165555555',
            photoPath: 'rekrutmen/photos/crosscheck/eka-putri.jpg',
            resumePath: 'rekrutmen/cv/crosscheck/eka-putri.pdf',
            source: 'jobstreet',
            status: JobApplicationStatus::WITHDRAWN,
            createdAt: $this->timestamp(63, 10),
            updatedAt: $this->timestamp(57, 15),
        );

        $this->upsertInitialHistory($ekaId, $stages['Screening CV'], 'Lamaran masuk melalui career site.', $users['recruiter_id'], $this->timestamp(63, 10));
        $this->upsertDecisionHistory($ekaId, $stages['Screening CV'], $stages['Screening CV'], JobApplicationStatus::WITHDRAWN, 'Kandidat mengundurkan diri sebelum proses screening selesai.', $users['recruiter_id'], $this->timestamp(57, 15));
    }

    /**
     * @param  Collection<string, int>  $companies
     * @param  array{approver_id: int, recruiter_id: int}  $users
     * @param  array<string, int>  $stages
     */
    private function seedSalesExecutiveScenario(Collection $companies, array $users, array $stages): void
    {
        $requestId = $this->upsertRequest(
            statusResponseId: '0f8430f1-34c3-46e6-9b4e-000000000002',
            companyId: (int) $companies->get('retail'),
            divisionId: (int) $this->divisionIds->get('retail'),
            emailAddress: 'sales-request@'.self::SAMPLE_EMAIL_DOMAIN,
            namaPengaju: 'RIZKY ANANDA',
            posisiPengaju: 'COMMERCIAL MANAGER',
            tanggalPengajuan: $this->date(64),
            posisiDibutuhkan: 'Sales Executive',
            lokasiPenempatan: 'Surabaya',
            statusKebutuhan: StatusKebutuhan::REPLACEMENT,
            divisi: 'Commercial',
            levelPekerjaan: 'Coordinator',
            namaKaryawanReplacement: 'RINA HANDAYANI',
            jumlahKaryawanDibutuhkan: 1,
            estimasiTanggalJoin: $this->date(14),
            requirementsKualifikasi: 'Pengalaman B2B sales, negosiasi, dan reporting pipeline.',
            jobDescription: 'Mengelola prospek penjualan area Jawa Timur dan menjaga key account.',
            keterangan: 'Replacement untuk area sales Surabaya.',
            status: RequestManPowerStatus::APPROVED,
            approvedBy: $users['approver_id'],
            createdAt: $this->timestamp(64, 9),
            updatedAt: $this->timestamp(61, 11),
        );

        $retailApprovers = $this->approvalStepApprovers['retail'];

        $this->upsertApproval(
            requestId: $requestId,
            approverId: $retailApprovers['first'],
            stepOrder: 1,
            status: RequestManPowerApprovalStatus::APPROVED,
            actionToken: '9c100000-0000-4000-8000-000000000003',
            actionExpiresAt: $this->timestamp(58, 23, 59),
            notifiedAt: $this->timestamp(64, 10),
            actedAt: $this->timestamp(63, 11),
            notes: 'Approval HRBP Commercial selesai.',
            actedByUserId: $users['approver_id'],
            createdAt: $this->timestamp(64, 9),
            updatedAt: $this->timestamp(63, 11),
        );
        $this->upsertApproval(
            requestId: $requestId,
            approverId: $retailApprovers['second'],
            stepOrder: 2,
            status: RequestManPowerApprovalStatus::APPROVED,
            actionToken: '9c100000-0000-4000-8000-000000000004',
            actionExpiresAt: $this->timestamp(56, 23, 59),
            notifiedAt: $this->timestamp(63, 12),
            actedAt: $this->timestamp(61, 11),
            notes: 'Approval final Commercial diterbitkan.',
            actedByUserId: $users['approver_id'],
            createdAt: $this->timestamp(63, 12),
            updatedAt: $this->timestamp(61, 11),
        );

        $postingId = $this->upsertPosting(
            requestId: $requestId,
            slug: self::SAMPLE_POSTING_SLUG_PREFIX.'sales-executive-surabaya',
            pipelineId: $this->resolvePipelineIdFromStage($stages['Screening CV']),
            title: 'Sales Executive Surabaya',
            description: 'Posisi sales untuk memperkuat akuisisi mitra dan retail account area Surabaya.',
            requirements: 'Sales B2B, CRM, negosiasi, presentasi produk, dan analisis target.',
            location: 'Surabaya',
            thumbnailPath: 'rekrutmen/job-postings/crosscheck/sales-executive-surabaya.jpg',
            isPublished: true,
            closingDate: $this->today->addDays(21),
            createdAt: $this->timestamp(61, 12),
            updatedAt: $this->timestamp(8, 17),
        );

        $screeningGroupId = '7b200000-0000-4000-8000-000000000006';
        $hrGroupId = '7b200000-0000-4000-8000-000000000007';
        $userGroupId = '7b200000-0000-4000-8000-000000000008';
        $offeringGroupId = '7b200000-0000-4000-8000-000000000009';
        $userPendingGroupId = '7b200000-0000-4000-8000-000000000010';

        $farhanId = $this->upsertApplication(
            jobPostingId: $postingId,
            currentStageId: $stages['Interview HR'],
            fullName: 'FARHAN YUSUF',
            email: 'farhan.yusuf@'.self::SAMPLE_EMAIL_DOMAIN,
            gender: JobApplicationGender::Male,
            birthDate: '1995-02-14',
            maritalStatus: JobApplicationMaritalStatus::Married,
            addressKtp: 'JL. PAHLAWAN NO. 8 SURABAYA',
            addressDomicile: 'JL. PAHLAWAN NO. 8 SURABAYA',
            whatsappNumber: '628177777777',
            activePhone: '628177777777',
            emergencyContactName: 'PUTRI YUSUF',
            emergencyContactRelation: 'ISTRI',
            emergencyContactPhone: '628187777777',
            photoPath: 'rekrutmen/photos/crosscheck/farhan-yusuf.jpg',
            resumePath: 'rekrutmen/cv/crosscheck/farhan-yusuf.pdf',
            source: 'linkedin',
            status: JobApplicationStatus::IN_PROGRESS,
            createdAt: $this->timestamp(56, 9),
            updatedAt: $this->timestamp(45, 15),
        );

        $this->upsertInitialHistory($farhanId, $stages['Screening CV'], 'Lamaran masuk melalui career site.', $users['recruiter_id'], $this->timestamp(56, 9));
        $this->upsertBatchHistory($farhanId, $stages['Screening CV'], ActivityType::SCREENING, ActivityEntryResult::PASSED, JobApplicationStatus::IN_PROGRESS, $screeningGroupId, 'CV memenuhi target pengalaman channel sales.', $users['recruiter_id'], $this->timestamp(50, 14));
        $this->upsertStageTransitionHistory($farhanId, $stages['Screening CV'], $stages['Interview HR'], 'Masuk ke tahap Interview HR.', $users['recruiter_id'], $this->timestamp(50, 15));
        $this->upsertBatchHistory($farhanId, $stages['Interview HR'], ActivityType::INTERVIEW_HRD, ActivityEntryResult::PENDING, JobApplicationStatus::IN_PROGRESS, $hrGroupId, 'Masih menunggu feedback final HR.', $users['recruiter_id'], $this->timestamp(45, 15));

        $gitaId = $this->upsertApplication(
            jobPostingId: $postingId,
            currentStageId: $stages['Interview User'],
            fullName: 'GITA PERMATA',
            email: 'gita.permata@'.self::SAMPLE_EMAIL_DOMAIN,
            gender: JobApplicationGender::Female,
            birthDate: '1997-07-29',
            maritalStatus: JobApplicationMaritalStatus::Single,
            addressKtp: 'JL. RAYA MENGANTI NO. 17 SURABAYA',
            addressDomicile: 'JL. RAYA MENGANTI NO. 17 SURABAYA',
            whatsappNumber: '628188888888',
            activePhone: '628188888888',
            emergencyContactName: 'SINTA PERMATA',
            emergencyContactRelation: 'IBU',
            emergencyContactPhone: '628198888888',
            photoPath: 'rekrutmen/photos/crosscheck/gita-permata.jpg',
            resumePath: 'rekrutmen/cv/crosscheck/gita-permata.pdf',
            source: 'employee-referral',
            status: JobApplicationStatus::IN_PROGRESS,
            createdAt: $this->timestamp(55, 10),
            updatedAt: $this->timestamp(8, 15),
        );

        $this->upsertInitialHistory($gitaId, $stages['Screening CV'], 'Lamaran masuk melalui career site.', $users['recruiter_id'], $this->timestamp(55, 10));
        $this->upsertBatchHistory($gitaId, $stages['Screening CV'], ActivityType::SCREENING, ActivityEntryResult::PASSED, JobApplicationStatus::IN_PROGRESS, $screeningGroupId, 'CV sesuai kebutuhan retail sales.', $users['recruiter_id'], $this->timestamp(50, 14));
        $this->upsertStageTransitionHistory($gitaId, $stages['Screening CV'], $stages['Interview HR'], 'Masuk ke tahap Interview HR.', $users['recruiter_id'], $this->timestamp(50, 15));
        $this->upsertBatchHistory($gitaId, $stages['Interview HR'], ActivityType::INTERVIEW_HRD, ActivityEntryResult::PASSED, JobApplicationStatus::IN_PROGRESS, $hrGroupId, 'Lolos HR, lanjut user interview.', $users['recruiter_id'], $this->timestamp(44, 14));
        $this->upsertStageTransitionHistory($gitaId, $stages['Interview HR'], $stages['Interview User'], 'Lolos Interview HR dan dijadwalkan user interview.', $users['recruiter_id'], $this->timestamp(44, 15));
        $this->upsertBatchHistory($gitaId, $stages['Interview User'], ActivityType::INTERVIEW_USER, ActivityEntryResult::PENDING, JobApplicationStatus::IN_PROGRESS, $userPendingGroupId, 'Menunggu final approval kepala area.', $users['recruiter_id'], $this->timestamp(8, 15));

        $hanifId = $this->upsertApplication(
            jobPostingId: $postingId,
            currentStageId: $stages['Screening CV'],
            fullName: 'HANIF WIJAYA',
            email: 'hanif.wijaya@'.self::SAMPLE_EMAIL_DOMAIN,
            gender: JobApplicationGender::Male,
            birthDate: '1993-01-03',
            maritalStatus: JobApplicationMaritalStatus::Married,
            addressKtp: 'JL. MANYAR NO. 6 SURABAYA',
            addressDomicile: 'JL. MANYAR NO. 6 SURABAYA',
            whatsappNumber: '628199999999',
            activePhone: '628199999999',
            emergencyContactName: 'SARI WIJAYA',
            emergencyContactRelation: 'ISTRI',
            emergencyContactPhone: '628129999999',
            photoPath: 'rekrutmen/photos/crosscheck/hanif-wijaya.jpg',
            resumePath: 'rekrutmen/cv/crosscheck/hanif-wijaya.pdf',
            source: 'jobstreet',
            status: JobApplicationStatus::REJECTED,
            createdAt: $this->timestamp(54, 9),
            updatedAt: $this->timestamp(50, 18),
        );

        $this->upsertInitialHistory($hanifId, $stages['Screening CV'], 'Lamaran masuk melalui career site.', $users['recruiter_id'], $this->timestamp(54, 9));
        $this->upsertBatchHistory($hanifId, $stages['Screening CV'], ActivityType::SCREENING, ActivityEntryResult::FAILED, JobApplicationStatus::IN_PROGRESS, $screeningGroupId, 'Pengalaman sales lapangan kurang relevan.', $users['recruiter_id'], $this->timestamp(50, 14));
        $this->upsertDecisionHistory($hanifId, $stages['Screening CV'], $stages['Screening CV'], JobApplicationStatus::REJECTED, 'Ditolak di tahap screening sales.', $users['recruiter_id'], $this->timestamp(50, 18));

        $intanId = $this->upsertApplication(
            jobPostingId: $postingId,
            currentStageId: $stages['Hired'],
            fullName: 'INTAN SARI',
            email: 'intan.sari@'.self::SAMPLE_EMAIL_DOMAIN,
            gender: JobApplicationGender::Female,
            birthDate: '1996-10-09',
            maritalStatus: JobApplicationMaritalStatus::Single,
            addressKtp: 'JL. KALISARI NO. 10 SURABAYA',
            addressDomicile: 'JL. KALISARI NO. 10 SURABAYA',
            whatsappNumber: '628121212121',
            activePhone: '628121212121',
            emergencyContactName: 'RINA SARI',
            emergencyContactRelation: 'IBU',
            emergencyContactPhone: '628131212121',
            photoPath: 'rekrutmen/photos/crosscheck/intan-sari.jpg',
            resumePath: 'rekrutmen/cv/crosscheck/intan-sari.pdf',
            source: 'career-site',
            status: JobApplicationStatus::HIRED,
            createdAt: $this->timestamp(53, 11),
            updatedAt: $this->timestamp(23, 16),
        );

        $this->upsertInitialHistory($intanId, $stages['Screening CV'], 'Lamaran masuk melalui career site.', $users['recruiter_id'], $this->timestamp(53, 11));
        $this->upsertBatchHistory($intanId, $stages['Screening CV'], ActivityType::SCREENING, ActivityEntryResult::PASSED, JobApplicationStatus::IN_PROGRESS, $screeningGroupId, 'CV sangat sesuai untuk retail sales.', $users['recruiter_id'], $this->timestamp(50, 14));
        $this->upsertStageTransitionHistory($intanId, $stages['Screening CV'], $stages['Interview HR'], 'Masuk ke tahap Interview HR.', $users['recruiter_id'], $this->timestamp(50, 15));
        $this->upsertBatchHistory($intanId, $stages['Interview HR'], ActivityType::INTERVIEW_HRD, ActivityEntryResult::PASSED, JobApplicationStatus::IN_PROGRESS, $hrGroupId, 'Lolos Interview HR.', $users['recruiter_id'], $this->timestamp(44, 14));
        $this->upsertStageTransitionHistory($intanId, $stages['Interview HR'], $stages['Interview User'], 'Lolos Interview HR dan lanjut ke user interview.', $users['recruiter_id'], $this->timestamp(44, 15));
        $this->upsertBatchHistory($intanId, $stages['Interview User'], ActivityType::INTERVIEW_USER, ActivityEntryResult::PASSED, JobApplicationStatus::IN_PROGRESS, $userGroupId, 'User area menyetujui kandidat.', $users['recruiter_id'], $this->timestamp(37, 13));
        $this->upsertStageTransitionHistory($intanId, $stages['Interview User'], $stages['Offering'], 'Masuk tahap offering.', $users['recruiter_id'], $this->timestamp(37, 14));
        $this->upsertBatchHistory($intanId, $stages['Offering'], ActivityType::OFFERING, ActivityEntryResult::PASSED, JobApplicationStatus::IN_PROGRESS, $offeringGroupId, 'Offering disetujui kandidat.', $users['recruiter_id'], $this->timestamp(29, 13));
        $this->upsertStageTransitionHistory($intanId, $stages['Offering'], $stages['Hired'], 'Offering selesai dan kandidat masuk tahap hired.', $users['recruiter_id'], $this->timestamp(29, 14));
        $this->upsertDecisionHistory($intanId, $stages['Hired'], $stages['Hired'], JobApplicationStatus::HIRED, 'Join date dikonfirmasi untuk kandidat sales.', $users['recruiter_id'], $this->timestamp(23, 16));
    }

    /**
     * @param  Collection<string, int>  $companies
     * @param  array{approver_id: int, recruiter_id: int}  $users
     * @param  array<string, int>  $stages
     */
    private function seedWarehouseSupervisorScenario(Collection $companies, array $users, array $stages): void
    {
        $requestId = $this->upsertRequest(
            statusResponseId: '0f8430f1-34c3-46e6-9b4e-000000000003',
            companyId: (int) $companies->get('operations'),
            divisionId: (int) $this->divisionIds->get('operations'),
            emailAddress: 'warehouse-request@'.self::SAMPLE_EMAIL_DOMAIN,
            namaPengaju: 'BAGUS SAPUTRA',
            posisiPengaju: 'OPERATIONS MANAGER',
            tanggalPengajuan: $this->date(42),
            posisiDibutuhkan: 'Warehouse Supervisor',
            lokasiPenempatan: 'Bandung',
            statusKebutuhan: StatusKebutuhan::NEW_HIRING,
            divisi: 'Operations',
            levelPekerjaan: 'Leader',
            namaKaryawanReplacement: null,
            jumlahKaryawanDibutuhkan: 1,
            estimasiTanggalJoin: $this->today->addDays(10),
            requirementsKualifikasi: 'Pengalaman warehouse, leadership shift, dan kontrol stok.',
            jobDescription: 'Memimpin tim gudang dan memastikan inbound outbound berjalan tepat waktu.',
            keterangan: 'Pembukaan area gudang Bandung.',
            status: RequestManPowerStatus::APPROVED,
            approvedBy: $users['approver_id'],
            createdAt: $this->timestamp(42, 9),
            updatedAt: $this->timestamp(40, 11),
        );

        $operationsApprovers = $this->approvalStepApprovers['operations'];

        $this->upsertApproval(
            requestId: $requestId,
            approverId: $operationsApprovers['first'],
            stepOrder: 1,
            status: RequestManPowerApprovalStatus::APPROVED,
            actionToken: '9c100000-0000-4000-8000-000000000005',
            actionExpiresAt: $this->timestamp(37, 23, 59),
            notifiedAt: $this->timestamp(42, 10),
            actedAt: $this->timestamp(41, 13),
            notes: 'Approval HRBP Operations selesai.',
            actedByUserId: $users['approver_id'],
            createdAt: $this->timestamp(42, 9),
            updatedAt: $this->timestamp(41, 13),
        );
        $this->upsertApproval(
            requestId: $requestId,
            approverId: $operationsApprovers['second'],
            stepOrder: 2,
            status: RequestManPowerApprovalStatus::APPROVED,
            actionToken: '9c100000-0000-4000-8000-000000000006',
            actionExpiresAt: $this->timestamp(35, 23, 59),
            notifiedAt: $this->timestamp(41, 14),
            actedAt: $this->timestamp(40, 11),
            notes: 'Approval final Operations diterbitkan.',
            actedByUserId: $users['approver_id'],
            createdAt: $this->timestamp(41, 14),
            updatedAt: $this->timestamp(40, 11),
        );

        $postingId = $this->upsertPosting(
            requestId: $requestId,
            slug: self::SAMPLE_POSTING_SLUG_PREFIX.'warehouse-supervisor-bandung',
            pipelineId: $this->resolvePipelineIdFromStage($stages['Screening CV']),
            title: 'Warehouse Supervisor Bandung',
            description: 'Posisi untuk memimpin operasional gudang dan menjaga akurasi stok harian.',
            requirements: 'Warehouse management, people leadership, stock opname, dan WMS.',
            location: 'Bandung',
            thumbnailPath: 'rekrutmen/job-postings/crosscheck/warehouse-supervisor-bandung.jpg',
            isPublished: true,
            closingDate: $this->today->addDays(14),
            createdAt: $this->timestamp(40, 12),
            updatedAt: $this->timestamp(3, 17),
        );

        $screeningGroupId = '7b200000-0000-4000-8000-000000000011';
        $hrGroupId = '7b200000-0000-4000-8000-000000000012';
        $userPendingGroupId = '7b200000-0000-4000-8000-000000000013';

        $jokoId = $this->upsertApplication(
            jobPostingId: $postingId,
            currentStageId: $stages['Screening CV'],
            fullName: 'JOKO SANTOSO',
            email: 'joko.santoso@'.self::SAMPLE_EMAIL_DOMAIN,
            gender: JobApplicationGender::Male,
            birthDate: '1992-05-16',
            maritalStatus: JobApplicationMaritalStatus::Married,
            addressKtp: 'JL. SOEKARNO HATTA NO. 20 BANDUNG',
            addressDomicile: 'JL. SOEKARNO HATTA NO. 20 BANDUNG',
            whatsappNumber: '628141414141',
            activePhone: '628141414141',
            emergencyContactName: 'LIA SANTOSO',
            emergencyContactRelation: 'ISTRI',
            emergencyContactPhone: '628151414141',
            photoPath: 'rekrutmen/photos/crosscheck/joko-santoso.jpg',
            resumePath: 'rekrutmen/cv/crosscheck/joko-santoso.pdf',
            source: 'walk-in',
            status: JobApplicationStatus::IN_PROGRESS,
            createdAt: $this->timestamp(34, 10),
            updatedAt: $this->timestamp(31, 15),
        );

        $this->upsertInitialHistory($jokoId, $stages['Screening CV'], 'Lamaran masuk melalui career site.', $users['recruiter_id'], $this->timestamp(34, 10));
        $this->upsertBatchHistory($jokoId, $stages['Screening CV'], ActivityType::SCREENING, ActivityEntryResult::PENDING, JobApplicationStatus::IN_PROGRESS, $screeningGroupId, 'Menunggu verifikasi pengalaman warehouse terakhir.', $users['recruiter_id'], $this->timestamp(31, 15));

        $kartikaId = $this->upsertApplication(
            jobPostingId: $postingId,
            currentStageId: $stages['Interview HR'],
            fullName: 'KARTIKA DEWI',
            email: 'kartika.dewi@'.self::SAMPLE_EMAIL_DOMAIN,
            gender: JobApplicationGender::Female,
            birthDate: '1994-12-12',
            maritalStatus: JobApplicationMaritalStatus::Single,
            addressKtp: 'JL. KOPO NO. 11 BANDUNG',
            addressDomicile: 'JL. KOPO NO. 11 BANDUNG',
            whatsappNumber: '628161616161',
            activePhone: '628161616161',
            emergencyContactName: 'LINA DEWI',
            emergencyContactRelation: 'IBU',
            emergencyContactPhone: '628171616161',
            photoPath: 'rekrutmen/photos/crosscheck/kartika-dewi.jpg',
            resumePath: 'rekrutmen/cv/crosscheck/kartika-dewi.pdf',
            source: 'jobstreet',
            status: JobApplicationStatus::REJECTED,
            createdAt: $this->timestamp(33, 10),
            updatedAt: $this->timestamp(24, 16),
        );

        $this->upsertInitialHistory($kartikaId, $stages['Screening CV'], 'Lamaran masuk melalui career site.', $users['recruiter_id'], $this->timestamp(33, 10));
        $this->upsertBatchHistory($kartikaId, $stages['Screening CV'], ActivityType::SCREENING, ActivityEntryResult::PASSED, JobApplicationStatus::IN_PROGRESS, $screeningGroupId, 'Lolos screening warehouse.', $users['recruiter_id'], $this->timestamp(31, 15));
        $this->upsertStageTransitionHistory($kartikaId, $stages['Screening CV'], $stages['Interview HR'], 'Masuk ke tahap Interview HR.', $users['recruiter_id'], $this->timestamp(31, 16));
        $this->upsertBatchHistory($kartikaId, $stages['Interview HR'], ActivityType::INTERVIEW_HRD, ActivityEntryResult::FAILED, JobApplicationStatus::IN_PROGRESS, $hrGroupId, 'Kesiapan kerja shift tidak sesuai kebutuhan.', $users['recruiter_id'], $this->timestamp(24, 15));
        $this->upsertDecisionHistory($kartikaId, $stages['Interview HR'], $stages['Interview HR'], JobApplicationStatus::REJECTED, 'Ditolak setelah interview HR gudang.', $users['recruiter_id'], $this->timestamp(24, 16));

        $lilisId = $this->upsertApplication(
            jobPostingId: $postingId,
            currentStageId: $stages['Interview User'],
            fullName: 'LILIS ANGGRAINI',
            email: 'lilis.anggraini@'.self::SAMPLE_EMAIL_DOMAIN,
            gender: JobApplicationGender::Female,
            birthDate: '1995-09-05',
            maritalStatus: JobApplicationMaritalStatus::Married,
            addressKtp: 'JL. CIMAHI NO. 2 BANDUNG',
            addressDomicile: 'JL. CIMAHI NO. 2 BANDUNG',
            whatsappNumber: '628181818181',
            activePhone: '628181818181',
            emergencyContactName: 'ARIF ANGGRAINI',
            emergencyContactRelation: 'SUAMI',
            emergencyContactPhone: '628191818181',
            photoPath: 'rekrutmen/photos/crosscheck/lilis-anggraini.jpg',
            resumePath: 'rekrutmen/cv/crosscheck/lilis-anggraini.pdf',
            source: 'linkedin',
            status: JobApplicationStatus::IN_PROGRESS,
            createdAt: $this->timestamp(32, 11),
            updatedAt: $this->timestamp(3, 16),
        );

        $this->upsertInitialHistory($lilisId, $stages['Screening CV'], 'Lamaran masuk melalui career site.', $users['recruiter_id'], $this->timestamp(32, 11));
        $this->upsertBatchHistory($lilisId, $stages['Screening CV'], ActivityType::SCREENING, ActivityEntryResult::PASSED, JobApplicationStatus::IN_PROGRESS, $screeningGroupId, 'Lolos screening supervisor gudang.', $users['recruiter_id'], $this->timestamp(31, 15));
        $this->upsertStageTransitionHistory($lilisId, $stages['Screening CV'], $stages['Interview HR'], 'Masuk ke tahap Interview HR.', $users['recruiter_id'], $this->timestamp(31, 16));
        $this->upsertBatchHistory($lilisId, $stages['Interview HR'], ActivityType::INTERVIEW_HRD, ActivityEntryResult::PASSED, JobApplicationStatus::IN_PROGRESS, $hrGroupId, 'Lolos Interview HR untuk posisi gudang.', $users['recruiter_id'], $this->timestamp(24, 15));
        $this->upsertStageTransitionHistory($lilisId, $stages['Interview HR'], $stages['Interview User'], 'Dijadwalkan user interview supervisor gudang.', $users['recruiter_id'], $this->timestamp(24, 16));
        $this->upsertBatchHistory($lilisId, $stages['Interview User'], ActivityType::INTERVIEW_USER, ActivityEntryResult::PENDING, JobApplicationStatus::IN_PROGRESS, $userPendingGroupId, 'Menunggu keputusan final kepala gudang.', $users['recruiter_id'], $this->timestamp(3, 16));

        $mayaId = $this->upsertApplication(
            jobPostingId: $postingId,
            currentStageId: $stages['Screening CV'],
            fullName: 'MAYA PUTRI',
            email: 'maya.putri@'.self::SAMPLE_EMAIL_DOMAIN,
            gender: JobApplicationGender::Female,
            birthDate: '2000-01-20',
            maritalStatus: JobApplicationMaritalStatus::Single,
            addressKtp: 'JL. CIBIRU NO. 9 BANDUNG',
            addressDomicile: 'JL. CIBIRU NO. 9 BANDUNG',
            whatsappNumber: '628131313131',
            activePhone: '628131313131',
            emergencyContactName: 'NUR PUTRI',
            emergencyContactRelation: 'IBU',
            emergencyContactPhone: '628141313131',
            photoPath: 'rekrutmen/photos/crosscheck/maya-putri.jpg',
            resumePath: 'rekrutmen/cv/crosscheck/maya-putri.pdf',
            source: 'career-site',
            status: JobApplicationStatus::IN_PROGRESS,
            createdAt: $this->timestamp(35, 9),
            updatedAt: $this->timestamp(18, 17),
            deletedAt: $this->timestamp(18, 17),
        );

        $this->upsertInitialHistory($mayaId, $stages['Screening CV'], 'Lamaran masuk melalui career site.', $users['recruiter_id'], $this->timestamp(35, 9));
    }

    /**
     * @param  Collection<string, int>  $companies
     * @param  array{approver_id: int, recruiter_id: int}  $users
     * @param  array<string, int>  $stages
     */
    private function seedInternalAuditorScenario(Collection $companies, array $users, array $stages): void
    {
        $requestId = $this->upsertRequest(
            statusResponseId: '0f8430f1-34c3-46e6-9b4e-000000000004',
            companyId: (int) $companies->get('audit'),
            divisionId: (int) $this->divisionIds->get('audit'),
            emailAddress: 'auditor-request@'.self::SAMPLE_EMAIL_DOMAIN,
            namaPengaju: 'DEVI LARASATI',
            posisiPengaju: 'HEAD OF INTERNAL AUDIT',
            tanggalPengajuan: $this->date(52),
            posisiDibutuhkan: 'Internal Auditor',
            lokasiPenempatan: 'Medan',
            statusKebutuhan: StatusKebutuhan::NEW_HIRING,
            divisi: 'Audit',
            levelPekerjaan: 'Manager',
            namaKaryawanReplacement: null,
            jumlahKaryawanDibutuhkan: 1,
            estimasiTanggalJoin: $this->today->addDays(30),
            requirementsKualifikasi: 'Audit internal, risk assessment, dan penyusunan temuan audit.',
            jobDescription: 'Membangun ritme audit internal untuk cabang dan area operasional.',
            keterangan: 'Belum dipublikasikan sambil menunggu final JD dari user.',
            status: RequestManPowerStatus::APPROVED,
            approvedBy: $users['approver_id'],
            createdAt: $this->timestamp(52, 10),
            updatedAt: $this->timestamp(50, 13),
        );

        $auditApprovers = $this->approvalStepApprovers['audit'];

        $this->upsertApproval(
            requestId: $requestId,
            approverId: $auditApprovers['first'],
            stepOrder: 1,
            status: RequestManPowerApprovalStatus::APPROVED,
            actionToken: '9c100000-0000-4000-8000-000000000007',
            actionExpiresAt: $this->timestamp(47, 23, 59),
            notifiedAt: $this->timestamp(52, 11),
            actedAt: $this->timestamp(51, 10),
            notes: 'Approval HRBP Audit selesai.',
            actedByUserId: $users['approver_id'],
            createdAt: $this->timestamp(52, 10),
            updatedAt: $this->timestamp(51, 10),
        );
        $this->upsertApproval(
            requestId: $requestId,
            approverId: $auditApprovers['second'],
            stepOrder: 2,
            status: RequestManPowerApprovalStatus::APPROVED,
            actionToken: '9c100000-0000-4000-8000-000000000008',
            actionExpiresAt: $this->timestamp(45, 23, 59),
            notifiedAt: $this->timestamp(51, 11),
            actedAt: $this->timestamp(50, 13),
            notes: 'Approval final Audit diterbitkan.',
            actedByUserId: $users['approver_id'],
            createdAt: $this->timestamp(51, 11),
            updatedAt: $this->timestamp(50, 13),
        );

        $this->upsertPosting(
            requestId: $requestId,
            slug: self::SAMPLE_POSTING_SLUG_PREFIX.'internal-auditor-medan',
            pipelineId: $this->resolvePipelineIdFromStage($stages['Screening CV']),
            title: 'Internal Auditor Medan',
            description: 'Posisi audit internal untuk area Medan dan sekitarnya.',
            requirements: 'Audit internal, risk control matrix, dan reporting audit.',
            location: 'Medan',
            thumbnailPath: 'rekrutmen/job-postings/crosscheck/internal-auditor-medan.jpg',
            isPublished: false,
            closingDate: $this->today->addDays(45),
            createdAt: $this->timestamp(50, 14),
            updatedAt: $this->timestamp(50, 14),
        );
    }

    /**
     * @param  Collection<string, int>  $companies
     * @param  array{approver_id: int, recruiter_id: int}  $users
     */
    private function seedPendingFinanceRequest(Collection $companies, array $users): void
    {
        $requestId = $this->upsertRequest(
            statusResponseId: '0f8430f1-34c3-46e6-9b4e-000000000005',
            companyId: (int) $companies->get('finance'),
            divisionId: (int) $this->divisionIds->get('finance'),
            emailAddress: 'finance-request@'.self::SAMPLE_EMAIL_DOMAIN,
            namaPengaju: 'MELATI PUTRI',
            posisiPengaju: 'FINANCE MANAGER',
            tanggalPengajuan: $this->date(6),
            posisiDibutuhkan: 'Finance Analyst',
            lokasiPenempatan: 'Jakarta',
            statusKebutuhan: StatusKebutuhan::NEW_HIRING,
            divisi: 'Finance',
            levelPekerjaan: 'Staff',
            namaKaryawanReplacement: null,
            jumlahKaryawanDibutuhkan: 1,
            estimasiTanggalJoin: $this->today->addDays(20),
            requirementsKualifikasi: 'Finance reporting, budgeting, dan analisa varians.',
            jobDescription: 'Menyusun analisa biaya dan membantu closing laporan bulanan.',
            keterangan: 'Masih menunggu approval direktur finance.',
            status: RequestManPowerStatus::PENDING,
            approvedBy: null,
            createdAt: $this->timestamp(6, 9),
            updatedAt: $this->timestamp(6, 9),
        );

        $financeApprovers = $this->approvalStepApprovers['finance'];

        $this->upsertApproval(
            requestId: $requestId,
            approverId: $financeApprovers['first'],
            stepOrder: 1,
            status: RequestManPowerApprovalStatus::PENDING,
            actionToken: '9c100000-0000-4000-8000-000000000009',
            actionExpiresAt: $this->today->addDays(7)->setTime(23, 59),
            notifiedAt: $this->timestamp(6, 10),
            actedAt: null,
            notes: null,
            actedByUserId: null,
            createdAt: $this->timestamp(6, 9),
            updatedAt: $this->timestamp(6, 10),
        );
        $this->upsertApproval(
            requestId: $requestId,
            approverId: $financeApprovers['second'],
            stepOrder: 2,
            status: RequestManPowerApprovalStatus::WAITING,
            actionToken: null,
            actionExpiresAt: null,
            notifiedAt: null,
            actedAt: null,
            notes: null,
            actedByUserId: null,
            createdAt: $this->timestamp(6, 9),
            updatedAt: $this->timestamp(6, 9),
        );
    }

    /**
     * @param  Collection<string, int>  $companies
     * @param  array{approver_id: int, recruiter_id: int}  $users
     */
    private function seedRejectedQaRequest(Collection $companies, array $users): void
    {
        $requestId = $this->upsertRequest(
            statusResponseId: '0f8430f1-34c3-46e6-9b4e-000000000006',
            companyId: (int) $companies->get('tech'),
            divisionId: (int) $this->divisionIds->get('tech'),
            emailAddress: 'qa-request@'.self::SAMPLE_EMAIL_DOMAIN,
            namaPengaju: 'HENDRA NUGRAHA',
            posisiPengaju: 'ENGINEERING MANAGER',
            tanggalPengajuan: $this->date(13),
            posisiDibutuhkan: 'QA Engineer',
            lokasiPenempatan: 'Yogyakarta',
            statusKebutuhan: StatusKebutuhan::REPLACEMENT,
            divisi: 'Technology',
            levelPekerjaan: 'Staff',
            namaKaryawanReplacement: 'ARDI PRATAMA',
            jumlahKaryawanDibutuhkan: 1,
            estimasiTanggalJoin: $this->today->addDays(25),
            requirementsKualifikasi: 'Manual testing, API testing, regression planning, dan documentation.',
            jobDescription: 'Menjaga kualitas rilis produk dan menjalankan regression test.',
            keterangan: 'Ditolak karena reorganisasi tim QA sedang berjalan.',
            status: RequestManPowerStatus::REJECTED,
            approvedBy: $users['approver_id'],
            createdAt: $this->timestamp(13, 10),
            updatedAt: $this->timestamp(11, 16),
        );

        $techApprovers = $this->approvalStepApprovers['tech'];

        $this->upsertApproval(
            requestId: $requestId,
            approverId: $techApprovers['first'],
            stepOrder: 1,
            status: RequestManPowerApprovalStatus::APPROVED,
            actionToken: '9c100000-0000-4000-8000-000000000010',
            actionExpiresAt: $this->timestamp(7, 23, 59),
            notifiedAt: $this->timestamp(13, 11),
            actedAt: $this->timestamp(12, 11),
            notes: 'Approval HRBP Technology selesai.',
            actedByUserId: $users['approver_id'],
            createdAt: $this->timestamp(13, 10),
            updatedAt: $this->timestamp(12, 11),
        );
        $this->upsertApproval(
            requestId: $requestId,
            approverId: $techApprovers['second'],
            stepOrder: 2,
            status: RequestManPowerApprovalStatus::REJECTED,
            actionToken: '9c100000-0000-4000-8000-000000000011',
            actionExpiresAt: $this->timestamp(4, 23, 59),
            notifiedAt: $this->timestamp(12, 12),
            actedAt: $this->timestamp(11, 16),
            notes: 'Ditolak karena reorganisasi tim QA sedang berjalan.',
            actedByUserId: $users['approver_id'],
            createdAt: $this->timestamp(12, 12),
            updatedAt: $this->timestamp(11, 16),
        );
    }

    private function upsertRequest(
        string $statusResponseId,
        int $companyId,
        ?int $divisionId,
        string $emailAddress,
        string $namaPengaju,
        string $posisiPengaju,
        CarbonImmutable $tanggalPengajuan,
        string $posisiDibutuhkan,
        string $lokasiPenempatan,
        StatusKebutuhan $statusKebutuhan,
        string $divisi,
        string $levelPekerjaan,
        ?string $namaKaryawanReplacement,
        int $jumlahKaryawanDibutuhkan,
        CarbonImmutable $estimasiTanggalJoin,
        string $requirementsKualifikasi,
        string $jobDescription,
        string $keterangan,
        RequestManPowerStatus $status,
        ?int $approvedBy,
        CarbonImmutable $createdAt,
        CarbonImmutable $updatedAt,
    ): int {
        DB::table('rekrutmen_request_man_powers')->updateOrInsert(
            ['status_response_id' => $statusResponseId],
            [
                'company_id'                 => $companyId,
                'division_id'                => $divisionId,
                'email_address'              => $emailAddress,
                'nama_pengaju'               => $namaPengaju,
                'posisi_pengaju'             => $posisiPengaju,
                'tanggal_pengajuan'          => $tanggalPengajuan->toDateString(),
                'posisi_dibutuhkan'          => $posisiDibutuhkan,
                'lokasi_penempatan'          => $lokasiPenempatan,
                'status_kebutuhan'           => $statusKebutuhan->value,
                'divisi'                     => $divisi,
                'level_pekerjaan'            => $levelPekerjaan,
                'nama_karyawan_replacement'  => $namaKaryawanReplacement,
                'jumlah_karyawan_dibutuhkan' => $jumlahKaryawanDibutuhkan,
                'estimasi_tanggal_join'      => $estimasiTanggalJoin->toDateString(),
                'requirements_kualifikasi'   => $requirementsKualifikasi,
                'job_description'            => $jobDescription,
                'keterangan'                 => $keterangan,
                'status'                     => $status->value,
                'approved_by'                => $approvedBy,
                'created_at'                 => $createdAt,
                'updated_at'                 => $updatedAt,
                'deleted_at'                 => null,
            ],
        );

        return (int) DB::table('rekrutmen_request_man_powers')
            ->where('status_response_id', $statusResponseId)
            ->value('id');
    }

    private function upsertApproval(
        int $requestId,
        int $approverId,
        int $stepOrder,
        RequestManPowerApprovalStatus $status,
        ?string $actionToken,
        ?CarbonImmutable $actionExpiresAt,
        ?CarbonImmutable $notifiedAt,
        ?CarbonImmutable $actedAt,
        ?string $notes,
        ?int $actedByUserId,
        CarbonImmutable $createdAt,
        CarbonImmutable $updatedAt,
    ): void {
        $approver = DB::table('rekrutmen_approvers')
            ->where('id', $approverId)
            ->first(['name', 'email', 'title']);

        if (! $approver) {
            throw new RuntimeException("Unable to resolve approver for approval step [{$stepOrder}].");
        }

        DB::table('rekrutmen_request_man_power_approvals')->updateOrInsert(
            [
                'request_man_power_id' => $requestId,
                'step_order'           => $stepOrder,
            ],
            [
                'approver_id'       => $approverId,
                'approver_name'     => $approver->name,
                'approver_email'    => $approver->email,
                'approver_title'    => $approver->title,
                'status'            => $status->value,
                'action_token'      => $actionToken,
                'action_expires_at' => $actionExpiresAt,
                'notified_at'       => $notifiedAt,
                'acted_at'          => $actedAt,
                'notes'             => $notes,
                'acted_by_user_id'  => $actedByUserId,
                'created_at'        => $createdAt,
                'updated_at'        => $updatedAt,
            ],
        );
    }

    private function upsertPosting(
        int $requestId,
        string $slug,
        int $pipelineId,
        string $title,
        string $description,
        string $requirements,
        string $location,
        string $thumbnailPath,
        bool $isPublished,
        CarbonImmutable $closingDate,
        CarbonImmutable $createdAt,
        CarbonImmutable $updatedAt,
    ): int {
        DB::table('rekrutmen_job_postings')->updateOrInsert(
            ['slug' => $slug],
            [
                'request_man_power_id'  => $requestId,
                'rekrutmen_pipeline_id' => $pipelineId,
                'title'                 => $title,
                'description'           => $description,
                'requirements'          => $requirements,
                'location'              => $location,
                'thumbnail_path'        => $thumbnailPath,
                'is_published'          => $isPublished,
                'closing_date'          => $closingDate->toDateString(),
                'created_at'            => $createdAt,
                'updated_at'            => $updatedAt,
                'deleted_at'            => null,
            ],
        );

        return (int) DB::table('rekrutmen_job_postings')
            ->where('slug', $slug)
            ->value('id');
    }

    private function upsertApplication(
        int $jobPostingId,
        int $currentStageId,
        string $fullName,
        string $email,
        JobApplicationGender $gender,
        string $birthDate,
        JobApplicationMaritalStatus $maritalStatus,
        string $addressKtp,
        string $addressDomicile,
        string $whatsappNumber,
        string $activePhone,
        string $emergencyContactName,
        string $emergencyContactRelation,
        string $emergencyContactPhone,
        string $photoPath,
        string $resumePath,
        string $source,
        JobApplicationStatus $status,
        CarbonImmutable $createdAt,
        CarbonImmutable $updatedAt,
        ?CarbonImmutable $deletedAt = null,
    ): int {
        $normalizedEmail = Str::lower(trim($email));

        DB::table('rekrutmen_job_applications')->updateOrInsert(
            [
                'job_posting_id' => $jobPostingId,
                'email'          => $normalizedEmail,
            ],
            [
                'current_stage_id'           => $currentStageId,
                'position'                   => $this->nextStagePosition($currentStageId),
                'full_name'                  => $fullName,
                'gender'                     => $gender->value,
                'birth_date'                 => $birthDate,
                'marital_status'             => $maritalStatus->value,
                'address_ktp'                => $addressKtp,
                'address_domicile'           => $addressDomicile,
                'whatsapp_number'            => $whatsappNumber,
                'active_phone'               => $activePhone,
                'emergency_contact_name'     => $emergencyContactName,
                'emergency_contact_relation' => $emergencyContactRelation,
                'emergency_contact_phone'    => $emergencyContactPhone,
                'active_email'               => $deletedAt ? null : $normalizedEmail,
                'photo_path'                 => $photoPath,
                'resume_path'                => $resumePath,
                'source'                     => $source,
                'status'                     => $status->value,
                'created_at'                 => $createdAt,
                'updated_at'                 => $updatedAt,
                'deleted_at'                 => $deletedAt,
            ],
        );

        return (int) DB::table('rekrutmen_job_applications')
            ->where('job_posting_id', $jobPostingId)
            ->where('email', $normalizedEmail)
            ->value('id');
    }

    private function upsertInitialHistory(
        int $applicationId,
        int $stageId,
        string $notes,
        int $performedBy,
        CarbonImmutable $createdAt,
    ): void {
        DB::table('rekrutmen_job_application_histories')->updateOrInsert(
            [
                'job_application_id' => $applicationId,
                'status'             => JobApplicationStatus::IN_PROGRESS->value,
                'from_stage_id'      => null,
                'to_stage_id'        => $stageId,
                'notes'              => $notes,
                'activity_group_id'  => null,
            ],
            [
                'activity_type'  => null,
                'activity_date'  => null,
                'result'         => null,
                'activity_title' => null,
                'performed_by'   => $performedBy,
                'created_at'     => $createdAt,
                'updated_at'     => $createdAt,
            ],
        );
    }

    private function upsertStageTransitionHistory(
        int $applicationId,
        int $fromStageId,
        int $toStageId,
        string $notes,
        int $performedBy,
        CarbonImmutable $createdAt,
    ): void {
        DB::table('rekrutmen_job_application_histories')->updateOrInsert(
            [
                'job_application_id' => $applicationId,
                'status'             => JobApplicationStatus::IN_PROGRESS->value,
                'from_stage_id'      => $fromStageId,
                'to_stage_id'        => $toStageId,
                'notes'              => $notes,
                'activity_group_id'  => null,
            ],
            [
                'activity_type'  => null,
                'activity_date'  => null,
                'result'         => null,
                'activity_title' => null,
                'performed_by'   => $performedBy,
                'created_at'     => $createdAt,
                'updated_at'     => $createdAt,
            ],
        );
    }

    private function upsertDecisionHistory(
        int $applicationId,
        int $fromStageId,
        int $toStageId,
        JobApplicationStatus $status,
        string $notes,
        int $performedBy,
        CarbonImmutable $createdAt,
    ): void {
        DB::table('rekrutmen_job_application_histories')->updateOrInsert(
            [
                'job_application_id' => $applicationId,
                'status'             => $status->value,
                'from_stage_id'      => $fromStageId,
                'to_stage_id'        => $toStageId,
                'notes'              => $notes,
                'activity_group_id'  => null,
            ],
            [
                'activity_type'  => null,
                'activity_date'  => null,
                'result'         => null,
                'activity_title' => null,
                'performed_by'   => $performedBy,
                'created_at'     => $createdAt,
                'updated_at'     => $createdAt,
            ],
        );
    }

    private function upsertBatchHistory(
        int $applicationId,
        int $stageId,
        ActivityType $activityType,
        ActivityEntryResult $result,
        JobApplicationStatus $status,
        string $groupId,
        string $notes,
        int $performedBy,
        CarbonImmutable $createdAt,
    ): void {
        $stageName = $this->stageNamesById[$stageId] ?? null;

        if (! is_string($stageName)) {
            throw new RuntimeException("Stage id [{$stageId}] is not mapped for crosscheck activity seeding.");
        }

        DB::table('rekrutmen_job_application_histories')->updateOrInsert(
            [
                'job_application_id' => $applicationId,
                'activity_group_id'  => $groupId,
            ],
            [
                'from_stage_id'  => $stageId,
                'to_stage_id'    => $stageId,
                'activity_type'  => $activityType->value,
                'activity_date'  => $createdAt->toDateString(),
                'result'         => $result->value,
                'activity_title' => JobApplication::generateBatchActivityTitle($stageName, $createdAt->toDateString()),
                'status'         => $status->value,
                'notes'          => $notes,
                'performed_by'   => $performedBy,
                'created_at'     => $createdAt,
                'updated_at'     => $createdAt,
            ],
        );
    }

    private function resolvePipelineIdFromStage(int $stageId): int
    {
        $pipelineId = DB::table('rekrutmen_stages')
            ->where('id', $stageId)
            ->value('rekrutmen_pipeline_id');

        if (! is_numeric($pipelineId)) {
            throw new RuntimeException("Unable to resolve pipeline for stage id [{$stageId}].");
        }

        return (int) $pipelineId;
    }

    private function nextStagePosition(int $stageId): string
    {
        $sequence = ($this->stagePositionCounters[$stageId] ?? 0) + 1;
        $this->stagePositionCounters[$stageId] = $sequence;

        return number_format($sequence * 1000, 10, '.', '');
    }

    private function date(int $daysAgo): CarbonImmutable
    {
        return $this->today->subDays($daysAgo);
    }

    private function timestamp(int $daysAgo, int $hour, int $minute = 0): CarbonImmutable
    {
        return $this->date($daysAgo)->setTime($hour, $minute);
    }
}
