<?php

namespace Cesa\FormTransfer\Tests\Feature;

use Cesa\FormTransfer\Models\FormTransferPublicCategory;
use Cesa\FormTransfer\Tests\FormTransferTestCase;

class PublicCategoryIndexTest extends FormTransferTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        require base_path('plugins/cesa/form-transfer/routes/web.php');

        $routes = app('router')->getRoutes();
        $routes->refreshNameLookups();
        $routes->refreshActionLookups();
    }

    public function test_form_index_lists_active_categories_with_links_to_their_detail(): void
    {
        $retail = FormTransferPublicCategory::factory()->create([
            'name'       => 'Retail Category',
            'slug'       => 'retail',
            'sort_order' => 10,
            'is_active'  => true,
        ]);

        $distributor = FormTransferPublicCategory::factory()->create([
            'name'       => 'Distributor Category',
            'slug'       => 'distributor',
            'sort_order' => 20,
            'is_active'  => true,
        ]);

        FormTransferPublicCategory::factory()->create([
            'name'      => 'Hidden Category',
            'slug'      => 'hidden',
            'is_active' => false,
        ]);

        $this->get('/form')
            ->assertOk()
            ->assertSee('Retail Category')
            ->assertSee('Distributor Category')
            ->assertDontSee('Hidden Category')
            ->assertSee(route('form-transfer.public.dynamic-index', ['publicIndexSlug' => $retail->slug]), false)
            ->assertSee(route('form-transfer.public.dynamic-index', ['publicIndexSlug' => $distributor->slug]), false);
    }
}
