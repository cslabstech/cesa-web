<?php

namespace Cesa\Rekrutmen\Tests\Feature;

use Cesa\Rekrutmen\Enums\RequestManPowerStatus;
use Cesa\Rekrutmen\Filament\Resources\RequestManPowerResource;
use Cesa\Rekrutmen\Filament\Resources\RequestManPowerResource\RelationManagers\ApprovalsRelationManager;
use Cesa\Rekrutmen\Models\RequestManPower;
use Cesa\Rekrutmen\Tests\RekrutmenTestCase;
use Filament\Facades\Filament;
use Spatie\Permission\Models\Permission;
use Webkul\Security\Models\User;

class RequestManPowerViewPageTest extends RekrutmenTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');

        $user = User::factory()->create([
            'is_active' => true,
        ]);

        Permission::findOrCreate('view_rekrutmen_request::man::power', 'web');
        Permission::findOrCreate('update_rekrutmen_request::man::power', 'web');

        $user->givePermissionTo([
            'view_rekrutmen_request::man::power',
            'update_rekrutmen_request::man::power',
        ]);

        $this->actingAs($user);
    }

    public function test_request_man_power_resource_registers_the_approvals_relation_manager(): void
    {
        $this->assertSame([
            ApprovalsRelationManager::class,
        ], RequestManPowerResource::getRelations());
    }

    public function test_approved_request_man_power_can_be_held_but_not_set_pending(): void
    {
        $record = new RequestManPower([
            'status' => RequestManPowerStatus::APPROVED,
        ]);

        $this->assertTrue(RequestManPowerResource::canHold($record));
        $this->assertFalse(RequestManPowerResource::canSetPending($record));
    }

    public function test_rejected_request_man_power_can_be_set_pending(): void
    {
        $record = new RequestManPower([
            'status' => RequestManPowerStatus::REJECTED,
        ]);

        $this->assertTrue(RequestManPowerResource::canSetPending($record));
    }

    public function test_request_man_power_infolist_uses_repeatable_entry_for_approval_flow(): void
    {
        $resourcePath = base_path('plugins/cesa/rekrutmen/src/Filament/Resources/RequestManPowerResource.php');
        $resourceSource = file_get_contents($resourcePath);

        $this->assertIsString($resourceSource);
        $this->assertStringContainsString("RepeatableEntry::make('approvals')", $resourceSource);
        $this->assertStringContainsString("TextEntry::make('approver_name')", $resourceSource);
        $this->assertStringContainsString("TextEntry::make('status')", $resourceSource);
        $this->assertStringContainsString("TextEntry::make('action_token')", $resourceSource);
        $this->assertStringContainsString("->label(__('rekrutmen::filament/resources/request-man-power.form.fields.approval_link'))", $resourceSource);
        $this->assertStringContainsString("->formatStateUsing(fn (): string => __('rekrutmen::filament/resources/request-man-power.table.actions.open_approval_page'))", $resourceSource);
        $this->assertStringContainsString('$record->status === RequestManPowerApprovalStatus::PENDING', $resourceSource);
        $this->assertStringNotContainsString("TextEntry::make('step_order')", $resourceSource);
        $this->assertStringNotContainsString("TextEntry::make('approver_title')", $resourceSource);
        $this->assertStringNotContainsString("TextEntry::make('approver_email')", $resourceSource);
        $this->assertStringNotContainsString("TextEntry::make('notified_at')", $resourceSource);
        $this->assertStringNotContainsString("TextEntry::make('acted_at')", $resourceSource);
        $this->assertStringNotContainsString("TextEntry::make('notes')", $resourceSource);
    }
}
