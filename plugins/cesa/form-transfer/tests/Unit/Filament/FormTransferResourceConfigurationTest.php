<?php

namespace Cesa\FormTransfer\Tests\Unit\Filament;

use Cesa\FormTransfer\Filament\Clusters\Configurations\Resources\FormTransferResource;
use Cesa\FormTransfer\Filament\Clusters\Configurations\Resources\FormTransferResource\Pages\EditFormTransfer;
use Cesa\FormTransfer\Filament\Clusters\Configurations\Resources\FormTransferResource\Pages\ViewFormTransfer;
use Cesa\FormTransfer\Filament\Clusters\Configurations\Resources\FormTransferResource\RelationManagers\ApprovalWorkflowsRelationManager;
use Cesa\FormTransfer\Filament\Clusters\Configurations\Resources\FormTransferResource\RelationManagers\DivisionsRelationManager;
use Cesa\FormTransfer\Filament\Clusters\Configurations\Resources\FormTransferResource\RelationManagers\ReferenceNotesRelationManager;
use Cesa\FormTransfer\Models\FormTransfer;
use Cesa\FormTransfer\Tests\FormTransferTestCase;

class FormTransferResourceConfigurationTest extends FormTransferTestCase
{
    public function test_external_entry_preparation_keeps_public_fields_simple(): void
    {
        $prepared = FormTransferResource::prepareDataForPersistence([
            'name'                   => 'Google Resto',
            'public_entry_type'      => FormTransfer::PUBLIC_ENTRY_TYPE_EXTERNAL,
            'public_external_url'    => 'https://forms.gle/google-resto',
            'public_badge_label'     => 'Google Form',
            'approver_mail_subject'  => 'Should be removed',
        ]);

        $this->assertSame(FormTransfer::PUBLIC_ENTRY_TYPE_EXTERNAL, $prepared['public_entry_type']);
        $this->assertSame('https://forms.gle/google-resto', $prepared['public_external_url']);
        $this->assertArrayNotHasKey('public_open_in_new_tab', $prepared);
        $this->assertArrayHasKey('uid_prefix', $prepared);
        $this->assertNotSame('', $prepared['uid_prefix']);

        foreach (array_keys(FormTransferResource::getDefaultNotificationData()) as $field) {
            $this->assertNull($prepared[$field]);
        }
    }

    public function test_internal_entry_preparation_applies_default_notification_templates(): void
    {
        $prepared = FormTransferResource::prepareDataForPersistence([
            'name'              => 'Form Internal',
            'public_entry_type' => FormTransfer::PUBLIC_ENTRY_TYPE_INTERNAL,
        ]);

        $this->assertNull($prepared['public_external_url']);
        $this->assertArrayNotHasKey('public_open_in_new_tab', $prepared);

        foreach (FormTransferResource::getDefaultNotificationData() as $field => $value) {
            $this->assertSame($value, $prepared[$field]);
        }
    }

    public function test_form_transfer_resource_source_hides_internal_sections_for_external_entries(): void
    {
        $source = file_get_contents(base_path(
            'plugins/cesa/form-transfer/src/Filament/Clusters/Configurations/Resources/FormTransferResource.php'
        ));

        $this->assertIsString($source);
        $this->assertStringContainsString(
            '->visible(fn (Get $get): bool => static::isInternalEntry($get))',
            $source
        );
        $this->assertStringContainsString(
            '->visible(fn (FormTransfer $record): bool => ! $record->usesExternalPublicEntry())',
            $source
        );
        $this->assertStringNotContainsString("Toggle::make('public_open_in_new_tab')", $source);
    }

    public function test_configuration_resources_only_use_internal_form_transfers_for_relationship_options(): void
    {
        $resourcePaths = [
            'plugins/cesa/form-transfer/src/Filament/Clusters/Configurations/Resources/DivisionResource.php',
            'plugins/cesa/form-transfer/src/Filament/Clusters/Configurations/Resources/ReferenceNoteResource.php',
            'plugins/cesa/form-transfer/src/Filament/Clusters/Configurations/Resources/ApprovalWorkflowResource.php',
        ];

        foreach ($resourcePaths as $resourcePath) {
            $source = file_get_contents(base_path($resourcePath));

            $this->assertIsString($source);
            $this->assertStringContainsString("Select::make('form_transfer_id')", $source);
            $this->assertStringContainsString("SelectFilter::make('form_transfer_id')", $source);
            $this->assertGreaterThanOrEqual(2, substr_count($source, '->internalEntry()'));
        }
    }

    public function test_relation_managers_are_hidden_for_external_form_transfers(): void
    {
        $internal = FormTransfer::factory()->create([
            'public_entry_type' => FormTransfer::PUBLIC_ENTRY_TYPE_INTERNAL,
        ]);
        $external = FormTransfer::factory()->create([
            'public_entry_type' => FormTransfer::PUBLIC_ENTRY_TYPE_EXTERNAL,
        ]);

        $relationManagers = [
            DivisionsRelationManager::class,
            ReferenceNotesRelationManager::class,
            ApprovalWorkflowsRelationManager::class,
        ];

        $pages = [
            EditFormTransfer::class,
            ViewFormTransfer::class,
        ];

        foreach ($relationManagers as $relationManager) {
            foreach ($pages as $pageClass) {
                $this->assertTrue($relationManager::canViewForRecord($internal, $pageClass));
                $this->assertFalse($relationManager::canViewForRecord($external, $pageClass));
            }
        }
    }
}
