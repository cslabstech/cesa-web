<?php

namespace Cesa\FormTransfer\Tests\Feature;

use Cesa\FormTransfer\Livewire\PublicTransferProgressPage;
use Cesa\FormTransfer\Models\TransferRequest;
use Cesa\FormTransfer\Tests\FormTransferTestCase;
use Livewire\Livewire;

class PublicTransferProgressLookupTest extends FormTransferTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        require base_path('plugins/cesa/form-transfer/routes/web.php');

        $routes = app('router')->getRoutes();
        $routes->refreshNameLookups();
        $routes->refreshActionLookups();
    }

    public function test_public_progress_lookup_form_is_available_without_response_token(): void
    {
        $response = $this->get('/transfer-requests/progress');

        $response
            ->assertOk()
            ->assertSee('Cek progres pengajuan')
            ->assertSee('ID Referensi / ID Status Response')
            ->assertSee('Email');

        expect(substr_count($response->getContent(), 'x-data="{ expanded: true }"'))
            ->toBeGreaterThanOrEqual(1);
    }

    public function test_public_progress_lookup_redirects_to_token_url_when_reference_and_email_match(): void
    {
        $request = TransferRequest::factory()->create([
            'uid'                => 'MAJU-00001',
            'email'              => 'requester@example.com',
            'status_response_id' => 'status-token-123',
        ]);

        Livewire::test(PublicTransferProgressPage::class)
            ->set('lookupReference', 'maju-00001')
            ->set('lookupEmail', 'requester@example.com')
            ->call('lookup')
            ->assertRedirect(route('form-transfer.public.progress', [
                'response' => $request->status_response_id,
            ]));
    }

    public function test_public_progress_lookup_rejects_mismatched_email_for_specific_reference(): void
    {
        TransferRequest::factory()->create([
            'uid'                => 'MAJU-00002',
            'email'              => 'requester@example.com',
            'status_response_id' => 'status-token-456',
        ]);

        Livewire::test(PublicTransferProgressPage::class)
            ->set('lookupReference', 'MAJU-00002')
            ->set('lookupEmail', 'other@example.com')
            ->call('lookup')
            ->assertHasErrors(['lookupReference']);
    }

    public function test_public_progress_lookup_lists_all_requests_for_email_when_reference_is_empty(): void
    {
        $first = TransferRequest::factory()->create([
            'uid'                => 'MAJU-00004',
            'email'              => 'requester@example.com',
            'status_response_id' => 'status-token-004',
        ]);

        $second = TransferRequest::factory()->create([
            'uid'                => 'MAJU-00005',
            'email'              => 'requester@example.com',
            'status_response_id' => 'status-token-005',
        ]);

        TransferRequest::factory()->create([
            'uid'                => 'MAJU-00006',
            'email'              => 'other@example.com',
            'status_response_id' => 'status-token-006',
        ]);

        Livewire::test(PublicTransferProgressPage::class)
            ->set('lookupEmail', 'requester@example.com')
            ->call('lookup')
            ->assertSee('MAJU-00004')
            ->assertSee('MAJU-00005')
            ->assertDontSee('MAJU-00006')
            ->assertSee(route('form-transfer.public.progress', [
                'response' => $first->status_response_id,
            ], false))
            ->assertSee(route('form-transfer.public.progress', [
                'response' => $second->status_response_id,
            ], false));
    }

    public function test_public_progress_token_url_still_renders_request_status(): void
    {
        $request = TransferRequest::factory()->create([
            'uid'                => 'MAJU-00003',
            'account_number'     => '1234567890',
            'status_response_id' => 'status-token-789',
        ]);

        $response = $this->get('/transfer-requests/progress/'.$request->status_response_id);

        $response
            ->assertOk()
            ->assertSee('MAJU-00003')
            ->assertSee('Status saat ini');

        expect(substr_count($response->getContent(), 'x-data="{ expanded: true }"'))
            ->toBeGreaterThanOrEqual(2);
    }
}
