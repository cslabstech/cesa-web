<?php

namespace Cesa\FormTransfer\Tests\Feature\Resources;

use Cesa\FormTransfer\Filament\Resources\TransferRequestResource\Pages\ListTransferRequests;
use Cesa\FormTransfer\Models\TransferRequest;
use Cesa\FormTransfer\Tests\FormTransferTestCase;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Webkul\Security\Models\User;
use ZipArchive;

class ListTransferRequestsBulkDownloadTest extends FormTransferTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');

        Route::get('/testing/transfer-requests', fn (): string => 'ok')
            ->name('filament.admin.resources.transfer-requests.index');
        Route::get('/testing/transfer-requests/create', fn (): string => 'ok')
            ->name('filament.admin.resources.transfer-requests.create');
        Route::get('/testing/transfer-requests/{record}', fn (): string => 'ok')
            ->name('filament.admin.resources.transfer-requests.view');
        Route::get('/testing/transfer-requests/{record}/edit', fn (): string => 'ok')
            ->name('filament.admin.resources.transfer-requests.edit');

        $user = User::factory()->create([
            'is_active' => true,
        ]);

        Permission::findOrCreate('view_any_form_transfer_transfer::request', 'web');
        Permission::findOrCreate('view_form_transfer_transfer::request', 'web');

        $user->givePermissionTo([
            'view_any_form_transfer_transfer::request',
            'view_form_transfer_transfer::request',
        ]);

        $this->actingAs($user);
    }

    public function test_table_has_bulk_download_pdf_action(): void
    {
        Livewire::test(ListTransferRequests::class)
            ->assertTableBulkActionExists('download-pdf-bulk');
    }

    public function test_bulk_download_pdf_action_downloads_selected_requests_as_zip(): void
    {
        $requests = TransferRequest::factory()->count(2)->create([
            'invoice_path'            => null,
            'account_attachment_path' => null,
            'realization_proof_path'  => null,
        ]);

        $component = Livewire::test(ListTransferRequests::class)
            ->callTableBulkAction('download-pdf-bulk', $requests)
            ->assertFileDownloaded('pengajuan-transfer-bulk.zip', null, 'application/zip');

        $downloadedArchive = base64_decode((string) data_get($component->effects, 'download.content'), true);

        $this->assertIsString($downloadedArchive);

        $temporaryArchivePath = tempnam(sys_get_temp_dir(), 'transfer-request-zip-test-');

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
            ->map(fn (TransferRequest $request): string => 'pengajuan-transfer-'.($request->uid ?: $request->id).'.pdf')
            ->sort()
            ->values()
            ->all();

        $this->assertSame($expectedEntryNames, $entryNames);
    }
}
