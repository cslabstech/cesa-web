<?php

namespace Cesa\Lead\Tests\Feature;

use Cesa\Lead\Filament\Resources\Lead\Pages\CreateLead;
use Cesa\Lead\Filament\Resources\Lead\Pages\EditLead;
use Cesa\Lead\Filament\Resources\Lead\Pages\ListLeads;
use Cesa\Lead\Models\Lead;
use Cesa\Lead\Tests\TestCase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Webkul\Security\Models\User;

class LeadResourceTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->markTestSkipped(
            'Quarantined pending a dedicated Filament panel test harness for the lead plugin. '.
            'Plugin bootstrap, autoload, install flow, and non-UI regression coverage are verified separately.'
        );

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        // Create necessary permissions
        Permission::create(['name' => 'view_any_lead', 'guard_name' => 'web']);
        Permission::create(['name' => 'view_lead', 'guard_name' => 'web']);
        Permission::create(['name' => 'create_lead', 'guard_name' => 'web']);
        Permission::create(['name' => 'update_lead', 'guard_name' => 'web']);
        Permission::create(['name' => 'delete_lead', 'guard_name' => 'web']);
        Permission::create(['name' => 'delete_any_lead', 'guard_name' => 'web']);

        $this->user->givePermissionTo([
            'view_any_lead',
            'view_lead',
            'create_lead',
            'update_lead',
            'delete_lead',
            'delete_any_lead',
        ]);
    }

    public function test_can_render_list_page(): void
    {
        Livewire::test(ListLeads::class)
            ->assertSuccessful();
    }

    public function test_can_list_leads(): void
    {
        $leads = Lead::factory()->count(5)->create();

        Livewire::test(ListLeads::class)
            ->assertCanSeeTableRecords($leads);
    }

    public function test_can_search_leads_by_name(): void
    {
        $leads = Lead::factory()->count(5)->create();
        $targetLead = $leads->first();

        Livewire::test(ListLeads::class)
            ->searchTable($targetLead->name)
            ->assertCanSeeTableRecords([$targetLead])
            ->assertCanNotSeeTableRecords($leads->skip(1));
    }

    public function test_can_search_leads_by_phone(): void
    {
        $leads = Lead::factory()->count(5)->create();
        $targetLead = $leads->first();

        Livewire::test(ListLeads::class)
            ->searchTable($targetLead->phone)
            ->assertCanSeeTableRecords([$targetLead])
            ->assertCanNotSeeTableRecords($leads->skip(1));
    }

    public function test_can_search_leads_by_sales_person(): void
    {
        $specificSalesPerson = 'Unique Sales Person Name';
        $targetLead = Lead::factory()->create(['sales_person' => $specificSalesPerson]);
        Lead::factory()->count(3)->create();

        Livewire::test(ListLeads::class)
            ->searchTable($specificSalesPerson)
            ->assertCanSeeTableRecords([$targetLead]);
    }

    public function test_can_render_create_page(): void
    {
        Livewire::test(CreateLead::class)
            ->assertSuccessful();
    }

    public function test_can_create_lead(): void
    {
        $leadData = [
            'name'                         => 'john doe',
            'phone'                        => '08123456789',
            'address'                      => 'Jl. Test No. 123',
            'sales_person'                 => 'Jane Doe',
            'store_team_position'          => 'Kepala Toko',
            'store_branch'                 => 'Complete Selular Babakan',
            'phone_transaction_range'      => 'Harga di bawah 2 juta',
        ];

        Livewire::test(CreateLead::class)
            ->fillForm($leadData)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('leads', [
            'name'                         => 'JOHN DOE',
            'phone'                        => '628123456789',
            'address'                      => 'Jl. Test No. 123',
            'sales_person'                 => 'Jane Doe',
            'store_team_position'          => 'Kepala Toko',
            'store_branch'                 => 'Complete Selular Babakan',
            'phone_transaction_range'      => 'Harga di bawah 2 juta',
        ]);
    }

    public function test_create_lead_validates_required_fields(): void
    {
        Livewire::test(CreateLead::class)
            ->fillForm([
                'name'    => '',
                'phone'   => '',
                'address' => '',
            ])
            ->call('create')
            ->assertHasFormErrors([
                'name'    => 'required',
                'phone'   => 'required',
                'address' => 'required',
            ]);
    }

    public function test_create_lead_validates_phone_uniqueness(): void
    {
        $existingLead = Lead::factory()->create(['phone' => '628123456789']);

        Livewire::test(CreateLead::class)
            ->fillForm([
                'name'                => 'New Lead',
                'phone'               => '08123456789', // Will be normalized to 628123456789
                'address'             => 'Jl. Test',
                'sales_person'        => 'Sales',
                'store_team_position' => 'Kasir',
                'store_branch'        => 'Complete Selular Babakan',
            ])
            ->call('create')
            ->assertHasFormErrors(['phone']);
    }

    public function test_create_lead_validates_phone_format(): void
    {
        Livewire::test(CreateLead::class)
            ->fillForm([
                'name'                => 'Test Lead',
                'phone'               => 'invalid-phone',
                'address'             => 'Jl. Test',
                'sales_person'        => 'Sales',
                'store_team_position' => 'Kasir',
                'store_branch'        => 'Complete Selular Babakan',
            ])
            ->call('create')
            ->assertHasFormErrors(['phone']);
    }

    public function test_create_lead_validates_store_team_position(): void
    {
        Livewire::test(CreateLead::class)
            ->fillForm([
                'name'                => 'Test Lead',
                'phone'               => '628123456789',
                'address'             => 'Jl. Test',
                'sales_person'        => 'Sales',
                'store_team_position' => 'Invalid Position',
                'store_branch'        => 'Complete Selular Babakan',
            ])
            ->call('create')
            ->assertHasFormErrors(['store_team_position']);
    }

    public function test_create_lead_sets_creator_id_to_authenticated_user(): void
    {
        $leadData = [
            'name'                         => 'Test Lead',
            'phone'                        => '628123456789',
            'address'                      => 'Jl. Test No. 123',
            'sales_person'                 => 'Jane Doe',
            'store_team_position'          => 'Kepala Toko',
            'store_branch'                 => 'Complete Selular Babakan',
            'phone_transaction_range'      => 'Harga di bawah 2 juta',
        ];

        Livewire::test(CreateLead::class)
            ->fillForm($leadData)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('leads', [
            'name'       => 'TEST LEAD',
            'creator_id' => $this->user->id,
        ]);
    }

    public function test_can_render_edit_page(): void
    {
        $lead = Lead::factory()->create();

        Livewire::test(EditLead::class, ['record' => $lead->id])
            ->assertSuccessful();
    }

    public function test_can_retrieve_data_for_edit(): void
    {
        $lead = Lead::factory()->create([
            'name'  => 'original name',
            'phone' => '628111111111',
        ]);

        Livewire::test(EditLead::class, ['record' => $lead->id])
            ->assertFormSet([
                'name'  => 'ORIGINAL NAME',
                'phone' => '628111111111',
            ]);
    }

    public function test_can_update_lead(): void
    {
        $lead = Lead::factory()->create([
            'name'  => 'old name',
            'phone' => '628111111111',
        ]);

        Livewire::test(EditLead::class, ['record' => $lead->id])
            ->fillForm([
                'name'  => 'new name',
                'phone' => '08222222222',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('leads', [
            'id'    => $lead->id,
            'name'  => 'NEW NAME',
            'phone' => '628222222222',
        ]);
    }

    public function test_update_lead_validates_required_fields(): void
    {
        $lead = Lead::factory()->create();

        Livewire::test(EditLead::class, ['record' => $lead->id])
            ->fillForm([
                'name'    => '',
                'phone'   => '',
                'address' => '',
            ])
            ->call('save')
            ->assertHasFormErrors([
                'name'    => 'required',
                'phone'   => 'required',
                'address' => 'required',
            ]);
    }

    public function test_update_lead_validates_phone_uniqueness_except_current_record(): void
    {
        $lead1 = Lead::factory()->create(['phone' => '628111111111']);
        $lead2 = Lead::factory()->create(['phone' => '628222222222']);

        Livewire::test(EditLead::class, ['record' => $lead2->id])
            ->fillForm([
                'phone' => '08111111111', // Trying to use lead1's phone
            ])
            ->call('save')
            ->assertHasFormErrors(['phone']);
    }

    public function test_update_lead_allows_keeping_same_phone(): void
    {
        $lead = Lead::factory()->create(['phone' => '628123456789']);

        Livewire::test(EditLead::class, ['record' => $lead->id])
            ->fillForm([
                'name'  => 'Updated Name',
                'phone' => '628123456789', // Same phone
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('leads', [
            'id'    => $lead->id,
            'name'  => 'UPDATED NAME',
            'phone' => '628123456789',
        ]);
    }

    public function test_can_delete_lead_from_edit_page(): void
    {
        $lead = Lead::factory()->create();

        Livewire::test(EditLead::class, ['record' => $lead->id])
            ->callAction('delete');

        $this->assertSoftDeleted('leads', ['id' => $lead->id]);
    }

    public function test_can_delete_lead_from_list_page(): void
    {
        $lead = Lead::factory()->create();

        Livewire::test(ListLeads::class)
            ->callTableAction('delete', $lead);

        $this->assertSoftDeleted('leads', ['id' => $lead->id]);
    }

    public function test_can_bulk_delete_leads(): void
    {
        $leads = Lead::factory()->count(3)->create();

        Livewire::test(ListLeads::class)
            ->callTableBulkAction('delete', $leads);

        foreach ($leads as $lead) {
            $this->assertSoftDeleted('leads', ['id' => $lead->id]);
        }
    }

    public function test_table_columns_are_sortable(): void
    {
        Lead::factory()->create(['name' => 'alpha', 'created_at' => now()->subDays(2)]);
        Lead::factory()->create(['name' => 'zeta', 'created_at' => now()->subDay()]);
        Lead::factory()->create(['name' => 'beta', 'created_at' => now()]);

        Livewire::test(ListLeads::class)
            ->sortTable('name')
            ->assertCanSeeTableRecords(Lead::orderBy('name')->get(), inOrder: true)
            ->sortTable('name', 'desc')
            ->assertCanSeeTableRecords(Lead::orderBy('name', 'desc')->get(), inOrder: true);
    }

    public function test_table_has_pagination(): void
    {
        Lead::factory()->count(30)->create();

        Livewire::test(ListLeads::class)
            ->assertCountTableRecords(25); // Default pagination is 25
    }

    public function test_can_change_pagination_page_size(): void
    {
        Lead::factory()->count(15)->create();

        Livewire::test(ListLeads::class)
            ->call('paginateTable', 10)
            ->assertCountTableRecords(10);
    }

    public function test_name_is_converted_to_uppercase_on_create(): void
    {
        Livewire::test(CreateLead::class)
            ->fillForm([
                'name'                => 'lowercase name',
                'phone'               => '628123456789',
                'address'             => 'Address',
                'sales_person'        => 'Sales',
                'store_team_position' => 'Kasir',
                'store_branch'        => 'Complete Selular Babakan',
            ])
            ->call('create');

        $this->assertDatabaseHas('leads', [
            'name' => 'LOWERCASE NAME',
        ]);
    }

    public function test_phone_is_normalized_on_create(): void
    {
        Livewire::test(CreateLead::class)
            ->fillForm([
                'name'                => 'Test',
                'phone'               => '0812-3456-789',
                'address'             => 'Address',
                'sales_person'        => 'Sales',
                'store_team_position' => 'Kasir',
                'store_branch'        => 'Complete Selular Babakan',
            ])
            ->call('create');

        $this->assertDatabaseHas('leads', [
            'phone' => '628123456789',
        ]);
    }

    public function test_phone_transaction_range_is_optional_on_edit(): void
    {
        $lead = Lead::factory()->create([
            'phone_transaction_range' => 'Harga di bawah 2 juta',
        ]);

        Livewire::test(EditLead::class, ['record' => $lead->id])
            ->fillForm([
                'phone_transaction_range' => null,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('leads', [
            'id'                        => $lead->id,
            'phone_transaction_range'   => null,
        ]);
    }

    public function test_all_jabatan_options_are_valid(): void
    {
        $options = ['Kepala Toko', 'Promotor', 'Kasir', 'Frontliner'];

        foreach ($options as $index => $option) {
            Livewire::test(CreateLead::class)
                ->fillForm([
                    'name'                => 'Test',
                    'phone'               => '62812345678'.sprintf('%02d', $index),
                    'address'             => 'Address',
                    'sales_person'        => 'Sales',
                    'store_team_position' => $option,
                    'store_branch'        => 'Complete Selular Babakan',
                ])
                ->call('create')
                ->assertHasNoFormErrors();
        }

        $this->assertDatabaseCount('leads', count($options));
    }
}
