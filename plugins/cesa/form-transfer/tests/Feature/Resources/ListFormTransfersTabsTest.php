<?php

namespace Cesa\FormTransfer\Tests\Feature\Resources;

use Cesa\FormTransfer\Filament\Clusters\Configurations\Resources\FormTransferResource\Pages\ListFormTransfers;
use Cesa\FormTransfer\Models\FormTransfer;
use Cesa\FormTransfer\Tests\FormTransferTestCase;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Webkul\Security\Models\User;

class ListFormTransfersTabsTest extends FormTransferTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');

        if (! Route::has('filament.admin.form-transfer.configurations')) {
            Route::get('/testing/form-transfer/configurations', fn (): string => 'form-transfer-configurations')
                ->name('filament.admin.form-transfer.configurations');
        }

        Route::get('/testing/form-transfers', fn (): string => 'ok')
            ->name('filament.admin.form-transfer.configurations.resources.form-transfers.index');
        Route::get('/testing/form-transfers/create', fn (): string => 'ok')
            ->name('filament.admin.form-transfer.configurations.resources.form-transfers.create');
        Route::get('/testing/form-transfers/{record}', fn (): string => 'ok')
            ->name('filament.admin.form-transfer.configurations.resources.form-transfers.view');
        Route::get('/testing/form-transfers/{record}/edit', fn (): string => 'ok')
            ->name('filament.admin.form-transfer.configurations.resources.form-transfers.edit');

        $user = User::factory()->create([
            'is_active' => true,
        ]);

        Permission::findOrCreate('view_any_form_transfer_form::transfer', 'web');
        Permission::findOrCreate('view_form_transfer_form::transfer', 'web');

        $user->givePermissionTo([
            'view_any_form_transfer_form::transfer',
            'view_form_transfer_form::transfer',
        ]);

        $this->actingAs($user);
    }

    public function test_form_transfer_list_registers_internal_and_external_tabs(): void
    {
        $component = Livewire::test(ListFormTransfers::class);

        $this->assertSame(
            [
                FormTransfer::PUBLIC_ENTRY_TYPE_INTERNAL,
                FormTransfer::PUBLIC_ENTRY_TYPE_EXTERNAL,
            ],
            array_keys($component->instance()->getTabs())
        );
        $this->assertSame(
            FormTransfer::PUBLIC_ENTRY_TYPE_INTERNAL,
            $component->instance()->getDefaultActiveTab()
        );
    }

    public function test_form_transfer_list_defaults_to_internal_tab_and_can_switch_to_external(): void
    {
        $internal = FormTransfer::factory()->create([
            'name'              => 'Form Internal',
            'public_entry_type' => FormTransfer::PUBLIC_ENTRY_TYPE_INTERNAL,
        ]);
        $external = FormTransfer::factory()->create([
            'name'                => 'Form External',
            'public_entry_type'   => FormTransfer::PUBLIC_ENTRY_TYPE_EXTERNAL,
            'public_external_url' => 'https://example.com/form-external',
        ]);

        Livewire::test(ListFormTransfers::class)
            ->assertCanSeeTableRecords([$internal])
            ->assertCanNotSeeTableRecords([$external])
            ->set('activeTab', FormTransfer::PUBLIC_ENTRY_TYPE_EXTERNAL)
            ->assertCanSeeTableRecords([$external])
            ->assertCanNotSeeTableRecords([$internal]);
    }
}
