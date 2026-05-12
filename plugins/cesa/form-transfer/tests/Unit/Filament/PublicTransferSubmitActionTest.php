<?php

namespace Cesa\FormTransfer\Tests\Unit\Filament;

use Cesa\FormTransfer\Support\TransferRequestAttachmentField;
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

    public function test_transfer_request_attachment_fields_do_not_allow_generic_binary_mime_types(): void
    {
        $invoiceTypes = TransferRequestAttachmentField::makeInvoice()->getAcceptedFileTypes();
        $accountAttachmentTypes = TransferRequestAttachmentField::makeAccountAttachment()->getAcceptedFileTypes();

        $this->assertIsArray($invoiceTypes);
        $this->assertIsArray($accountAttachmentTypes);
        $this->assertContains('application/x-pdf', $invoiceTypes);
        $this->assertContains('application/x-pdf', $accountAttachmentTypes);
        $this->assertNotContains('application/octet-stream', $invoiceTypes);
        $this->assertNotContains('application/octet-stream', $accountAttachmentTypes);
    }

    public function test_transfer_request_attachment_fields_use_local_disk(): void
    {
        $this->assertSame('local', TransferRequestAttachmentField::makeInvoice()->getDiskName());
        $this->assertSame('local', TransferRequestAttachmentField::makeAccountAttachment()->getDiskName());
        $this->assertSame('local', TransferRequestAttachmentField::makeRealizationProof()->getDiskName());
    }
}
