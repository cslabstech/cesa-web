<?php

namespace Cesa\ExitClearance\Tests\Feature;

use Cesa\ExitClearance\Filament\Resources\RequestResource\Pages\ListRequests;
use Cesa\ExitClearance\Filament\Resources\RequestResource\Pages\ViewRequest;
use Cesa\ExitClearance\Models\Request;
use Cesa\ExitClearance\Tests\ExitClearanceTestCase;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
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
            ->assertTableActionExists('download-pdf', record: $request)
            ->assertTableBulkActionExists('download-pdf-bulk');
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
