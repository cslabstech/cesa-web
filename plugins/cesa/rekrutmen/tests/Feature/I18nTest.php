<?php

namespace Cesa\Rekrutmen\Tests\Feature;

use Cesa\Rekrutmen\Enums\JobApplicationStatus;
use Cesa\Rekrutmen\Enums\RequestManPowerStatus;
use Cesa\Rekrutmen\Enums\StatusKebutuhan;
use Cesa\Rekrutmen\Filament\Clusters\Configurations;
use Cesa\Rekrutmen\Filament\Resources\ActivityLogResource;
use Cesa\Rekrutmen\Filament\Resources\ApproverResource;
use Cesa\Rekrutmen\Filament\Resources\DivisionResource;
use Cesa\Rekrutmen\Filament\Resources\JobPostingResource;
use Cesa\Rekrutmen\Filament\Resources\RekrutmenPipelineResource;
use Cesa\Rekrutmen\Filament\Resources\RequestManPowerResource;
use Cesa\Rekrutmen\Http\Controllers\Api\CareerController;
use Cesa\Rekrutmen\Models\JobPosting;
use Cesa\Rekrutmen\Models\RekrutmenPipeline;
use Cesa\Rekrutmen\Models\RekrutmenStage;
use Cesa\Rekrutmen\Models\RequestManPower;
use Cesa\Rekrutmen\Tests\RekrutmenTestCase;

class I18nTest extends RekrutmenTestCase
{
    public function test_navigation_and_enum_labels_are_localized(): void
    {
        foreach ($this->localeExpectations() as $locale => $expected) {
            app()->setLocale($locale);

            $this->assertSame(__('admin.navigation.rekrutmen'), RequestManPowerResource::getNavigationGroup());
            $this->assertSame($expected['navigation_label'], RequestManPowerResource::getNavigationLabel());
            $this->assertSame($expected['configurations_label'], Configurations::getNavigationLabel());
            $this->assertSame($expected['division_navigation_label'], DivisionResource::getNavigationLabel());
            $this->assertSame($expected['pipeline_navigation_label'], RekrutmenPipelineResource::getNavigationLabel());
            $this->assertSame($expected['approver_navigation_label'], ApproverResource::getNavigationLabel());
            $this->assertSame($expected['job_posting_navigation_label'], JobPostingResource::getNavigationLabel());
            $this->assertSame($expected['activity_log_navigation_label'], ActivityLogResource::getNavigationLabel());
            $this->assertSame($expected['status_kebutuhan'], StatusKebutuhan::NEW_HIRING->getLabel());
            $this->assertSame($expected['request_status'], RequestManPowerStatus::PENDING->getLabel());
            $this->assertSame($expected['application_status'], JobApplicationStatus::IN_PROGRESS->getLabel());
            $this->assertSame($expected['job_level'], RequestManPower::getTranslatedLevelPekerjaanOptions()['Staff']);
            $this->assertSame($expected['public_progress_heading'], __('rekrutmen::livewire/public-request-man-power-progress-page.heading'));
            $this->assertSame($expected['mail_progress_action'], __('rekrutmen::mail/request-man-power-submitted.view_progress'));
            $this->assertSame(
                $expected['flowforge_empty_column'],
                __('flowforge::flowforge.no_cards_in_column', ['cardLabel' => 'data'])
            );
        }
    }

    public function test_job_detail_api_returns_localized_messages_and_application_form_labels(): void
    {
        $pipeline = RekrutmenPipeline::query()->create([
            'name' => 'Localized Job Detail Pipeline',
        ]);

        RekrutmenStage::query()->create([
            'rekrutmen_pipeline_id' => $pipeline->id,
            'name'                  => 'CV Screening',
            'order_column'          => 1,
        ]);

        $jobPosting = JobPosting::query()->create([
            'request_man_power_id'   => null,
            'rekrutmen_pipeline_id'  => $pipeline->id,
            'title'                  => 'Backend Developer',
            'slug'                   => 'backend-developer',
            'description'            => 'Build APIs and internal tools.',
            'requirements'           => 'Laravel and SQL',
            'location'               => 'Jakarta',
            'is_published'           => true,
            'closing_date'           => null,
        ]);

        foreach ($this->localeExpectations() as $locale => $expected) {
            app()->setLocale($locale);

            $response = app(CareerController::class)->show($jobPosting->slug);
            $payload = $response->getData(true);

            $this->assertSame(200, $response->getStatusCode());
            $this->assertSame($expected['job_detail_message'], $payload['message']);
            $this->assertSame($expected['application_form_full_name'], $payload['data']['application_form'][0]['label']);
            $this->assertSame($expected['application_form_gender'], $payload['data']['application_form'][2]['label']);
            $this->assertSame($expected['application_form_photo'], $payload['data']['application_form'][12]['label']);
        }
    }

    public function test_rekrutmen_configuration_cluster_uses_expected_slug(): void
    {
        $this->assertSame('rekrutmen/configurations', Configurations::getSlug());
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function localeExpectations(): array
    {
        return [
            'en' => [
                'navigation_label'             => 'Manpower Requests',
                'configurations_label'         => 'Configurations',
                'division_navigation_label'    => 'Recruitment Divisions',
                'pipeline_navigation_label'    => 'Recruitment Pipelines',
                'approver_navigation_label'    => 'Recruitment Approvers',
                'job_posting_navigation_label' => 'Job Postings',
                'activity_log_navigation_label'=> 'Record Recruitment Activity',
                'status_kebutuhan'             => 'New Hiring',
                'request_status'               => 'Pending',
                'application_status'           => 'In Progress',
                'job_level'                    => 'Staff',
                'public_progress_heading'      => 'Manpower Request Progress',
                'mail_progress_action'         => 'View Submission Progress',
                'flowforge_empty_column'       => 'No data in this column',
                'job_detail_message'           => 'Job detail retrieved successfully.',
                'application_form_full_name'   => 'Full Name (As Per ID Card)',
                'application_form_gender'      => 'Gender',
                'application_form_photo'       => 'Latest Personal Photo',
            ],
            'id' => [
                'navigation_label'             => 'Permintaan Tenaga Kerja',
                'configurations_label'         => 'Konfigurasi',
                'division_navigation_label'    => 'Divisi',
                'pipeline_navigation_label'    => 'Pipeline',
                'approver_navigation_label'    => 'Approver',
                'job_posting_navigation_label' => 'Lowongan Kerja',
                'activity_log_navigation_label'=> 'Catat Aktivitas',
                'status_kebutuhan'             => 'Karyawan Baru',
                'request_status'               => 'Pending',
                'application_status'           => 'Dalam Proses',
                'job_level'                    => 'Staf',
                'public_progress_heading'      => 'Progress Permintaan Tenaga Kerja',
                'mail_progress_action'         => 'Lihat Progress Pengajuan',
                'flowforge_empty_column'       => 'Belum ada data di kolom ini',
                'job_detail_message'           => 'Detail lowongan berhasil diambil.',
                'application_form_full_name'   => 'Nama Lengkap (Sesuai KTP)',
                'application_form_gender'      => 'Jenis Kelamin',
                'application_form_photo'       => 'Photo Diri Terbaru',
            ],
        ];
    }
}
