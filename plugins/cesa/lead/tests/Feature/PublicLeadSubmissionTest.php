<?php

namespace Cesa\Lead\Tests\Feature;

use Cesa\Lead\Livewire\PublicLeadForm;
use Cesa\Lead\Models\Lead;
use Cesa\Lead\Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

class PublicLeadSubmissionTest extends TestCase
{
    public function test_can_render_public_lead_form_page(): void
    {
        $this->get('/lead')
            ->assertOk();
    }

    public function test_legacy_public_lead_url_redirects_to_new_path(): void
    {
        $this->get('/leads')
            ->assertRedirect('/lead');
    }

    public function test_can_submit_public_lead_form_and_persist_lead(): void
    {
        $component = Livewire::test(PublicLeadForm::class)
            ->set('data.name', 'john doe')
            ->set('data.phone', '08123456789')
            ->set('data.address', 'Jl. Test No. 123')
            ->set('data.sales_person', 'Jane Doe')
            ->set('data.store_team_position', 'Kepala Toko')
            ->set('data.store_branch', 'Complete Selular Babakan')
            ->set('data.phone_transaction_range', 'Harga di bawah 2 juta')
            ->call('submit')
            ->assertHasNoErrors();

        $lead = Lead::query()->firstOrFail();

        $component->assertRedirect($lead->getPublicProgressUrl());

        $this->assertDatabaseHas('leads', [
            'name'                => 'JOHN DOE',
            'phone'               => '628123456789',
            'address'             => 'Jl. Test No. 123',
            'sales_person'        => 'Jane Doe',
            'store_team_position' => 'Kepala Toko',
            'store_branch'        => 'Complete Selular Babakan',
            'public_response_id'  => $lead->public_response_id,
            'created_by'          => null,
        ]);
    }

    public function test_can_render_public_lead_progress_page(): void
    {
        $lead = Lead::factory()->create([
            'public_response_id' => '01jqqqqqqqqqqqqqqqqqqqqqqq',
        ]);

        $this->get('/lead/'.$lead->public_response_id)
            ->assertOk()
            ->assertSee($lead->name);
    }

    public function test_public_lead_form_rejects_duplicate_phone_after_normalization(): void
    {
        Lead::factory()->create(['phone' => '628123456789']);

        Livewire::test(PublicLeadForm::class)
            ->set('data.name', 'New Lead')
            ->set('data.phone', '08123456789')
            ->set('data.address', 'Jl. Duplicate')
            ->set('data.sales_person', 'Sales')
            ->set('data.store_team_position', 'Kasir')
            ->set('data.store_branch', 'Complete Selular Babakan')
            ->call('submit')
            ->assertHasErrors(['data.phone']);
    }

    public function test_can_validate_whatsapp_number_via_fonnte(): void
    {
        config([
            'lead.whatsapp_validation.enabled'                 => true,
            'lead.whatsapp_validation.provider'                => 'fonnte',
            'lead.whatsapp_validation.endpoint'                => 'https://api.fonnte.com/validate',
            'lead.whatsapp_validation.token'                   => 'test-token',
            'lead.whatsapp_validation.country_code'            => '62',
            'lead.whatsapp_validation.allow_manual_fallback'   => true,
            'lead.whatsapp_validation.rate_limit.max_attempts' => 0,
            'lead.whatsapp_validation.rate_limit.decay'        => 0,
        ]);

        Http::fake([
            'api.fonnte.com/validate' => Http::response([
                'status'         => true,
                'registered'     => ['628123456789'],
                'not_registered' => [],
                'invalid'        => [],
                'message'        => 'success',
            ], 200),
        ]);

        Livewire::test(PublicLeadForm::class)
            ->set('data.phone', '08123456789')
            ->call('checkWhatsAppValidation')
            ->assertSet('whatsappValidationStatus', 'success')
            ->assertHasNoErrors(['data.phone']);
    }

    public function test_whatsapp_validation_can_block_submission_when_manual_fallback_disabled(): void
    {
        config([
            'lead.whatsapp_validation.enabled'                 => true,
            'lead.whatsapp_validation.provider'                => 'fonnte',
            'lead.whatsapp_validation.endpoint'                => 'https://api.fonnte.com/validate',
            'lead.whatsapp_validation.token'                   => 'test-token',
            'lead.whatsapp_validation.country_code'            => '62',
            'lead.whatsapp_validation.allow_manual_fallback'   => false,
            'lead.whatsapp_validation.rate_limit.max_attempts' => 0,
            'lead.whatsapp_validation.rate_limit.decay'        => 0,
        ]);

        Http::fake([
            'api.fonnte.com/validate' => Http::response([
                'status'         => true,
                'registered'     => [],
                'not_registered' => ['628123456789'],
                'invalid'        => [],
                'message'        => 'success',
            ], 200),
        ]);

        Livewire::test(PublicLeadForm::class)
            ->set('data.phone', '08123456789')
            ->call('checkWhatsAppValidation')
            ->assertSet('whatsappValidationStatus', 'not_registered')
            ->assertHasErrors(['data.phone']);
    }
}
