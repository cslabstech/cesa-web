<?php

namespace Cesa\FormTransfer\Tests\Unit\Models;

use App\Models\User;
use Cesa\FormTransfer\Enums\TransferRequestApprovalStatus;
use Cesa\FormTransfer\Enums\TransferRequestRealizationStatus;
use Cesa\FormTransfer\Enums\TransferRequestSubmissionStatus;
use Cesa\FormTransfer\Models\FormTransfer;
use Cesa\FormTransfer\Models\TransferApprovalWorkflow;
use Cesa\FormTransfer\Models\TransferBank;
use Cesa\FormTransfer\Models\TransferDivision;
use Cesa\FormTransfer\Models\TransferReferenceNote;
use Cesa\FormTransfer\Models\TransferRequest;
use Cesa\FormTransfer\Models\TransferRequestRealization;
use Cesa\FormTransfer\Tests\FormTransferTestCase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Webkul\Security\Models\User as SecurityUser;

class FormTransferModelTest extends FormTransferTestCase
{
    public function test_generate_next_request_uid_increments_sequence(): void
    {
        $formTransfer = FormTransfer::factory()->create([
            'uid_prefix'   => 'TRX',
            'uid_padding'  => 4,
            'uid_sequence' => 0,
        ]);

        $firstUid = $formTransfer->generateNextRequestUid();
        $secondUid = $formTransfer->generateNextRequestUid();

        $this->assertSame('TRX-0001', $firstUid);
        $this->assertSame('TRX-0002', $secondUid);
        $this->assertSame(2, $formTransfer->fresh()->uid_sequence);
    }

    public function test_form_transfer_assigns_public_sort_order_automatically(): void
    {
        $first = FormTransfer::query()->create([
            'name'       => 'Form Transfer Alpha',
            'uid_prefix' => 'ALPH',
        ]);

        $second = FormTransfer::query()->create([
            'name'       => 'Form Transfer Beta',
            'uid_prefix' => 'BETA',
        ]);

        $this->assertSame(1, $first->public_sort_order);
        $this->assertSame(2, $second->public_sort_order);
    }

    public function test_internal_entry_scope_only_returns_non_deleted_internal_form_transfers(): void
    {
        $internal = FormTransfer::factory()->create([
            'public_entry_type' => FormTransfer::PUBLIC_ENTRY_TYPE_INTERNAL,
        ]);
        FormTransfer::factory()->create([
            'public_entry_type' => FormTransfer::PUBLIC_ENTRY_TYPE_EXTERNAL,
        ]);
        $deletedInternal = FormTransfer::factory()->create([
            'public_entry_type' => FormTransfer::PUBLIC_ENTRY_TYPE_INTERNAL,
        ]);

        $deletedInternal->delete();

        $formTransferIds = FormTransfer::query()
            ->withTrashed()
            ->internalEntry()
            ->pluck('id')
            ->all();

        $this->assertSame([$internal->id], $formTransferIds);
    }

    public function test_has_custom_notification_templates_detects_any_filled_template(): void
    {
        $withoutTemplate = FormTransfer::factory()->make([
            'approver_mail_subject'      => null,
            'approver_mail_greeting'     => null,
            'approver_mail_action_text'  => null,
            'approver_mail_template'     => null,
            'requester_mail_subject'     => null,
            'requester_mail_greeting'    => null,
            'requester_mail_action_text' => null,
            'requester_mail_template'    => null,
            'approver_whatsapp_template' => null,
        ]);

        $withTemplate = FormTransfer::factory()->make([
            'approver_mail_subject' => 'Subject exists',
        ]);

        $this->assertFalse($withoutTemplate->hasCustomNotificationTemplates());
        $this->assertTrue($withTemplate->hasCustomNotificationTemplates());
    }

    public function test_transfer_request_attachment_normalization_and_mutator_are_consistent(): void
    {
        $this->assertSame(['invoice.pdf'], TransferRequest::normalizeAttachmentPaths('invoice.pdf'));
        $this->assertSame(
            ['invoice-a.pdf', 'invoice-b.pdf'],
            TransferRequest::normalizeAttachmentPaths('["invoice-a.pdf","invoice-b.pdf"]')
        );
        $this->assertSame([], TransferRequest::normalizeAttachmentPaths(null));

        $request = new TransferRequest;
        $request->invoice_path = ['invoice-a.pdf', 'invoice-b.pdf'];
        $request->account_attachment_path = 'rekening.pdf';

        $this->assertSame('["invoice-a.pdf","invoice-b.pdf"]', $request->getAttributes()['invoice_path']);
        $this->assertSame('rekening.pdf', $request->getAttributes()['account_attachment_path']);
        $this->assertSame(['invoice-a.pdf', 'invoice-b.pdf'], $request->invoice_path);
        $this->assertSame(['rekening.pdf'], $request->account_attachment_path);
    }

    public function test_transfer_request_creation_populates_uid_and_default_statuses(): void
    {
        $user = User::factory()->create();
        $bank = TransferBank::factory()->create();
        $formTransfer = FormTransfer::factory()->create([
            'uid_prefix'   => 'PAY',
            'uid_padding'  => 3,
            'uid_sequence' => 0,
            'creator_id'   => $user->id,
        ]);

        $request = TransferRequest::query()->create([
            'form_transfer_id' => $formTransfer->id,
            'user_id'          => $user->id,
            'creator_id'       => $user->id,
            'requester_name'   => 'Budi',
            'email'            => 'budi@example.com',
            'account_number'   => '123456789',
            'account_name'     => 'Budi Santoso',
            'bank_id'          => $bank->id,
            'transfer_amount'  => 1250000,
            'purpose'          => 'Operational transfer',
        ]);

        $this->assertSame('PAY-001', $request->uid);
        $this->assertNotEmpty($request->status_response_id);
        $this->assertSame(TransferRequestSubmissionStatus::BARU, $request->submission_status);
        $this->assertSame(TransferRequestApprovalStatus::PENDING, $request->approval_status);
        $this->assertSame(TransferRequestRealizationStatus::PENDING, $request->realization_status);
        $this->assertSame('0.00', $request->realized_amount);
        $this->assertSame('1250000.00', $request->remaining_realization_amount);
    }

    public function test_transfer_request_supports_realization_installments(): void
    {
        $user = User::factory()->create();
        $bank = TransferBank::factory()->create();
        $formTransfer = FormTransfer::factory()->create([
            'creator_id' => $user->id,
        ]);

        $request = TransferRequest::query()->create([
            'form_transfer_id' => $formTransfer->id,
            'user_id'          => $user->id,
            'creator_id'       => $user->id,
            'requester_name'   => 'Budi',
            'email'            => 'budi@example.com',
            'account_number'   => '123456789',
            'account_name'     => 'Budi Santoso',
            'bank_id'          => $bank->id,
            'transfer_amount'  => 1000000,
            'purpose'          => 'Operational transfer',
        ]);

        $request->recordRealization([
            'amount'      => 400000,
            'realized_at' => '2026-04-20',
            'notes'       => 'Cicilan pertama',
            'user_id'     => $user->id,
        ]);

        $request->refresh();

        $this->assertSame(TransferRequestRealizationStatus::PARTIAL, $request->realization_status);
        $this->assertSame('400000.00', $request->realized_amount);
        $this->assertSame('600000.00', $request->remaining_realization_amount);
        $this->assertTrue($request->canRecordAdditionalRealization());
        $this->assertSame(1, $request->realizations()->count());

        $request->recordRealization([
            'amount'      => 600000,
            'realized_at' => '2026-04-21',
            'notes'       => 'Pelunasan',
            'user_id'     => $user->id,
        ]);

        $request->refresh();

        $this->assertSame(TransferRequestRealizationStatus::DONE, $request->realization_status);
        $this->assertSame('1000000.00', $request->realized_amount);
        $this->assertSame('0.00', $request->remaining_realization_amount);
        $this->assertFalse($request->canRecordAdditionalRealization());
        $this->assertSame(2, $request->realizations()->count());
        $this->assertSame('Pelunasan', $request->realization_notes);
    }

    public function test_transfer_request_realizations_can_be_adjusted_after_accidental_full_realization(): void
    {
        $user = User::factory()->create();
        $bank = TransferBank::factory()->create();
        $formTransfer = FormTransfer::factory()->create([
            'creator_id' => $user->id,
        ]);

        $request = TransferRequest::query()->create([
            'form_transfer_id' => $formTransfer->id,
            'user_id'          => $user->id,
            'creator_id'       => $user->id,
            'requester_name'   => 'Budi',
            'email'            => 'budi@example.com',
            'account_number'   => '123456789',
            'account_name'     => 'Budi Santoso',
            'bank_id'          => $bank->id,
            'transfer_amount'  => 1000000,
            'purpose'          => 'Operational transfer',
        ]);

        $realization = $request->recordRealization([
            'amount'      => 1000000,
            'realized_at' => '2026-04-20',
            'notes'       => 'Salah input full',
            'user_id'     => $user->id,
        ]);

        $request->refresh();

        $this->assertSame(TransferRequestRealizationStatus::DONE, $request->realization_status);

        $request->replaceRealizations([
            [
                'id'          => $realization->id,
                'amount'      => 400000,
                'realized_at' => '2026-04-20',
                'notes'       => 'Koreksi cicilan pertama',
            ],
        ], $user->id);

        $request->refresh();
        $realization->refresh();

        $this->assertSame(TransferRequestRealizationStatus::PARTIAL, $request->realization_status);
        $this->assertSame('400000.00', $request->realized_amount);
        $this->assertSame('600000.00', $request->remaining_realization_amount);
        $this->assertTrue($request->canRecordAdditionalRealization());
        $this->assertSame('400000.00', $realization->amount);
        $this->assertSame('Koreksi cicilan pertama', $request->realization_notes);
    }

    public function test_transfer_request_realization_adjustment_rejects_total_above_transfer_amount(): void
    {
        $user = User::factory()->create();
        $bank = TransferBank::factory()->create();
        $formTransfer = FormTransfer::factory()->create([
            'creator_id' => $user->id,
        ]);

        $request = TransferRequest::query()->create([
            'form_transfer_id' => $formTransfer->id,
            'user_id'          => $user->id,
            'creator_id'       => $user->id,
            'requester_name'   => 'Budi',
            'email'            => 'budi@example.com',
            'account_number'   => '123456789',
            'account_name'     => 'Budi Santoso',
            'bank_id'          => $bank->id,
            'transfer_amount'  => 1000000,
            'purpose'          => 'Operational transfer',
        ]);

        try {
            $request->replaceRealizations([
                ['amount' => 700000, 'realized_at' => '2026-04-20'],
                ['amount' => 400000, 'realized_at' => '2026-04-21'],
            ], $user->id);

            $this->fail('Expected over-realization adjustment to be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('realizations', $exception->errors());
        }

        $request->refresh();

        $this->assertSame(TransferRequestRealizationStatus::PENDING, $request->realization_status);
        $this->assertSame('0.00', $request->realized_amount);
        $this->assertSame(0, $request->realizations()->count());
    }

    public function test_transfer_request_rejects_realization_amount_above_remaining_balance(): void
    {
        $user = User::factory()->create();
        $bank = TransferBank::factory()->create();
        $formTransfer = FormTransfer::factory()->create([
            'creator_id' => $user->id,
        ]);

        $request = TransferRequest::query()->create([
            'form_transfer_id' => $formTransfer->id,
            'user_id'          => $user->id,
            'creator_id'       => $user->id,
            'requester_name'   => 'Budi',
            'email'            => 'budi@example.com',
            'account_number'   => '123456789',
            'account_name'     => 'Budi Santoso',
            'bank_id'          => $bank->id,
            'transfer_amount'  => 1000000,
            'purpose'          => 'Operational transfer',
        ]);

        try {
            $request->recordRealization([
                'amount'      => 1000000.01,
                'realized_at' => '2026-04-20',
                'user_id'     => $user->id,
            ]);

            $this->fail('Expected over-realization to be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('amount', $exception->errors());
        }

        $request->refresh();

        $this->assertSame(TransferRequestRealizationStatus::PENDING, $request->realization_status);
        $this->assertSame('0.00', $request->realized_amount);
        $this->assertSame(0, $request->realizations()->count());
    }

    public function test_transfer_request_requires_requester_email(): void
    {
        $user = User::factory()->create();
        $bank = TransferBank::factory()->create();
        $formTransfer = FormTransfer::factory()->create([
            'creator_id' => $user->id,
        ]);

        $this->expectException(ValidationException::class);

        TransferRequest::query()->create([
            'form_transfer_id' => $formTransfer->id,
            'user_id'          => $user->id,
            'creator_id'       => $user->id,
            'requester_name'   => 'Budi',
            'email'            => '   ',
            'account_number'   => '123456789',
            'account_name'     => 'Budi Santoso',
            'bank_id'          => $bank->id,
            'transfer_amount'  => 1250000,
            'purpose'          => 'Operational transfer',
        ]);
    }

    public function test_transfer_approval_workflow_requires_default_email_for_each_step(): void
    {
        $formTransfer = FormTransfer::factory()->create();

        $this->expectException(ValidationException::class);

        TransferApprovalWorkflow::query()->create([
            'form_transfer_id' => $formTransfer->id,
            'name'             => 'Default Workflow',
            'code'             => 'WF-EMAIL',
            'steps'            => [
                [
                    'label'         => 'Manager Approval',
                    'default_name'  => 'Manager',
                    'default_email' => '   ',
                    'default_title' => 'Manager',
                    'is_mandatory'  => true,
                ],
            ],
            'is_active'        => true,
        ]);
    }

    public function test_transfer_division_rejects_external_form_transfer(): void
    {
        $formTransfer = FormTransfer::factory()->create([
            'public_entry_type' => FormTransfer::PUBLIC_ENTRY_TYPE_EXTERNAL,
        ]);

        $this->expectException(ValidationException::class);

        TransferDivision::query()->create([
            'form_transfer_id' => $formTransfer->id,
            'name'             => 'Division External',
            'is_active'        => true,
        ]);
    }

    public function test_transfer_reference_note_rejects_external_form_transfer(): void
    {
        $formTransfer = FormTransfer::factory()->create([
            'public_entry_type' => FormTransfer::PUBLIC_ENTRY_TYPE_EXTERNAL,
        ]);

        $this->expectException(ValidationException::class);

        TransferReferenceNote::query()->create([
            'form_transfer_id' => $formTransfer->id,
            'label'            => 'Catatan External',
            'is_active'        => true,
        ]);
    }

    public function test_transfer_approval_workflow_rejects_external_form_transfer(): void
    {
        $formTransfer = FormTransfer::factory()->create([
            'public_entry_type' => FormTransfer::PUBLIC_ENTRY_TYPE_EXTERNAL,
        ]);

        $this->expectException(ValidationException::class);

        TransferApprovalWorkflow::query()->create([
            'form_transfer_id' => $formTransfer->id,
            'name'             => 'Workflow External',
            'code'             => 'WF-EXT',
            'steps'            => [
                [
                    'label'         => 'Approver',
                    'default_name'  => 'Manager',
                    'default_email' => 'manager@example.com',
                    'default_title' => 'Manager',
                    'is_mandatory'  => true,
                ],
            ],
            'is_active'        => true,
        ]);
    }

    public function test_transfer_request_creation_renames_uploaded_attachments_using_uid(): void
    {
        Storage::fake('local');
        config()->set('filesystems.default', 'local');
        config()->set('filament.default_filesystem_disk', 'local');

        Storage::disk('local')->put('form-transfer/invoices/tmp-invoice-a.pdf', 'invoice-a');
        Storage::disk('local')->put('form-transfer/invoices/tmp-invoice-b.pdf', 'invoice-b');
        Storage::disk('local')->put('form-transfer/account-attachments/tmp-account.pdf', 'account');

        $user = User::factory()->create();
        $bank = TransferBank::factory()->create();
        $formTransfer = FormTransfer::factory()->create([
            'uid_prefix'   => 'CSN',
            'uid_padding'  => 5,
            'uid_sequence' => 112,
            'creator_id'   => $user->id,
        ]);

        $request = TransferRequest::query()->create([
            'form_transfer_id'        => $formTransfer->id,
            'user_id'                 => $user->id,
            'creator_id'              => $user->id,
            'requester_name'          => 'Budi',
            'email'                   => 'budi@example.com',
            'account_number'          => '123456789',
            'account_name'            => 'Budi Santoso',
            'bank_id'                 => $bank->id,
            'transfer_amount'         => 1250000,
            'purpose'                 => 'Operational transfer',
            'invoice_path'            => [
                'form-transfer/invoices/tmp-invoice-a.pdf',
                'form-transfer/invoices/tmp-invoice-b.pdf',
            ],
            'account_attachment_path' => 'form-transfer/account-attachments/tmp-account.pdf',
        ])->refresh();

        $this->assertSame('CSN-00113', $request->uid);
        $this->assertCount(2, $request->invoice_path);
        $this->assertMatchesRegularExpression(
            '#^form-transfer/invoices/CSN-00113-01-[a-z0-9]{6}\.pdf$#',
            $request->invoice_path[0]
        );
        $this->assertMatchesRegularExpression(
            '#^form-transfer/invoices/CSN-00113-02-[a-z0-9]{6}\.pdf$#',
            $request->invoice_path[1]
        );
        $this->assertCount(1, $request->account_attachment_path);
        $this->assertMatchesRegularExpression(
            '#^form-transfer/account-attachments/CSN-00113-[a-z0-9]{6}\.pdf$#',
            $request->account_attachment_path[0]
        );

        Storage::disk('local')->assertMissing('form-transfer/invoices/tmp-invoice-a.pdf');
        Storage::disk('local')->assertMissing('form-transfer/invoices/tmp-invoice-b.pdf');
        Storage::disk('local')->assertMissing('form-transfer/account-attachments/tmp-account.pdf');
        Storage::disk('local')->assertExists($request->invoice_path[0]);
        Storage::disk('local')->assertExists($request->invoice_path[1]);
        Storage::disk('local')->assertExists($request->account_attachment_path[0]);
    }

    public function test_transfer_request_update_renames_realization_attachment_using_uid(): void
    {
        Storage::fake('local');
        config()->set('filesystems.default', 'local');
        config()->set('filament.default_filesystem_disk', 'local');

        Storage::disk('local')->put('form-transfer/realizations/tmp-realization.png', 'proof');

        $user = User::factory()->create();
        $bank = TransferBank::factory()->create();
        $formTransfer = FormTransfer::factory()->create([
            'uid_prefix'   => 'CSN',
            'uid_padding'  => 5,
            'uid_sequence' => 112,
            'creator_id'   => $user->id,
        ]);

        $request = TransferRequest::query()->create([
            'form_transfer_id' => $formTransfer->id,
            'user_id'          => $user->id,
            'creator_id'       => $user->id,
            'requester_name'   => 'Budi',
            'email'            => 'budi@example.com',
            'account_number'   => '123456789',
            'account_name'     => 'Budi Santoso',
            'bank_id'          => $bank->id,
            'transfer_amount'  => 1250000,
            'purpose'          => 'Operational transfer',
        ]);

        $request->fill([
            'realization_status'     => TransferRequestRealizationStatus::DONE,
            'realized_at'            => now()->toDateString(),
            'realization_proof_path' => 'form-transfer/realizations/tmp-realization.png',
        ]);
        $request->save();
        $request->refresh();

        $this->assertMatchesRegularExpression(
            '#^form-transfer/realizations/CSN-00113-[a-z0-9]{6}\.png$#',
            (string) $request->realization_proof_path
        );

        Storage::disk('local')->assertMissing('form-transfer/realizations/tmp-realization.png');
        Storage::disk('local')->assertExists((string) $request->realization_proof_path);
    }

    public function test_transfer_request_realization_renames_proof_using_request_uid(): void
    {
        Storage::fake('local');
        config()->set('filesystems.default', 'local');
        config()->set('filament.default_filesystem_disk', 'local');

        Storage::disk('local')->put('form-transfer/realizations/tmp-installment.png', 'proof');

        $user = User::factory()->create();
        $bank = TransferBank::factory()->create();
        $formTransfer = FormTransfer::factory()->create([
            'uid_prefix'   => 'CSN',
            'uid_padding'  => 5,
            'uid_sequence' => 112,
            'creator_id'   => $user->id,
        ]);

        $request = TransferRequest::query()->create([
            'form_transfer_id' => $formTransfer->id,
            'user_id'          => $user->id,
            'creator_id'       => $user->id,
            'requester_name'   => 'Budi',
            'email'            => 'budi@example.com',
            'account_number'   => '123456789',
            'account_name'     => 'Budi Santoso',
            'bank_id'          => $bank->id,
            'transfer_amount'  => 1250000,
            'purpose'          => 'Operational transfer',
        ]);

        $realization = $request->recordRealization([
            'amount'      => 1250000,
            'realized_at' => now()->toDateString(),
            'proof_path'  => 'form-transfer/realizations/tmp-installment.png',
            'user_id'     => $user->id,
        ])->refresh();

        $this->assertInstanceOf(TransferRequestRealization::class, $realization);
        $this->assertMatchesRegularExpression(
            '#^form-transfer/realizations/CSN-00113-R'.$realization->getKey().'-[a-z0-9]{6}\.png$#',
            (string) $realization->proof_path
        );

        $request->refresh();

        $this->assertSame(TransferRequestRealizationStatus::DONE, $request->realization_status);
        $this->assertSame($realization->proof_path, $request->realization_proof_path);

        Storage::disk('local')->assertMissing('form-transfer/realizations/tmp-installment.png');
        Storage::disk('local')->assertExists((string) $realization->proof_path);

        $request->forceDelete();

        Storage::disk('local')->assertMissing((string) $realization->proof_path);
    }

    public function test_transfer_request_realization_adjustment_keeps_existing_proof_when_submitted_null(): void
    {
        Storage::fake('local');
        config()->set('filesystems.default', 'local');
        config()->set('filament.default_filesystem_disk', 'local');

        Storage::disk('local')->put('form-transfer/realizations/tmp-installment.png', 'proof');

        $user = User::factory()->create();
        $bank = TransferBank::factory()->create();
        $formTransfer = FormTransfer::factory()->create([
            'uid_prefix'   => 'CSN',
            'uid_padding'  => 5,
            'uid_sequence' => 112,
            'creator_id'   => $user->id,
        ]);

        $request = TransferRequest::query()->create([
            'form_transfer_id' => $formTransfer->id,
            'user_id'          => $user->id,
            'creator_id'       => $user->id,
            'requester_name'   => 'Budi',
            'email'            => 'budi@example.com',
            'account_number'   => '123456789',
            'account_name'     => 'Budi Santoso',
            'bank_id'          => $bank->id,
            'transfer_amount'  => 1000000,
            'purpose'          => 'Operational transfer',
        ]);

        $realization = $request->recordRealization([
            'amount'      => 1000000,
            'realized_at' => '2026-04-20',
            'proof_path'  => 'form-transfer/realizations/tmp-installment.png',
            'user_id'     => $user->id,
        ])->refresh();

        $storedProofPath = (string) $realization->proof_path;

        $request->replaceRealizations([
            [
                'id'          => $realization->id,
                'amount'      => 400000,
                'realized_at' => '2026-04-20',
                'proof_path'  => null,
                'notes'       => 'Koreksi cicilan pertama',
            ],
        ], $user->id);

        $request->refresh();
        $realization->refresh();

        $this->assertSame($storedProofPath, $realization->proof_path);
        $this->assertSame($storedProofPath, $request->realization_proof_path);
        Storage::disk('local')->assertExists($storedProofPath);
    }

    public function test_transfer_request_realization_force_delete_keeps_proof_file(): void
    {
        Storage::fake('local');
        config()->set('filesystems.default', 'local');
        config()->set('filament.default_filesystem_disk', 'local');

        Storage::disk('local')->put('form-transfer/realizations/tmp-installment.png', 'proof');

        $user = User::factory()->create();
        $bank = TransferBank::factory()->create();
        $formTransfer = FormTransfer::factory()->create([
            'uid_prefix'   => 'CSN',
            'uid_padding'  => 5,
            'uid_sequence' => 112,
            'creator_id'   => $user->id,
        ]);

        $request = TransferRequest::query()->create([
            'form_transfer_id' => $formTransfer->id,
            'user_id'          => $user->id,
            'creator_id'       => $user->id,
            'requester_name'   => 'Budi',
            'email'            => 'budi@example.com',
            'account_number'   => '123456789',
            'account_name'     => 'Budi Santoso',
            'bank_id'          => $bank->id,
            'transfer_amount'  => 1000000,
            'purpose'          => 'Operational transfer',
        ]);

        $realization = $request->recordRealization([
            'amount'      => 1000000,
            'realized_at' => '2026-04-20',
            'proof_path'  => 'form-transfer/realizations/tmp-installment.png',
            'user_id'     => $user->id,
        ])->refresh();

        $storedProofPath = (string) $realization->proof_path;

        $realization->delete();
        $realization->forceDelete();

        Storage::disk('local')->assertExists($storedProofPath);
    }

    public function test_transfer_request_update_deletes_replaced_attachment_and_keeps_added_invoice(): void
    {
        Storage::fake('local');
        config()->set('filesystems.default', 'local');
        config()->set('filament.default_filesystem_disk', 'local');

        Storage::disk('local')->put('form-transfer/invoices/tmp-invoice-a.pdf', 'invoice-a');
        Storage::disk('local')->put('form-transfer/account-attachments/tmp-account-a.pdf', 'account-a');

        $user = User::factory()->create();
        $bank = TransferBank::factory()->create();
        $formTransfer = FormTransfer::factory()->create([
            'uid_prefix'   => 'CSN',
            'uid_padding'  => 5,
            'uid_sequence' => 112,
            'creator_id'   => $user->id,
        ]);

        $request = TransferRequest::query()->create([
            'form_transfer_id'        => $formTransfer->id,
            'user_id'                 => $user->id,
            'creator_id'              => $user->id,
            'requester_name'          => 'Budi',
            'email'                   => 'budi@example.com',
            'account_number'          => '123456789',
            'account_name'            => 'Budi Santoso',
            'bank_id'                 => $bank->id,
            'transfer_amount'         => 1250000,
            'purpose'                 => 'Operational transfer',
            'invoice_path'            => 'form-transfer/invoices/tmp-invoice-a.pdf',
            'account_attachment_path' => 'form-transfer/account-attachments/tmp-account-a.pdf',
        ])->refresh();

        $existingInvoicePath = $request->invoice_path[0];
        $existingAccountPath = $request->account_attachment_path[0];

        Storage::disk('local')->put('form-transfer/invoices/tmp-invoice-b.pdf', 'invoice-b');
        Storage::disk('local')->put('form-transfer/account-attachments/tmp-account-b.pdf', 'account-b');

        $request->fill([
            'invoice_path'            => [$existingInvoicePath, 'form-transfer/invoices/tmp-invoice-b.pdf'],
            'account_attachment_path' => 'form-transfer/account-attachments/tmp-account-b.pdf',
        ]);
        $request->save();
        $request->refresh();

        $this->assertCount(2, $request->invoice_path);
        $this->assertContains($existingInvoicePath, $request->invoice_path);
        $this->assertMatchesRegularExpression(
            '#^form-transfer/invoices/CSN-00113-02-[a-z0-9]{6}\.pdf$#',
            $request->invoice_path[1]
        );
        $this->assertCount(1, $request->account_attachment_path);
        $this->assertMatchesRegularExpression(
            '#^form-transfer/account-attachments/CSN-00113-[a-z0-9]{6}\.pdf$#',
            $request->account_attachment_path[0]
        );

        Storage::disk('local')->assertExists($existingInvoicePath);
        Storage::disk('local')->assertMissing($existingAccountPath);
        Storage::disk('local')->assertMissing('form-transfer/invoices/tmp-invoice-b.pdf');
        Storage::disk('local')->assertMissing('form-transfer/account-attachments/tmp-account-b.pdf');
        Storage::disk('local')->assertExists($request->invoice_path[1]);
        Storage::disk('local')->assertExists($request->account_attachment_path[0]);
    }

    public function test_transfer_request_soft_delete_keeps_attachments_until_force_delete(): void
    {
        Storage::fake('local');
        config()->set('filesystems.default', 'local');
        config()->set('filament.default_filesystem_disk', 'local');

        Storage::disk('local')->put('form-transfer/invoices/tmp-invoice-a.pdf', 'invoice-a');
        Storage::disk('local')->put('form-transfer/account-attachments/tmp-account-a.pdf', 'account-a');
        Storage::disk('local')->put('form-transfer/realizations/tmp-realization-a.pdf', 'realization-a');

        $user = User::factory()->create();
        $bank = TransferBank::factory()->create();
        $formTransfer = FormTransfer::factory()->create([
            'uid_prefix'   => 'CSN',
            'uid_padding'  => 5,
            'uid_sequence' => 112,
            'creator_id'   => $user->id,
        ]);

        $request = TransferRequest::query()->create([
            'form_transfer_id'        => $formTransfer->id,
            'user_id'                 => $user->id,
            'creator_id'              => $user->id,
            'requester_name'          => 'Budi',
            'email'                   => 'budi@example.com',
            'account_number'          => '123456789',
            'account_name'            => 'Budi Santoso',
            'bank_id'                 => $bank->id,
            'transfer_amount'         => 1250000,
            'purpose'                 => 'Operational transfer',
            'invoice_path'            => 'form-transfer/invoices/tmp-invoice-a.pdf',
            'account_attachment_path' => 'form-transfer/account-attachments/tmp-account-a.pdf',
            'realization_proof_path'  => 'form-transfer/realizations/tmp-realization-a.pdf',
        ])->refresh();

        $storedInvoicePath = $request->invoice_path[0];
        $storedAccountPath = $request->account_attachment_path[0];
        $storedRealizationPath = (string) $request->realization_proof_path;

        $request->delete();

        Storage::disk('local')->assertExists($storedInvoicePath);
        Storage::disk('local')->assertExists($storedAccountPath);
        Storage::disk('local')->assertExists($storedRealizationPath);

        $request->forceDelete();

        Storage::disk('local')->assertMissing($storedInvoicePath);
        Storage::disk('local')->assertMissing($storedAccountPath);
        Storage::disk('local')->assertMissing($storedRealizationPath);
    }

    public function test_soft_deleted_related_records_remain_readable(): void
    {
        $formTransfer = FormTransfer::factory()->create();
        $division = TransferDivision::factory()->create([
            'form_transfer_id' => $formTransfer->id,
        ]);
        $referenceNote = TransferReferenceNote::factory()->create([
            'form_transfer_id' => $formTransfer->id,
        ]);
        $workflow = TransferApprovalWorkflow::factory()->create([
            'form_transfer_id' => $formTransfer->id,
            'division_id'      => $division->id,
        ]);
        $request = TransferRequest::factory()->create([
            'form_transfer_id' => $formTransfer->id,
            'creator_id'       => $formTransfer->creator_id,
        ]);

        $division->delete();
        $referenceNote->delete();
        $workflow->delete();
        $request->delete();
        SecurityUser::query()->findOrFail($request->creator_id)->delete();

        $freshFormTransfer = FormTransfer::query()->findOrFail($formTransfer->id);
        $freshRequest = TransferRequest::withTrashed()->findOrFail($request->id);

        $this->assertTrue($freshFormTransfer->divisions->contains('id', $division->id));
        $this->assertTrue($freshFormTransfer->referenceNotes->contains('id', $referenceNote->id));
        $this->assertTrue($freshFormTransfer->approvalWorkflows->contains('id', $workflow->id));
        $this->assertTrue($freshFormTransfer->transferRequests->contains('id', $request->id));
        $this->assertSame($request->creator_id, $freshRequest->creator?->id);
    }
}
