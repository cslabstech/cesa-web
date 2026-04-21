<?php

namespace Cesa\FormTransfer\Tests\Feature;

use Cesa\FormTransfer\Enums\TransferRequestSubmissionStatus;
use Cesa\FormTransfer\Livewire\PublicTransferRequestForm;
use Cesa\FormTransfer\Models\FormTransfer;
use Cesa\FormTransfer\Models\TransferBank;
use Cesa\FormTransfer\Models\TransferRequest;
use Cesa\FormTransfer\Tests\FormTransferTestCase;
use Livewire\Livewire;

class PublicTransferRequestFormValidationFeedbackTest extends FormTransferTestCase
{
    public function test_public_transfer_request_validation_dispatches_feedback_events(): void
    {
        $formTransfer = FormTransfer::factory()->create([
            'creator_id' => null,
            'company_id' => null,
            'is_active'  => true,
            'code'       => 'PT_CSN',
        ]);
        $bank = TransferBank::factory()->create([
            'is_active' => true,
        ]);

        Livewire::test(PublicTransferRequestForm::class, [
            'formTransfer' => $formTransfer->code,
        ])
            ->set('data.email', 'andi@example.com')
            ->set('data.requester_name', 'Andi Saputra')
            ->set('data.bank_id', $bank->getKey())
            ->set('data.account_name', 'Andi Saputra')
            ->set('data.transfer_amount', '100000')
            ->set('data.purpose', 'Operational expense')
            ->set('data.reference_note', 'Urgent transfer')
            ->call('submit')
            ->assertHasErrors([
                'data.account_number',
            ])
            ->assertDispatched('form-errors-presented')
            ->assertDispatched('form-processing-finished');
    }

    public function test_public_transfer_request_without_division_uses_legacy_default_submission_status(): void
    {
        config()->set('form-transfer.notifications.mail.enabled', false);
        config()->set('form-transfer.notifications.whatsapp.enabled', false);

        $formTransfer = FormTransfer::factory()->create([
            'creator_id' => null,
            'company_id' => null,
            'is_active'  => true,
            'code'       => 'PT_CSN',
        ]);
        $bank = TransferBank::factory()->create([
            'is_active' => true,
        ]);

        Livewire::test(PublicTransferRequestForm::class, [
            'formTransfer' => $formTransfer->code,
        ])
            ->set('data.email', 'andi@example.com')
            ->set('data.requester_name', 'Andi Saputra')
            ->set('data.bank_id', $bank->getKey())
            ->set('data.account_number', '1234567890')
            ->set('data.account_name', 'Andi Saputra')
            ->set('data.transfer_amount', '100000')
            ->set('data.purpose', 'Operational expense')
            ->set('data.reference_note', 'Urgent transfer')
            ->set('data.submission_status', TransferRequestSubmissionStatus::REVISI->value)
            ->call('submit')
            ->assertHasNoErrors();

        $transferRequest = TransferRequest::query()->sole();

        $this->assertNull($transferRequest->division_id);
        $this->assertNull($transferRequest->division_name);
        $this->assertSame(TransferRequestSubmissionStatus::BARU, $transferRequest->submission_status);
    }
}
