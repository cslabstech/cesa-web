<?php

namespace Cesa\FormTransfer\Tests\Feature;

use Cesa\FormTransfer\Models\FormTransfer;
use Cesa\FormTransfer\Tests\FormTransferTestCase;

class PublicTransferAffiliatePageTest extends FormTransferTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        require base_path('plugins/cesa/form-transfer/routes/web.php');

        $routes = app('router')->getRoutes();
        $routes->refreshNameLookups();
        $routes->refreshActionLookups();
    }

    public function test_public_affiliate_page_lists_only_active_links_in_sort_order(): void
    {
        FormTransfer::factory()->create([
            'name'                           => 'CV BOGA PERKASA',
            'public_entry_type'              => FormTransfer::PUBLIC_ENTRY_TYPE_EXTERNAL,
            'public_external_url'            => 'https://forms.gle/TwjJMixsPfLKsjRh6',
            'public_badge_label'             => 'Google Form',
            'public_sort_order'              => 20,
            'show_on_transfer_request_index' => false,
            'show_on_affiliate_index'        => true,
            'is_active'                      => true,
        ]);

        FormTransfer::factory()->create([
            'name'                           => 'PT. PEKANBARU',
            'public_entry_type'              => FormTransfer::PUBLIC_ENTRY_TYPE_EXTERNAL,
            'public_external_url'            => 'https://forms.gle/FJceibs3uXpGFbqP7',
            'public_badge_label'             => 'Google Form',
            'public_sort_order'              => 10,
            'show_on_transfer_request_index' => false,
            'show_on_affiliate_index'        => true,
            'is_active'                      => true,
        ]);

        FormTransfer::factory()->create([
            'name'                           => 'RESTO GABUNGAN',
            'public_entry_type'              => FormTransfer::PUBLIC_ENTRY_TYPE_EXTERNAL,
            'public_external_url'            => 'https://bit.ly/FORMPENGAJUANALLRESTO',
            'public_badge_label'             => 'Google Form',
            'public_sort_order'              => 30,
            'show_on_transfer_request_index' => false,
            'show_on_affiliate_index'        => true,
            'is_active'                      => false,
        ]);

        $response = $this->get('/afiliasi');

        $response
            ->assertOk()
            ->assertSeeInOrder([
                'PT. PEKANBARU',
                'CV BOGA PERKASA',
            ])
            ->assertDontSee('RESTO GABUNGAN')
            ->assertSee('https://forms.gle/FJceibs3uXpGFbqP7', false)
            ->assertSee('https://forms.gle/TwjJMixsPfLKsjRh6', false);
    }

    public function test_public_affiliate_page_shows_empty_state_when_no_active_links_exist(): void
    {
        $this->get('/afiliasi')
            ->assertOk()
            ->assertSee('Belum ada formulir transfer afiliasi yang tersedia saat ini.');
    }
}
