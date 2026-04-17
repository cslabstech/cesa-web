<?php

namespace Cesa\Rekrutmen\Tests\Feature\Seeders;

use Cesa\Rekrutmen\Database\Seeders\RekrutmenCrosscheckSeeder;
use Cesa\Rekrutmen\Database\Seeders\RekrutmenDemoSeeder;
use Cesa\Rekrutmen\Enums\JobApplicationStatus;
use Cesa\Rekrutmen\Enums\RequestManPowerApprovalStatus;
use Cesa\Rekrutmen\Enums\RequestManPowerStatus;
use Cesa\Rekrutmen\Enums\StatusKebutuhan;
use Cesa\Rekrutmen\Tests\RekrutmenTestCase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RekrutmenDemoSeederTest extends RekrutmenTestCase
{
    public function test_demo_seeder_creates_three_month_crosscheck_dataset_with_complete_columns(): void
    {
        $this->seed(RekrutmenDemoSeeder::class);

        $this->assertSame(
            RekrutmenCrosscheckSeeder::REQUEST_COUNT,
            DB::table('rekrutmen_request_man_powers')
                ->where('email_address', 'like', '%@'.RekrutmenCrosscheckSeeder::SAMPLE_EMAIL_DOMAIN)
                ->count(),
        );

        $this->assertSame(
            RekrutmenCrosscheckSeeder::JOB_POSTING_COUNT,
            DB::table('rekrutmen_job_postings')
                ->where('slug', 'like', RekrutmenCrosscheckSeeder::SAMPLE_POSTING_SLUG_PREFIX.'%')
                ->count(),
        );

        $this->assertSame(
            RekrutmenCrosscheckSeeder::JOB_APPLICATION_COUNT,
            DB::table('rekrutmen_job_applications')
                ->where('email', 'like', '%@'.RekrutmenCrosscheckSeeder::SAMPLE_EMAIL_DOMAIN)
                ->count(),
        );

        $this->assertSame(
            RekrutmenCrosscheckSeeder::DIVISION_COUNT,
            DB::table('rekrutmen_request_man_powers')
                ->where('email_address', 'like', '%@'.RekrutmenCrosscheckSeeder::SAMPLE_EMAIL_DOMAIN)
                ->distinct()
                ->count('division_id'),
        );

        $this->assertSame(
            RekrutmenCrosscheckSeeder::APPROVER_COUNT,
            DB::table('rekrutmen_approvers')
                ->where('email', 'like', '%@'.RekrutmenCrosscheckSeeder::SAMPLE_EMAIL_DOMAIN)
                ->count(),
        );

        $this->assertSame(
            RekrutmenCrosscheckSeeder::APPROVAL_COUNT,
            DB::table('rekrutmen_request_man_power_approvals')
                ->whereIn('request_man_power_id', function ($query): void {
                    $query->select('id')
                        ->from('rekrutmen_request_man_powers')
                        ->where('email_address', 'like', '%@'.RekrutmenCrosscheckSeeder::SAMPLE_EMAIL_DOMAIN);
                })
                ->count(),
        );

        $this->assertSame(
            RekrutmenCrosscheckSeeder::DELETED_JOB_APPLICATION_COUNT,
            DB::table('rekrutmen_job_applications')
                ->where('email', 'like', '%@'.RekrutmenCrosscheckSeeder::SAMPLE_EMAIL_DOMAIN)
                ->whereNotNull('deleted_at')
                ->count(),
        );

        $this->assertSame(
            RekrutmenCrosscheckSeeder::BATCH_ACTIVITY_GROUP_COUNT,
            DB::table('rekrutmen_job_application_histories')
                ->whereNotNull('activity_group_id')
                ->distinct()
                ->count('activity_group_id'),
        );

        $this->assertSame(
            0,
            DB::table('rekrutmen_request_man_powers')
                ->where('email_address', 'like', '%@'.RekrutmenCrosscheckSeeder::SAMPLE_EMAIL_DOMAIN)
                ->where(function ($query): void {
                    $query->whereNull('company_id')
                        ->orWhereNull('division_id')
                        ->orWhereNull('status_response_id')
                        ->orWhereNull('tanggal_pengajuan')
                        ->orWhereNull('estimasi_tanggal_join')
                        ->orWhereNull('requirements_kualifikasi')
                        ->orWhereNull('job_description');
                })
                ->count(),
        );

        $this->assertSame(
            0,
            DB::table('rekrutmen_job_postings')
                ->where('slug', 'like', RekrutmenCrosscheckSeeder::SAMPLE_POSTING_SLUG_PREFIX.'%')
                ->where(function ($query): void {
                    $query->whereNull('request_man_power_id')
                        ->orWhereNull('rekrutmen_pipeline_id')
                        ->orWhereNull('description')
                        ->orWhereNull('requirements')
                        ->orWhereNull('thumbnail_path')
                        ->orWhereNull('closing_date');
                })
                ->count(),
        );

        $this->assertSame(
            0,
            DB::table('rekrutmen_job_applications')
                ->where('email', 'like', '%@'.RekrutmenCrosscheckSeeder::SAMPLE_EMAIL_DOMAIN)
                ->whereNull('deleted_at')
                ->where(function ($query): void {
                    $query->whereNull('current_stage_id')
                        ->orWhereNull('position')
                        ->orWhereNull('source')
                        ->orWhereNull('gender')
                        ->orWhereNull('birth_date')
                        ->orWhereNull('marital_status')
                        ->orWhereNull('address_ktp')
                        ->orWhereNull('address_domicile')
                        ->orWhereNull('whatsapp_number')
                        ->orWhereNull('active_phone')
                        ->orWhereNull('active_email')
                        ->orWhereNull('photo_path')
                        ->orWhereNull('resume_path');
                })
                ->count(),
        );

        $this->assertSame(
            0,
            DB::table('rekrutmen_job_application_histories')
                ->whereNotNull('activity_group_id')
                ->where(function ($query): void {
                    $query->whereNull('activity_type')
                        ->orWhereNull('activity_date')
                        ->orWhereNull('result')
                        ->orWhereNull('activity_title')
                        ->orWhereNull('performed_by');
                })
                ->count(),
        );

        $this->assertSame(
            0,
            DB::table('rekrutmen_request_man_power_approvals')
                ->whereIn('request_man_power_id', function ($query): void {
                    $query->select('id')
                        ->from('rekrutmen_request_man_powers')
                        ->where('email_address', 'like', '%@'.RekrutmenCrosscheckSeeder::SAMPLE_EMAIL_DOMAIN);
                })
                ->where(function ($query): void {
                    $query->whereNull('approver_name')
                        ->orWhereNull('approver_email')
                        ->orWhereNull('approver_title');
                })
                ->count(),
        );

        $this->assertSame(
            1,
            DB::table('rekrutmen_request_man_powers')
                ->where('email_address', 'sales-request@'.RekrutmenCrosscheckSeeder::SAMPLE_EMAIL_DOMAIN)
                ->where('status_kebutuhan', StatusKebutuhan::REPLACEMENT->value)
                ->whereNotNull('nama_karyawan_replacement')
                ->count(),
        );

        $this->assertSame(
            1,
            DB::table('rekrutmen_request_man_powers')
                ->where('email_address', 'finance-request@'.RekrutmenCrosscheckSeeder::SAMPLE_EMAIL_DOMAIN)
                ->where('status', RequestManPowerStatus::PENDING->value)
                ->count(),
        );

        $this->assertSame(
            1,
            DB::table('rekrutmen_request_man_powers')
                ->where('email_address', 'qa-request@'.RekrutmenCrosscheckSeeder::SAMPLE_EMAIL_DOMAIN)
                ->where('status', RequestManPowerStatus::REJECTED->value)
                ->count(),
        );

        $this->assertSame(
            1,
            DB::table('rekrutmen_job_postings')
                ->where('slug', RekrutmenCrosscheckSeeder::SAMPLE_POSTING_SLUG_PREFIX.'internal-auditor-medan')
                ->where('is_published', false)
                ->count(),
        );

        $this->assertSame(
            1,
            DB::table('rekrutmen_request_man_power_approvals as approvals')
                ->join('rekrutmen_request_man_powers as requests', 'requests.id', '=', 'approvals.request_man_power_id')
                ->where('requests.email_address', 'finance-request@'.RekrutmenCrosscheckSeeder::SAMPLE_EMAIL_DOMAIN)
                ->where('approvals.status', RequestManPowerApprovalStatus::PENDING->value)
                ->where('approvals.step_order', 1)
                ->whereNotNull('approvals.action_token')
                ->whereNotNull('approvals.notified_at')
                ->count(),
        );

        $statusCounts = DB::table('rekrutmen_job_applications')
            ->where('email', 'like', '%@'.RekrutmenCrosscheckSeeder::SAMPLE_EMAIL_DOMAIN)
            ->whereNull('deleted_at')
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $this->assertSame(2, (int) ($statusCounts[JobApplicationStatus::HIRED->value] ?? 0));
        $this->assertSame(4, (int) ($statusCounts[JobApplicationStatus::REJECTED->value] ?? 0));
        $this->assertSame(1, (int) ($statusCounts[JobApplicationStatus::WITHDRAWN->value] ?? 0));
        $this->assertSame(5, (int) ($statusCounts[JobApplicationStatus::IN_PROGRESS->value] ?? 0));

        $approvalStatusCounts = DB::table('rekrutmen_request_man_power_approvals')
            ->whereIn('request_man_power_id', function ($query): void {
                $query->select('id')
                    ->from('rekrutmen_request_man_powers')
                    ->where('email_address', 'like', '%@'.RekrutmenCrosscheckSeeder::SAMPLE_EMAIL_DOMAIN);
            })
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $this->assertSame(9, (int) ($approvalStatusCounts[RequestManPowerApprovalStatus::APPROVED->value] ?? 0));
        $this->assertSame(1, (int) ($approvalStatusCounts[RequestManPowerApprovalStatus::PENDING->value] ?? 0));
        $this->assertSame(1, (int) ($approvalStatusCounts[RequestManPowerApprovalStatus::WAITING->value] ?? 0));
        $this->assertSame(1, (int) ($approvalStatusCounts[RequestManPowerApprovalStatus::REJECTED->value] ?? 0));

        $rangeStart = now()->subMonthsNoOverflow(3)->startOfDay();
        $rangeEnd = now()->endOfDay();

        $minSubmissionDate = Carbon::parse((string) DB::table('rekrutmen_request_man_powers')
            ->where('email_address', 'like', '%@'.RekrutmenCrosscheckSeeder::SAMPLE_EMAIL_DOMAIN)
            ->min('tanggal_pengajuan'));

        $maxActivityDate = Carbon::parse((string) DB::table('rekrutmen_job_application_histories')
            ->whereNotNull('activity_date')
            ->max('activity_date'));

        $this->assertTrue($minSubmissionDate->betweenIncluded($rangeStart, $rangeEnd));
        $this->assertTrue($maxActivityDate->betweenIncluded($rangeStart, $rangeEnd));
    }

    public function test_demo_seeder_can_be_rerun_without_changing_dataset_size(): void
    {
        $this->seed(RekrutmenDemoSeeder::class);

        $firstCounts = $this->collectDatasetCounts();

        $this->seed(RekrutmenDemoSeeder::class);

        $this->assertSame($firstCounts, $this->collectDatasetCounts());
    }

    /**
     * @return array<string, int>
     */
    private function collectDatasetCounts(): array
    {
        return [
            'requests' => DB::table('rekrutmen_request_man_powers')
                ->where('email_address', 'like', '%@'.RekrutmenCrosscheckSeeder::SAMPLE_EMAIL_DOMAIN)
                ->count(),
            'postings' => DB::table('rekrutmen_job_postings')
                ->where('slug', 'like', RekrutmenCrosscheckSeeder::SAMPLE_POSTING_SLUG_PREFIX.'%')
                ->count(),
            'applications' => DB::table('rekrutmen_job_applications')
                ->where('email', 'like', '%@'.RekrutmenCrosscheckSeeder::SAMPLE_EMAIL_DOMAIN)
                ->count(),
            'histories' => DB::table('rekrutmen_job_application_histories')
                ->whereIn('job_application_id', function ($query): void {
                    $query->select('id')
                        ->from('rekrutmen_job_applications')
                        ->where('email', 'like', '%@'.RekrutmenCrosscheckSeeder::SAMPLE_EMAIL_DOMAIN);
                })
                ->count(),
            'approvers' => DB::table('rekrutmen_approvers')
                ->where('email', 'like', '%@'.RekrutmenCrosscheckSeeder::SAMPLE_EMAIL_DOMAIN)
                ->count(),
            'approvals' => DB::table('rekrutmen_request_man_power_approvals')
                ->whereIn('request_man_power_id', function ($query): void {
                    $query->select('id')
                        ->from('rekrutmen_request_man_powers')
                        ->where('email_address', 'like', '%@'.RekrutmenCrosscheckSeeder::SAMPLE_EMAIL_DOMAIN);
                })
                ->count(),
            'batch_groups' => DB::table('rekrutmen_job_application_histories')
                ->whereNotNull('activity_group_id')
                ->distinct()
                ->count('activity_group_id'),
        ];
    }
}
