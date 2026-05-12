<?php

namespace Cesa\FormTransfer\Tests\Feature\Resources;

use Cesa\FormTransfer\Enums\TransferRequestRealizationStatus;
use Cesa\FormTransfer\Filament\Resources\TransferRequestResource\Pages\ViewTransferRequest;
use Cesa\FormTransfer\Models\TransferRequest;
use Cesa\FormTransfer\Tests\FormTransferTestCase;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Webkul\Security\Enums\PermissionType;
use Webkul\Security\Models\User;

class ViewTransferRequestRealizationAdjustmentTest extends FormTransferTestCase
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
            'is_active'           => true,
            'resource_permission' => PermissionType::GLOBAL->value,
        ]);

        Permission::findOrCreate('view_any_form_transfer_transfer::request', 'web');
        Permission::findOrCreate('view_form_transfer_transfer::request', 'web');
        Permission::findOrCreate('update_form_transfer_transfer::request', 'web');

        $user->givePermissionTo([
            'view_any_form_transfer_transfer::request',
            'view_form_transfer_transfer::request',
            'update_form_transfer_transfer::request',
        ]);

        $this->actingAs($user);
    }

    public function test_done_realization_can_be_adjusted_to_partial_from_view_action(): void
    {
        $request = TransferRequest::factory()->create([
            'transfer_amount'         => 1000000,
            'realized_amount'         => 0,
            'realization_proof_path'  => null,
            'realization_notes'       => null,
            'realization_status'      => TransferRequestRealizationStatus::PENDING->value,
            'invoice_path'            => null,
            'account_attachment_path' => null,
        ]);

        $realization = $request->recordRealization([
            'amount'      => 1000000,
            'realized_at' => '2026-04-20',
            'notes'       => 'Salah input full',
            'user_id'     => auth()->id(),
        ]);

        $component = Livewire::test(ViewTransferRequest::class, ['record' => $request->getKey()])
            ->assertActionExists('edit-realizations')
            ->mountAction('edit-realizations')
            ->assertActionMounted('edit-realizations');

        $mountedActionIndex = array_key_last($component->instance()->mountedActions);
        $realizations = $component->instance()->mountedActions[$mountedActionIndex]['data']['realizations'] ?? [];
        $realizationKey = array_key_first($realizations);

        $this->assertNotNull($realizationKey);

        $realizations[$realizationKey] = [
            'id'          => $realization->getKey(),
            'amount'      => 400000,
            'realized_at' => '2026-04-20',
            'proof_path'  => null,
            'notes'       => 'Koreksi cicilan pertama',
        ];

        Log::shouldReceive('info')
            ->once()
            ->with(
                'Transfer request realizations replace submitted.',
                \Mockery::type('array'),
            );

        $component
            ->setActionData(['realizations' => $realizations])
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $request->refresh();

        $this->assertSame(TransferRequestRealizationStatus::PARTIAL, $request->realization_status);
        $this->assertSame('400000.00', $request->realized_amount);
        $this->assertSame('600000.00', $request->remaining_realization_amount);
        $this->assertTrue($request->canRecordAdditionalRealization());
    }

    public function test_done_realization_requires_proof_on_view_action(): void
    {
        $request = TransferRequest::factory()->create([
            'transfer_amount'         => 1000000,
            'realized_amount'         => 0,
            'realization_proof_path'  => null,
            'realization_notes'       => null,
            'realization_status'      => TransferRequestRealizationStatus::PENDING->value,
            'invoice_path'            => null,
            'account_attachment_path' => null,
        ]);

        Livewire::test(ViewTransferRequest::class, ['record' => $request->getKey()])
            ->assertActionExists('realize-transfer')
            ->mountAction('realize-transfer')
            ->setActionData([
                'amount'                 => 1000000,
                'realization_status'     => TransferRequestRealizationStatus::DONE->value,
                'realized_at'            => '2026-04-20',
                'realization_notes'      => null,
                'realization_proof_path' => null,
            ])
            ->callMountedAction()
            ->assertHasActionErrors([
                'realization_proof_path' => 'required',
            ]);

        $this->assertSame(0, $request->realizations()->count());
    }
}
