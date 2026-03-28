<?php

namespace Cesa\FormTransfer\Tests\Feature;

use Cesa\FormTransfer\Enums\TransferRequestSubmissionStatus;
use Cesa\FormTransfer\Livewire\PublicTransferRequestForm;
use Cesa\FormTransfer\Models\FormTransfer;
use Cesa\FormTransfer\Models\TransferBank;
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
            ->set('data.division_id', 999)
            ->set('data.bank_id', $bank->getKey())
            ->set('data.account_number', '1234567890')
            ->set('data.account_name', 'Andi Saputra')
            ->set('data.transfer_amount', '100000')
            ->set('data.purpose', 'Operational expense')
            ->set('data.reference_note', 'Urgent transfer')
            ->set('data.submission_status', TransferRequestSubmissionStatus::BARU->value)
            ->call('submit')
            ->assertHasErrors([
                'data.division_id',
            ])
            ->assertDispatched('form-errors-presented')
            ->assertDispatched('form-processing-finished');
    }
}
