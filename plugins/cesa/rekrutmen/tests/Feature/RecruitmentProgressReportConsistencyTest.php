<?php

namespace Cesa\Rekrutmen\Tests\Feature;

use Cesa\Rekrutmen\Enums\JobApplicationGender;
use Cesa\Rekrutmen\Enums\JobApplicationMaritalStatus;
use Cesa\Rekrutmen\Enums\JobApplicationStatus;
use Cesa\Rekrutmen\Enums\RequestManPowerStatus;
use Cesa\Rekrutmen\Enums\StatusKebutuhan;
use Cesa\Rekrutmen\Livewire\RecruitmentProgressReport;
use Cesa\Rekrutmen\Models\JobApplication;
use Cesa\Rekrutmen\Models\JobPosting;
use Cesa\Rekrutmen\Models\RekrutmenPipeline;
use Cesa\Rekrutmen\Models\RekrutmenStage;
use Cesa\Rekrutmen\Models\RequestManPower;
use Cesa\Rekrutmen\Services\RecruitmentProgressReportExport;
use Cesa\Rekrutmen\Tests\RekrutmenTestCase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Spatie\Permission\Models\Permission;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

class RecruitmentProgressReportConsistencyTest extends RekrutmenTestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'is_active' => true,
        ]);

        Permission::findOrCreate('view_any_cesa::rekrutmen::models::job::application::history', 'web');
        $this->user->givePermissionTo('view_any_cesa::rekrutmen::models::job::application::history');

        $this->actingAs($this->user);
    }

    public function test_report_endpoints_use_consistent_pipeline_based_counts(): void
    {
        [$jobPosting, $firstStage] = $this->createFixtureForDivision('IT', 'backend-engineer-report');
        [$otherPosting, $otherStage] = $this->createFixtureForDivision('Finance', 'finance-analyst-report');

        $passedCandidate = $this->makeJobApplication($jobPosting, $firstStage, 'passed-report@example.com', 'Passed Candidate');
        $failedCandidate = $this->makeJobApplication($jobPosting, $firstStage, 'failed-report@example.com', 'Failed Candidate');
        $deletedCandidate = $this->makeJobApplication($jobPosting, $firstStage, 'deleted-report@example.com', 'Deleted Candidate');
        $otherCandidate = $this->makeJobApplication($otherPosting, $otherStage, 'other-report@example.com', 'Other Candidate');
        $deletedCandidate->delete();

        JobApplication::recordBatchActivity(
            $jobPosting->id,
            $firstStage->id,
            '2026-04-07',
            [
                ['job_application_id' => $passedCandidate->id, 'result' => 'passed', 'notes' => 'Proceed'],
                ['job_application_id' => $failedCandidate->id, 'result' => 'failed', 'notes' => 'Reject'],
            ],
            $this->user->id,
        );

        $otherCandidate->update([
            'status' => JobApplicationStatus::IN_PROGRESS,
        ]);

        $reportResponse = $this->getJson('/api/recruitment/progress-report?job_posting_id='.$jobPosting->id.'&date_from=2026-04-01&date_to=2026-04-30');
        $reportResponse
            ->assertOk()
            ->assertJsonPath('summary.total_positions_active', 1)
            ->assertJsonPath('summary.total_candidates_in_process', 1)
            ->assertJsonPath('summary.total_activities_this_period', 1)
            ->assertJsonPath('summary.total_hired_this_period', 0)
            ->assertJsonPath('summary.total_rejected_this_period', 1)
            ->assertJsonPath('activities.0.counts.total', 2)
            ->assertJsonPath('activities.0.counts.passed', 1)
            ->assertJsonPath('activities.0.counts.failed', 1)
            ->assertJsonPath('positions.0.total_applicants', 2)
            ->assertJsonPath('positions.0.in_progress', 1)
            ->assertJsonPath('positions.0.rejected', 1);

        $timelineResponse = $this->getJson('/api/recruitment/progress-report/timeline?job_posting_id='.$jobPosting->id.'&date_from=2026-04-01&date_to=2026-04-30');
        $timelineResponse
            ->assertOk()
            ->assertJsonPath('timeline.0.activity.counts.total', 2)
            ->assertJsonPath('timeline.0.activity.counts.passed', 1)
            ->assertJsonPath('timeline.0.activity.counts.failed', 1);

        $overviewResponse = $this->getJson('/api/recruitment/progress-report/overview?job_posting_id='.$jobPosting->id.'&date_from=2026-04-01&date_to=2026-04-30');
        $overviewResponse
            ->assertOk()
            ->assertJsonPath('overview.0.total_applicants', 2)
            ->assertJsonPath('overview.0.in_progress', 1)
            ->assertJsonPath('overview.0.rejected', 1)
            ->assertJsonPath('overview.0.latest_activity.summary', '2 Orang 1 Lolos 1 Tidak Lolos');
    }

    public function test_timeline_endpoint_keeps_multiple_activities_on_the_same_day(): void
    {
        [$firstPosting, $firstStage] = $this->createFixtureForDivision('IT', 'timeline-first');
        [$secondPosting, $secondStage] = $this->createFixtureForDivision('IT', 'timeline-second');

        $firstCandidate = $this->makeJobApplication($firstPosting, $firstStage, 'timeline-first@example.com', 'Timeline First');
        $secondCandidate = $this->makeJobApplication($secondPosting, $secondStage, 'timeline-second@example.com', 'Timeline Second');

        JobApplication::recordBatchActivity(
            $firstPosting->id,
            $firstStage->id,
            '2026-04-07',
            [
                ['job_application_id' => $firstCandidate->id, 'result' => 'pending', 'notes' => 'Pending'],
            ],
            $this->user->id,
        );

        JobApplication::recordBatchActivity(
            $secondPosting->id,
            $secondStage->id,
            '2026-04-07',
            [
                ['job_application_id' => $secondCandidate->id, 'result' => 'pending', 'notes' => 'Pending'],
            ],
            $this->user->id,
        );

        $timelineResponse = $this->getJson('/api/recruitment/progress-report/timeline?date_from=2026-04-01&date_to=2026-04-30');

        $timelineResponse
            ->assertOk()
            ->assertJsonPath('timeline.0.count', 2);

        $this->assertCount(2, $timelineResponse->json('timeline.0.activities'));
    }

    public function test_livewire_report_renders_same_activity_summary_without_querying_extra_state(): void
    {
        [$jobPosting, $firstStage] = $this->createFixtureForDivision('IT', 'qa-engineer-report');

        $firstCandidate = $this->makeJobApplication($jobPosting, $firstStage, 'livewire-1@example.com', 'Livewire One');
        $secondCandidate = $this->makeJobApplication($jobPosting, $firstStage, 'livewire-2@example.com', 'Livewire Two');

        JobApplication::recordBatchActivity(
            $jobPosting->id,
            $firstStage->id,
            '2026-04-07',
            [
                ['job_application_id' => $firstCandidate->id, 'result' => 'pending', 'notes' => 'Pending'],
                ['job_application_id' => $secondCandidate->id, 'result' => 'passed', 'notes' => 'Proceed'],
            ],
            $this->user->id,
        );

        $activityTitle = JobApplication::generateBatchActivityTitle($firstStage->name, '2026-04-07');

        $component = Livewire::test(RecruitmentProgressReport::class)
            ->set('jobPostingId', $jobPosting->id)
            ->set('dateFrom', '2026-04-01')
            ->set('dateTo', '2026-04-30')
            ->assertSee($jobPosting->title)
            ->assertSee($activityTitle)
            ->assertSee('2 Orang 1 Lolos 1 Menunggu');

        $component
            ->set('activeTab', 'per-position')
            ->assertSee('PT IT Core');
    }

    public function test_livewire_export_downloads_a_monthly_mpp_workbook_that_is_easy_to_read(): void
    {
        Excel::fake();
        Carbon::setTestNow('2026-04-07 10:00:00');

        try {
            [$aprilPosting, $aprilStage] = $this->createFixtureForDivision('IT', 'backend-engineer-export');
            [$mayPosting, $mayStage] = $this->createFixtureForDivision('Finance', 'finance-analyst-export');

            $aprilPosting->requestManPower()->update([
                'tanggal_pengajuan'          => '2026-04-02',
                'estimasi_tanggal_join'      => '2026-04-15',
                'jumlah_karyawan_dibutuhkan' => 2,
            ]);

            $mayPosting->requestManPower()->update([
                'tanggal_pengajuan'          => '2026-04-05',
                'estimasi_tanggal_join'      => '2026-05-15',
                'jumlah_karyawan_dibutuhkan' => 1,
            ]);

            $aprilHiredCandidate = $this->makeJobApplication($aprilPosting, $aprilStage, 'april-hired@example.com', 'April Hired');
            $aprilPendingCandidate = $this->makeJobApplication($aprilPosting, $aprilStage, 'april-pending@example.com', 'April Pending');
            $mayCandidate = $this->makeJobApplication($mayPosting, $mayStage, 'may-pipeline@example.com', 'May Pipeline');

            JobApplication::recordBatchActivity(
                $aprilPosting->id,
                $aprilStage->id,
                '2026-04-07',
                [
                    ['job_application_id' => $aprilHiredCandidate->id, 'result' => 'passed', 'notes' => 'Proceed to offer'],
                    ['job_application_id' => $aprilPendingCandidate->id, 'result' => 'pending', 'notes' => 'Waiting for user'],
                ],
                $this->user->id,
            );

            $aprilHiredCandidate->markAsHired('Accepted', $this->user->id);
            $aprilHiredCandidate->refresh();
            $mayCandidate->refresh();

            Livewire::test(RecruitmentProgressReport::class)
                ->set('dateFrom', '2026-04-01')
                ->set('dateTo', '2026-04-30')
                ->call('exportExcel')
                ->assertFileDownloaded();

            Excel::assertDownloaded('recruitment-progress-mpp-20260401-to-20260430.xlsx', function (RecruitmentProgressReportExport $export) use ($aprilPosting, $mayPosting): bool {
                $sheets = $export->sheets();

                $this->assertCount(4, $sheets);
                $this->assertSame('Overview MPP', $sheets[0]->title());
                $this->assertSame('Ringkasan Bulanan', $sheets[1]->title());
                $this->assertSame('Detail Posisi', $sheets[2]->title());
                $this->assertWorkbookSheetMatchesOverviewMppLayout($sheets[1], 'H');
                $this->assertWorkbookSheetMatchesOverviewMppLayout($sheets[2], 'N');
                $this->assertWorkbookSheetMatchesOverviewMppLayout($sheets[3], 'L');

                $overviewRows = collect($sheets[0]->array())->values();
                $summaryRows = collect($sheets[1]->array())->slice(5)->values();
                $detailRows = collect($sheets[2]->array())->slice(5)->values();

                $this->assertTrue($overviewRows->contains(fn (array $row): bool => ($row[0] ?? null) === 'MPP BULAN APRIL 2026'));
                $this->assertTrue($overviewRows->contains(fn (array $row): bool => ($row[0] ?? null) === 'KARYAWAN JOIN BULAN APRIL 2026'));

                $this->assertTrue($overviewRows->contains(fn (array $row): bool => ($row[0] ?? null) === 'PT FINANCE CORE'
                    && ($row[4] ?? null) === 1
                    && ($row[5] ?? null) === mb_strtoupper($mayPosting->title)));

                $this->assertTrue($overviewRows->contains(fn (array $row): bool => ($row[0] ?? null) === 'PT IT CORE'
                    && ($row[4] ?? null) === 1
                    && ($row[5] ?? null) === mb_strtoupper($aprilPosting->title)));

                $this->assertTrue($overviewRows->contains(fn (array $row): bool => ($row[0] ?? null) === 'PT IT CORE'
                    && ($row[2] ?? null) === '07 Apr 2026'
                    && ($row[4] ?? null) === 1
                    && ($row[5] ?? null) === mb_strtoupper($aprilPosting->title)));

                $this->assertTrue($overviewRows->contains(fn (array $row): bool => ($row[0] ?? null) === 'TOTAL' && ($row[4] ?? null) === 2));
                $this->assertTrue($overviewRows->contains(fn (array $row): bool => ($row[0] ?? null) === 'TOTAL' && ($row[4] ?? null) === 1));

                $this->assertTrue($summaryRows->contains(fn (array $row): bool => $row[0] === 'APRIL 2026'
                    && $row[3] === 2
                    && $row[4] === 1));

                $this->assertTrue($detailRows->contains(fn (array $row): bool => in_array('PT IT Core', $row, true)
                    && in_array($aprilPosting->title, $row, true)
                    && in_array('Pipeline cukup, lanjut monitor', $row, true)));

                $this->assertTrue($detailRows->contains(fn (array $row): bool => in_array('PT Finance Core', $row, true)
                    && in_array($mayPosting->title, $row, true)
                    && in_array('Pipeline cukup, lanjut monitor', $row, true)));

                return true;
            });
        } finally {
            Carbon::setTestNow();
        }
    }

    /**
     * @return array{0: JobPosting, 1: RekrutmenStage}
     */
    private function createFixtureForDivision(string $division, string $slug): array
    {
        $company = Company::query()->create([
            'name' => "PT {$division} Core",
        ]);

        $requestManPower = RequestManPower::query()->create([
            'company_id'                 => $company->id,
            'email_address'              => "{$slug}@example.com",
            'nama_pengaju'               => "Requester {$division}",
            'posisi_pengaju'             => 'Manager',
            'tanggal_pengajuan'          => now()->toDateString(),
            'posisi_dibutuhkan'          => "Role {$division}",
            'lokasi_penempatan'          => 'Jakarta',
            'status_kebutuhan'           => StatusKebutuhan::NEW_HIRING,
            'divisi'                     => $division,
            'level_pekerjaan'            => 'Staff',
            'jumlah_karyawan_dibutuhkan' => 2,
            'estimasi_tanggal_join'      => now()->addMonth()->toDateString(),
            'requirements_kualifikasi'   => 'Requirement',
            'job_description'            => 'Job description',
            'keterangan'                 => 'Testing',
            'status'                     => RequestManPowerStatus::PENDING,
        ]);

        $pipeline = RekrutmenPipeline::query()->create([
            'name' => "Pipeline {$division}",
        ]);

        $firstStage = RekrutmenStage::query()->create([
            'rekrutmen_pipeline_id' => $pipeline->id,
            'name'                  => 'CV Screening',
            'order_column'          => 1,
        ]);

        RekrutmenStage::query()->create([
            'rekrutmen_pipeline_id' => $pipeline->id,
            'name'                  => 'Interview User',
            'order_column'          => 2,
        ]);

        $jobPosting = JobPosting::query()->create([
            'request_man_power_id'  => $requestManPower->id,
            'rekrutmen_pipeline_id' => $pipeline->id,
            'title'                 => str($slug)->replace('-', ' ')->title()->toString(),
            'slug'                  => $slug,
            'description'           => 'Build APIs',
            'requirements'          => 'Laravel',
            'location'              => 'Jakarta',
            'is_published'          => true,
        ]);

        return [$jobPosting, $firstStage];
    }

    private function makeJobApplication(JobPosting $jobPosting, RekrutmenStage $stage, string $email, string $fullName): JobApplication
    {
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
            'whatsapp_number'            => '081234567890',
            'active_phone'               => '081234567891',
            'emergency_contact_name'     => 'Bunga',
            'emergency_contact_relation' => 'Saudara',
            'emergency_contact_phone'    => '081234567892',
            'status'                     => JobApplicationStatus::IN_PROGRESS,
        ]);
    }

    private function assertWorkbookSheetMatchesOverviewMppLayout(object $sheetExport, string $lastColumn): void
    {
        $spreadsheet = new Spreadsheet;
        $worksheet = $spreadsheet->getActiveSheet();
        $worksheet->fromArray($sheetExport->array(), null, 'A1');
        $sheetExport->styles($worksheet);

        $mergedCells = array_keys($worksheet->getMergeCells());

        $this->assertContains("A1:{$lastColumn}1", $mergedCells);
        $this->assertContains("A2:{$lastColumn}2", $mergedCells);
        $this->assertContains("A3:{$lastColumn}3", $mergedCells);
        $this->assertContains("A4:{$lastColumn}4", $mergedCells);
        $this->assertSame('FFF59D', $worksheet->getStyle('A5')->getFill()->getStartColor()->getRGB());
        $this->assertSame('FEF3C7', $worksheet->getStyle('A1')->getFill()->getStartColor()->getRGB());
        $this->assertSame('center', $worksheet->getStyle('A1')->getAlignment()->getHorizontal());
        $this->assertSame('A6', $worksheet->getFreezePane());
    }
}
