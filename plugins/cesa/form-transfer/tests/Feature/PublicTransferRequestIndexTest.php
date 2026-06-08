<?php

namespace Cesa\FormTransfer\Tests\Feature;

use Cesa\FormTransfer\Models\FormTransfer;
use Cesa\FormTransfer\Models\FormTransferPublicCategory;
use Cesa\FormTransfer\Tests\FormTransferTestCase;

class PublicTransferRequestIndexTest extends FormTransferTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        require base_path('plugins/cesa/form-transfer/routes/web.php');

        $routes = app('router')->getRoutes();
        $routes->refreshNameLookups();
        $routes->refreshActionLookups();
    }

    public function test_public_transfer_request_index_lists_internal_and_external_entries(): void
    {
        $internalForm = FormTransfer::factory()->create([
            'name'                           => 'FORM INTERNAL OPERASIONAL',
            'code'                           => 'FORM_INTERNAL_OPERASIONAL',
            'public_entry_type'              => FormTransfer::PUBLIC_ENTRY_TYPE_INTERNAL,
            'public_sort_order'              => 20,
            'show_on_transfer_request_index' => true,
            'show_on_affiliate_index'        => false,
            'is_active'                      => true,
        ]);

        FormTransfer::factory()->create([
            'name'                           => 'FORM GOOGLE RESTO',
            'public_entry_type'              => FormTransfer::PUBLIC_ENTRY_TYPE_EXTERNAL,
            'public_external_url'            => 'https://forms.gle/example-resto',
            'public_badge_label'             => 'Google Form',
            'public_sort_order'              => 10,
            'show_on_transfer_request_index' => true,
            'show_on_affiliate_index'        => false,
            'is_active'                      => true,
        ]);

        FormTransfer::factory()->create([
            'name'                           => 'FORM KHUSUS AFILIASI',
            'public_entry_type'              => FormTransfer::PUBLIC_ENTRY_TYPE_EXTERNAL,
            'public_external_url'            => 'https://forms.gle/affiliate-only',
            'public_sort_order'              => 30,
            'show_on_transfer_request_index' => false,
            'show_on_affiliate_index'        => true,
            'is_active'                      => true,
        ]);

        $this->get('/transfer-requests')
            ->assertRedirect(route('form-transfer.public.dynamic-index', [
                'publicIndexSlug' => FormTransfer::PUBLIC_INDEX_TRANSFER_REQUESTS,
            ]));

        $response = $this->get('/form/transfer-requests');

        $response
            ->assertOk()
            ->assertSeeInOrder([
                'FORM GOOGLE RESTO',
                'FORM INTERNAL OPERASIONAL',
            ])
            ->assertDontSee('FORM KHUSUS AFILIASI')
            ->assertSee('https://forms.gle/example-resto', false)
            ->assertSee(route('form-transfer.public.form', $internalForm->code), false);
    }

    public function test_public_transfer_request_index_can_be_grouped_by_dynamic_category_slug(): void
    {
        $retail = FormTransferPublicCategory::factory()->create([
            'name' => 'Retail',
            'slug' => 'retail',
        ]);
        $distributor = FormTransferPublicCategory::factory()->create([
            'name' => 'Distributor',
            'slug' => 'distributor',
        ]);

        $retailForm = FormTransfer::factory()->create([
            'name'                => 'FORM RETAIL STORE',
            'public_entry_type'   => FormTransfer::PUBLIC_ENTRY_TYPE_EXTERNAL,
            'public_external_url' => 'https://forms.gle/retail-store',
            'public_sort_order'   => 10,
            'is_active'           => true,
        ]);
        $retailForm->publicCategories()->attach($retail);

        $distributorForm = FormTransfer::factory()->create([
            'name'                => 'FORM DISTRIBUTOR',
            'public_entry_type'   => FormTransfer::PUBLIC_ENTRY_TYPE_EXTERNAL,
            'public_external_url' => 'https://forms.gle/distributor',
            'public_sort_order'   => 20,
            'is_active'           => true,
        ]);
        $distributorForm->publicCategories()->attach($distributor);

        $this->get('/form/retail')
            ->assertOk()
            ->assertSee('RETAIL')
            ->assertSee('FORM RETAIL STORE')
            ->assertDontSee('FORM DISTRIBUTOR')
            ->assertSee('https://forms.gle/retail-store', false);

        $this->get('/retail')
            ->assertNotFound();

        $this->get('/catalog/retail')
            ->assertNotFound();
    }

    public function test_unknown_public_category_slug_returns_not_found(): void
    {
        $this->get('/form/unknown-category')
            ->assertNotFound();
    }
}
