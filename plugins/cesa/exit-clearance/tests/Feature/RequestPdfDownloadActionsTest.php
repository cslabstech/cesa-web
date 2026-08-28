<?php

namespace Cesa\ExitClearance\Tests\Feature;

use Cesa\ExitClearance\Filament\Resources\RequestResource\Pages\ListRequests;
use Cesa\ExitClearance\Filament\Resources\RequestResource\Pages\ViewRequest;
use Cesa\ExitClearance\Jobs\SendWhatsAppNotification;
use Cesa\ExitClearance\Models\Approver;
use Cesa\ExitClearance\Models\Request;
use Cesa\ExitClearance\Notifications\ApprovalRequestNotification;
use Cesa\ExitClearance\Services\ExitClearanceNotificationService;
use Cesa\ExitClearance\Services\ExitClearanceRequestService;
use Cesa\ExitClearance\Tests\ExitClearanceTestCase;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use ReflectionProperty;
use Webkul\Security\Enums\PermissionType;
use Webkul\Security\Models\User as SecurityUser;
use ZipArchive;

class RequestPdfDownloadActionsTest extends ExitClearanceTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');

        $this->registerRequestResourceRoutes();
        $this->actingAs($this->fakeExitClearanceUser([
            'view_any_exit_clearance_request',
            'view_exit_clearance_request',
        ]));
    }

    public function test_request_list_exposes_single_and_bulk_pdf_actions(): void
    {
        $request = Request::factory()->create();

        Livewire::test(ListRequests::class)
            ->assertTableActionExists('view_progress', record: $request)
            ->assertTableActionExists('download-pdf', record: $request)
            ->assertTableBulkActionExists('download-pdf-bulk');
    }

    public function test_admin_request_view_exposes_public_progress_link(): void
    {
        $request = Request::factory()->create([
            'form_response_id' => 'exit-progress-token-123',
        ]);

        $expectedUrl = route('exit-clearance.public.progress', [
            'response' => 'exit-progress-token-123',
        ]);

        $this->assertSame($expectedUrl, $request->getPublicProgressUrl());

        Livewire::test(ViewRequest::class, [
            'record' => $request->getKey(),
        ])
            ->assertActionExists('view_progress')
            ->assertSee(__('exit-clearance::filament/resources/request.actions.view_progress'))
            ->assertSee($expectedUrl, false);
    }

    public function test_admin_request_view_shows_approval_flow_with_immediate_approval_links(): void
    {
        app()->setLocale('id');

        $request = Request::factory()->create([
            'form_status' => 'Pending',
        ]);

        $approver = Approver::query()->create([
            'name'  => 'Arik Cahya Hidayat',
            'email' => 'arik@example.com',
            'title' => 'IT Manager',
        ]);

        $request->approvers()->sync([
            $approver->getKey() => ['status' => 'pending'],
        ]);

        $expectedUrl = app(ExitClearanceNotificationService::class)->buildApprovalUrl($request, $approver);

        Livewire::test(ViewRequest::class, [
            'record' => $request->getKey(),
        ])
            ->assertSee(__('exit-clearance::filament/resources/request.infolist.approval_chain'))
            ->assertSee(__('exit-clearance::filament/resources/request.infolist_fields.approval_step'))
            ->assertSee(__('exit-clearance::filament/resources/request.infolist_fields.approver_name'))
            ->assertSee(__('exit-clearance::filament/resources/request.infolist_fields.approver_status'))
            ->assertSee(__('exit-clearance::filament/resources/request.infolist_fields.approval_link'))
            ->assertSee('IT Manager')
            ->assertSee('Arik Cahya Hidayat')
            ->assertSee('Menunggu')
            ->assertSee(__('exit-clearance::filament/resources/request.actions.open_approval_page'))
            ->assertSee($expectedUrl, false);
    }

    public function test_view_page_can_resend_whatsapp_to_a_single_pending_approver(): void
    {
        Notification::fake();
        Queue::fake();

        config()->set('exit-clearance.notifications.mail.enabled', true);
        config()->set('exit-clearance.notifications.whatsapp.enabled', true);
        config()->set('exit-clearance.notifications.whatsapp.endpoint', 'https://example.com/whatsapp');
        config()->set('exit-clearance.notifications.whatsapp.api_key', 'test-api-key');
        config()->set('exit-clearance.notifications.whatsapp.throttle.enabled', false);

        $request = Request::factory()->create([
            'form_status' => ExitClearanceRequestService::FORM_STATUS_PENDING,
        ]);

        $gaOfficer = Approver::query()->create([
            'name'  => 'Uwis GA',
            'title' => 'GA Officer',
            'email' => 'uwis.ga@example.com',
            'phone' => '089665104596',
        ]);

        $hrManager = Approver::query()->create([
            'name'  => 'Ester HR',
            'title' => 'HR Manager',
            'email' => 'ester.hr@example.com',
            'phone' => '081575216729',
        ]);

        $request->approvers()->sync([
            $gaOfficer->id => ['status' => ExitClearanceRequestService::APPROVAL_PENDING],
            $hrManager->id => ['status' => ExitClearanceRequestService::APPROVAL_PENDING],
        ]);

        Livewire::test(ViewRequest::class, [
            'record' => $request->getKey(),
        ])
            ->callAction('resend-pending-approvers', [
                'approver_id' => $gaOfficer->getKey(),
            ])
            ->assertNotified(__('exit-clearance::filament/resources/request/pages/view-request.notifications.notifications_resent.title'));

        Notification::assertSentOnDemandTimes(ApprovalRequestNotification::class, 1);
        Queue::assertPushed(SendWhatsAppNotification::class, 1);
        Queue::assertPushed(SendWhatsAppNotification::class, function (SendWhatsAppNotification $job): bool {
            $phone = new ReflectionProperty($job, 'phone');

            return $phone->getValue($job) === '6289665104596';
        });
    }

    public function test_view_page_download_pdf_action_downloads_pdf(): void
    {
        $request = Request::factory()->create();

        $component = Livewire::test(ViewRequest::class, [
            'record' => $request->getKey(),
        ])
            ->callAction('download-pdf')
            ->assertFileDownloaded('exit-clearance-'.($request->form_uid ?: $request->id).'.pdf');

        $downloadedPdf = base64_decode((string) data_get($component->effects, 'download.content'), true);

        $this->assertIsString($downloadedPdf);
        $this->assertStringStartsWith('%PDF', $downloadedPdf);
    }

    public function test_bulk_download_pdf_action_downloads_selected_requests_as_zip(): void
    {
        $requests = Request::factory()->count(2)->create();

        $component = Livewire::test(ListRequests::class)
            ->callTableBulkAction('download-pdf-bulk', $requests)
            ->assertFileDownloaded('exit-clearance-bulk.zip', null, 'application/zip');

        $downloadedArchive = base64_decode((string) data_get($component->effects, 'download.content'), true);

        $this->assertIsString($downloadedArchive);

        $temporaryArchivePath = tempnam(sys_get_temp_dir(), 'exit-clearance-zip-test-');

        $this->assertNotFalse($temporaryArchivePath);

        file_put_contents($temporaryArchivePath, $downloadedArchive);

        $zip = new ZipArchive;
        $zipOpenStatus = $zip->open($temporaryArchivePath);

        $this->assertTrue($zipOpenStatus === true);

        $entryNames = [];

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $entry = $zip->statIndex($index);

            if (is_array($entry) && isset($entry['name'])) {
                $entryNames[] = $entry['name'];
            }
        }

        $zip->close();

        unlink($temporaryArchivePath);

        sort($entryNames);

        $expectedEntryNames = $requests
            ->map(fn (Request $request): string => 'exit-clearance-'.($request->form_uid ?: $request->id).'.pdf')
            ->sort()
            ->values()
            ->all();

        $this->assertSame($expectedEntryNames, $entryNames);
    }

    private function registerRequestResourceRoutes(): void
    {
        if (! Route::has('filament.admin.resources.requests.index')) {
            Route::get('/testing/exit-clearance/requests', fn (): string => 'requests')
                ->name('filament.admin.resources.requests.index');
        }

        if (! Route::has('filament.admin.resources.requests.create')) {
            Route::get('/testing/exit-clearance/requests/create', fn (): string => 'requests')
                ->name('filament.admin.resources.requests.create');
        }

        if (! Route::has('filament.admin.resources.requests.view')) {
            Route::get('/testing/exit-clearance/requests/{record}', fn (): string => 'requests')
                ->name('filament.admin.resources.requests.view');
        }

        if (! Route::has('filament.admin.resources.requests.edit')) {
            Route::get('/testing/exit-clearance/requests/{record}/edit', fn (): string => 'requests')
                ->name('filament.admin.resources.requests.edit');
        }

        if (! Route::has('exit-clearance.public.attachments.download')) {
            Route::get('/testing/exit-clearance/public/attachments/{response}/{attachment}', fn (): string => 'attachment')
                ->name('exit-clearance.public.attachments.download');
        }

        if (! Route::has('exit-clearance.public.progress')) {
            Route::get('/exit-clearance/progress/{response}', fn (): string => 'progress')
                ->name('exit-clearance.public.progress');
        }

        if (! Route::has('exit-clearance.public.approval')) {
            Route::get('/exit-clearance/approval/{request}/{approver}', fn (): string => 'approval')
                ->name('exit-clearance.public.approval');
        }

        $routes = app('router')->getRoutes();
        $routes->refreshNameLookups();
        $routes->refreshActionLookups();
    }

    /**
     * @param  array<int, string>  $abilities
     */
    private function fakeExitClearanceUser(array $abilities): SecurityUser
    {
        $user = new class extends SecurityUser
        {
            /** @var array<int, string> */
            public array $grantedAbilities = [];

            public function can($ability, $arguments = []): bool
            {
                return in_array($ability, $this->grantedAbilities, true);
            }
        };

        DB::table('users')->updateOrInsert(
            ['id' => 1],
            [
                'name'                => 'Exit Clearance PDF Test User',
                'email'               => 'exit-clearance-pdf-test@example.com',
                'password'            => bcrypt('password'),
                'resource_permission' => PermissionType::GLOBAL->value,
                'created_at'          => now(),
                'updated_at'          => now(),
            ],
        );

        $user->id = 1;
        $user->resource_permission = PermissionType::GLOBAL;
        $user->grantedAbilities = $abilities;

        return $user;
    }
}
