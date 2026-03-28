<?php

namespace Cesa\FormTransfer\Tests\Unit\Filament;

use Cesa\FormTransfer\Tests\FormTransferTestCase;

class PublicTransferSubmitActionTest extends FormTransferTestCase
{
    public function test_public_transfer_form_uses_consistent_submit_copy(): void
    {
        $translations = require base_path('plugins/cesa/form-transfer/resources/lang/id/public.php');

        $this->assertSame('Kirim Pengajuan', data_get($translations, 'form.submit'));
    }

    public function test_public_transfer_form_submit_action_has_styled_attributes(): void
    {
        $component = file_get_contents(base_path('plugins/cesa/form-transfer/src/Livewire/PublicTransferRequestForm.php'));

        $this->assertIsString($component);
        $this->assertStringContainsString('->extraAttributes([', $component);
        $this->assertStringContainsString("->submit('submit')", $component);
    }
}
