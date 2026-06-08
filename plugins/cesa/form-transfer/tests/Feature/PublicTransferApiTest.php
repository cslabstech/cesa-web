<?php

namespace Cesa\FormTransfer\Tests\Feature;

use Cesa\FormTransfer\Enums\TransferRequestSubmissionStatus;
use Cesa\FormTransfer\Models\FormTransfer;
use Cesa\FormTransfer\Models\FormTransferPublicCategory;
use Cesa\FormTransfer\Models\TransferBank;
use Cesa\FormTransfer\Models\TransferDivision;
use Cesa\FormTransfer\Models\TransferReferenceNote;
use Cesa\FormTransfer\Models\TransferRequest;
use Cesa\FormTransfer\Tests\FormTransferTestCase;
use Illuminate\Support\Facades\Route;

class PublicTransferApiTest extends FormTransferTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! Route::has('form-transfer.api.transfer-requests.index')) {
            require base_path('plugins/cesa/form-transfer/routes/api.php');
        }

        if (! Route::has('form-transfer.public.progress')) {
            require base_path('plugins/cesa/form-transfer/routes/web.php');
        }

        $routes = app('router')->getRoutes();
        $routes->refreshNameLookups();
        $routes->refreshActionLookups();

        config()->set('form-transfer.notifications.mail.enabled', false);
        config()->set('form-transfer.notifications.whatsapp.enabled', false);
        config()->set('form-transfer.security.recaptcha.enabled', false);
    }

    public function test_public_api_lists_transfer_and_affiliate_catalogs_separately(): void
    {
        FormTransfer::factory()->create([
            'creator_id'                     => null,
            'company_id'                     => null,
            'name'                           => 'Transfer Operasional',
            'code'                           => 'TRANSFER_OPERASIONAL',
            'is_active'                      => true,
            'show_on_transfer_request_index' => true,
            'show_on_affiliate_index'        => false,
            'public_sort_order'              => 1,
        ]);

        FormTransfer::factory()->create([
            'creator_id'                     => null,
            'company_id'                     => null,
            'name'                           => 'Transfer Afiliasi',
            'code'                           => 'TRANSFER_AFILIASI',
            'is_active'                      => true,
            'show_on_transfer_request_index' => false,
            'show_on_affiliate_index'        => true,
            'public_sort_order'              => 1,
        ]);

        FormTransfer::factory()->create([
            'creator_id'                     => null,
            'company_id'                     => null,
            'name'                           => 'Inactive Afiliasi',
            'is_active'                      => false,
            'show_on_affiliate_index'        => true,
        ]);

        $retail = FormTransferPublicCategory::factory()->create([
            'name' => 'Retail',
            'slug' => 'retail',
        ]);

        $retailForm = FormTransfer::factory()->create([
            'creator_id'                     => null,
            'company_id'                     => null,
            'name'                           => 'Transfer Retail',
            'code'                           => 'TRANSFER_RETAIL',
            'is_active'                      => true,
            'show_on_transfer_request_index' => false,
            'public_sort_order'              => 1,
            'public_entry_type'              => FormTransfer::PUBLIC_ENTRY_TYPE_EXTERNAL,
            'public_external_url'            => 'https://forms.gle/retail',
        ]);
        $retailForm->publicCategories()->attach($retail);

        $this->getJson('/api/form-transfer/transfer-requests')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.mode', 'transfer_request')
            ->assertJsonPath('meta.public_index_slug', FormTransfer::PUBLIC_INDEX_TRANSFER_REQUESTS)
            ->assertJsonPath('data.0.code', 'TRANSFER_OPERASIONAL');

        $this->getJson('/api/form-transfer/afiliasi')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.mode', 'affiliate')
            ->assertJsonPath('meta.public_index_slug', FormTransfer::PUBLIC_INDEX_AFFILIATES)
            ->assertJsonPath('data.0.code', 'TRANSFER_AFILIASI');

        $this->getJson('/api/form-transfer/catalogs/retail')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.mode', 'retail')
            ->assertJsonPath('meta.public_index_slug', 'retail')
            ->assertJsonPath('data.0.code', 'TRANSFER_RETAIL')
            ->assertJsonPath('data.0.public_index_slugs', ['retail']);

        $this->getJson('/api/form-transfer/catalogs/unknown-category')
            ->assertNotFound();
    }

    public function test_public_api_returns_internal_form_detail_with_reference_data(): void
    {
        $formTransfer = FormTransfer::factory()->create([
            'creator_id' => null,
            'company_id' => null,
            'code'       => 'PT_CSN',
            'is_active'  => true,
        ]);

        TransferDivision::factory()->create([
            'form_transfer_id' => $formTransfer->getKey(),
            'name'             => 'Finance',
        ]);
        TransferReferenceNote::factory()->create([
            'form_transfer_id' => $formTransfer->getKey(),
            'label'            => 'Transfer vendor',
        ]);
        TransferBank::factory()->create([
            'code'       => 'BCA-API',
            'name'       => 'Bank Central Asia',
            'short_name' => 'BCA',
            'is_active'  => true,
        ]);

        $this->getJson('/api/form-transfer/transfer-requests/PT_CSN')
            ->assertOk()
            ->assertJsonPath('data.code', 'PT_CSN')
            ->assertJsonPath('data.requirements.division_required', true)
            ->assertJsonPath('data.requirements.reference_note.restricted', true)
            ->assertJsonPath('data.requirements.recaptcha.action', 'form_transfer_request')
            ->assertJsonPath('data.references.divisions.0.name', 'Finance')
            ->assertJsonPath('data.references.reference_notes.0.value', 'Transfer vendor')
            ->assertJsonPath('data.references.banks.0.display_name', 'BCA');
    }

    public function test_public_api_submits_internal_transfer_request(): void
    {
        $formTransfer = FormTransfer::factory()->create([
            'creator_id' => null,
            'company_id' => null,
            'code'       => 'PT_CSN',
            'uid_prefix' => 'CSN',
            'is_active'  => true,
        ]);
        $bank = TransferBank::factory()->create([
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/form-transfer/transfer-requests/PT_CSN', [
            'email'           => 'REQUESTER@EXAMPLE.COM',
            'requester_name'  => 'Andi Saputra',
            'bank_id'         => $bank->getKey(),
            'account_number'  => '1234567890',
            'account_name'    => 'Andi Saputra',
            'transfer_amount' => '100000',
            'purpose'         => 'Operational expense',
            'reference_note'  => 'Urgent transfer',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Pengajuan transfer berhasil dibuat.')
            ->assertJsonPath('data.uid', 'CSN-00001')
            ->assertJsonPath('data.statuses.submission.value', TransferRequestSubmissionStatus::BARU->value)
            ->assertJsonPath('data.requester.email', 'requester@example.com');

        $this->assertDatabaseHas('form_transfer_requests', [
            'form_transfer_id' => $formTransfer->getKey(),
            'uid'              => 'CSN-00001',
            'email'            => 'requester@example.com',
            'reference_note'   => 'Urgent transfer',
        ]);
    }

    public function test_public_api_progress_lookup_supports_reference_email_and_response_token(): void
    {
        $transferRequest = TransferRequest::factory()->create([
            'uid'                => 'MAJU-00001',
            'email'              => 'requester@example.com',
            'status_response_id' => 'status-token-123',
        ]);

        $this->getJson('/api/form-transfer/transfer-requests/progress?email=requester@example.com&reference=maju-00001')
            ->assertOk()
            ->assertJsonPath('data.uid', 'MAJU-00001')
            ->assertJsonPath('data.status_response_id', 'status-token-123');

        $this->getJson('/api/form-transfer/transfer-requests/progress?email=requester@example.com')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.uid', 'MAJU-00001')
            ->assertJsonPath('meta.count', 1);

        $this->getJson('/api/form-transfer/transfer-requests/progress/'.$transferRequest->status_response_id)
            ->assertOk()
            ->assertJsonPath('data.uid', 'MAJU-00001');
    }

    public function test_public_api_redacts_approval_tokens_and_private_approver_data(): void
    {
        $transferRequest = TransferRequest::factory()->create([
            'uid'                => 'MAJU-00002',
            'email'              => 'approval-token-requester@example.com',
            'status_response_id' => 'status-token-redacted',
            'approvals'          => [[
                'label'       => 'Finance Approval',
                'name'        => 'Finance Manager',
                'email'       => 'finance.manager@example.com',
                'phone'       => '081234567890',
                'title'       => 'Finance Lead',
                'status'      => 'pending',
                'comments'    => 'Internal approver comment',
                'notes'       => 'Internal approver note',
                'task_id'     => 'approval-task-secret',
                'notified_at' => '2026-05-01T08:00:00+00:00',
                'noted_at'    => null,
            ]],
        ]);

        $response = $this->getJson('/api/form-transfer/transfer-requests/progress/'.$transferRequest->status_response_id)
            ->assertOk()
            ->assertJsonPath('data.approvals.0.label', 'Finance Approval')
            ->assertJsonPath('data.approvals.0.name', 'Finance Manager')
            ->assertJsonPath('data.approvals.0.title', 'Finance Lead')
            ->assertJsonPath('data.approvals.0.status', 'pending');

        $this->assertNull($response->json('data.approvals.0.task_id'));
        $this->assertNull($response->json('data.approvals.0.email'));
        $this->assertNull($response->json('data.approvals.0.phone'));
        $this->assertNull($response->json('data.approvals.0.comments'));
        $this->assertNull($response->json('data.approvals.0.notes'));
        $this->assertNull($response->json('data.approvals.0.notified_at'));

        $lookupResponse = $this->getJson('/api/form-transfer/transfer-requests/progress?email=approval-token-requester@example.com')
            ->assertOk()
            ->assertJsonPath('data.0.uid', 'MAJU-00002');

        $this->assertNull($lookupResponse->json('data.0.approvals.0.task_id'));
        $this->assertNull($lookupResponse->json('data.0.approvals.0.email'));
    }

    public function test_public_api_does_not_accept_external_entry_submission(): void
    {
        FormTransfer::factory()->create([
            'creator_id'          => null,
            'company_id'          => null,
            'code'                => 'EXT_TRANSFER',
            'public_entry_type'   => FormTransfer::PUBLIC_ENTRY_TYPE_EXTERNAL,
            'public_external_url' => 'https://example.com/form',
            'is_active'           => true,
        ]);

        $this->postJson('/api/form-transfer/transfer-requests/EXT_TRANSFER', [
            'email'           => 'requester@example.com',
            'requester_name'  => 'Andi Saputra',
            'bank_id'         => 1,
            'account_number'  => '1234567890',
            'account_name'    => 'Andi Saputra',
            'transfer_amount' => '100000',
            'purpose'         => 'Operational expense',
            'reference_note'  => 'Urgent transfer',
        ])->assertNotFound();
    }
}
