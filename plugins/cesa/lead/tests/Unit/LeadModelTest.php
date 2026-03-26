<?php

namespace Cesa\Lead\Tests\Unit;

use Cesa\Lead\Models\Lead;
use Cesa\Lead\Tests\TestCase;
use Illuminate\Support\Carbon;
use Webkul\Security\Models\User;

class LeadModelTest extends TestCase
{
    public function test_lead_can_be_created(): void
    {
        $lead = Lead::factory()->create([
            'name'  => 'john doe',
            'phone' => '08123456789',
        ]);

        $this->assertDatabaseHas('leads', [
            'id'    => $lead->id,
            'name'  => 'JOHN DOE',
            'phone' => '628123456789',
        ]);
    }

    public function test_name_is_converted_to_uppercase(): void
    {
        $lead = new Lead;
        $lead->name = 'john doe';

        $this->assertEquals('JOHN DOE', $lead->name);
    }

    public function test_name_handles_multibyte_characters(): void
    {
        $lead = new Lead;
        $lead->name = 'budi santoso';

        $this->assertEquals('BUDI SANTOSO', $lead->name);
    }

    public function test_name_handles_special_characters(): void
    {
        $lead = new Lead;
        $lead->name = "o'brien";

        $this->assertEquals("O'BRIEN", $lead->name);
    }

    public function test_phone_normalizes_from_08_format(): void
    {
        $lead = new Lead;
        $lead->phone = '08123456789';

        $this->assertEquals('628123456789', $lead->phone);
    }

    public function test_phone_normalizes_from_62_format(): void
    {
        $lead = new Lead;
        $lead->phone = '628123456789';

        $this->assertEquals('628123456789', $lead->phone);
    }

    public function test_phone_normalizes_from_620_format(): void
    {
        $lead = new Lead;
        $lead->phone = '6208123456789';

        $this->assertEquals('628123456789', $lead->phone);
    }

    public function test_phone_normalizes_from_0062_format(): void
    {
        $lead = new Lead;
        $lead->phone = '006208123456789';

        $this->assertEquals('628123456789', $lead->phone);
    }

    public function test_phone_normalizes_from_00_format(): void
    {
        $lead = new Lead;
        $lead->phone = '008123456789';

        $this->assertEquals('628123456789', $lead->phone);
    }

    public function test_phone_normalizes_from_8_format(): void
    {
        $lead = new Lead;
        $lead->phone = '8123456789';

        $this->assertEquals('628123456789', $lead->phone);
    }

    public function test_phone_removes_non_digit_characters(): void
    {
        $lead = new Lead;
        $lead->phone = '+62-812-3456-789';

        $this->assertEquals('628123456789', $lead->phone);
    }

    public function test_phone_handles_spaces(): void
    {
        $lead = new Lead;
        $lead->phone = '0812 3456 789';

        $this->assertEquals('628123456789', $lead->phone);
    }

    public function test_phone_handles_parentheses(): void
    {
        $lead = new Lead;
        $lead->phone = '(0812) 3456-789';

        $this->assertEquals('628123456789', $lead->phone);
    }

    public function test_phone_handles_plus_sign_with_62(): void
    {
        $lead = new Lead;
        $lead->phone = '+628123456789';

        $this->assertEquals('628123456789', $lead->phone);
    }

    public function test_phone_handles_dots(): void
    {
        $lead = new Lead;
        $lead->phone = '0812.3456.789';

        $this->assertEquals('628123456789', $lead->phone);
    }

    public function test_phone_handles_mixed_separators(): void
    {
        $lead = new Lead;
        $lead->phone = '+62 (812) 3456-789';

        $this->assertEquals('628123456789', $lead->phone);
    }

    public function test_phone_normalization_handles_very_long_number(): void
    {
        $lead = new Lead;
        $lead->phone = '00628123456789012345';

        $this->assertEquals('628123456789012345', $lead->phone);
    }

    public function test_phone_normalization_handles_short_number(): void
    {
        $lead = new Lead;
        $lead->phone = '081234';

        $this->assertEquals('6281234', $lead->phone);
    }

    public function test_phone_normalization_preserves_already_correct_format(): void
    {
        $lead = new Lead;
        $lead->phone = '628987654321';

        $this->assertEquals('628987654321', $lead->phone);
    }

    public function test_phone_normalization_handles_only_digits(): void
    {
        $lead = new Lead;
        $lead->phone = 'abc123def456ghi';

        $this->assertEquals('62123456', $lead->phone);
    }

    public function test_lead_has_created_by_relationship(): void
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->create([
            'created_by' => $user->id,
        ]);

        $this->assertInstanceOf(User::class, $lead->createdBy);
        $this->assertEquals($user->id, $lead->createdBy->id);
    }

    public function test_lead_created_by_relationship_returns_null_when_no_creator(): void
    {
        $lead = Lead::factory()->create([
            'created_by' => null,
        ]);

        $this->assertNull($lead->createdBy);
    }

    public function test_lead_created_by_relationship_returns_null_when_user_deleted(): void
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->create([
            'created_by' => $user->id,
        ]);

        $user->delete();
        $lead->refresh();

        $this->assertNull($lead->createdBy);
    }

    public function test_lead_can_be_soft_deleted(): void
    {
        $lead = Lead::factory()->create();
        $leadId = $lead->id;

        $lead->delete();

        $this->assertSoftDeleted('leads', ['id' => $leadId]);
    }

    public function test_soft_deleted_lead_can_be_restored(): void
    {
        $lead = Lead::factory()->create();
        $lead->delete();

        $lead->restore();

        $this->assertDatabaseHas('leads', [
            'id'         => $lead->id,
            'deleted_at' => null,
        ]);
    }

    public function test_soft_deleted_leads_are_not_in_default_query(): void
    {
        Lead::factory()->create(['name' => 'active lead']);
        $deletedLead = Lead::factory()->create(['name' => 'deleted lead']);
        $deletedLead->delete();

        $leads = Lead::all();

        $this->assertCount(1, $leads);
        $this->assertEquals('ACTIVE LEAD', $leads->first()->name);
    }

    public function test_can_query_only_trashed_leads(): void
    {
        Lead::factory()->create(['name' => 'active lead']);
        $deletedLead = Lead::factory()->create(['name' => 'deleted lead']);
        $deletedLead->delete();

        $trashedLeads = Lead::onlyTrashed()->get();

        $this->assertCount(1, $trashedLeads);
        $this->assertEquals('DELETED LEAD', $trashedLeads->first()->name);
    }

    public function test_can_query_with_trashed_leads(): void
    {
        Lead::factory()->create(['name' => 'active lead']);
        $deletedLead = Lead::factory()->create(['name' => 'deleted lead']);
        $deletedLead->delete();

        $allLeads = Lead::withTrashed()->get();

        $this->assertCount(2, $allLeads);
    }

    public function test_fillable_attributes_can_be_mass_assigned(): void
    {
        $user = User::factory()->create();
        $attributes = [
            'name'                         => 'john doe',
            'phone'                        => '08123456789',
            'address'                      => 'Jl. Test No. 123',
            'sales_person'                 => 'Jane Doe',
            'store_team_position'          => 'Kepala Toko',
            'store_branch'                 => 'Complete Selular Babakan',
            'phone_transaction_range'      => 'Harga di bawah 2 juta',
            'created_by'                   => $user->id,
        ];

        $lead = Lead::create($attributes);

        $this->assertDatabaseHas('leads', [
            'id'      => $lead->id,
            'name'    => 'JOHN DOE',
            'phone'   => '628123456789',
            'address' => 'Jl. Test No. 123',
        ]);
    }

    public function test_casts_dates_to_datetime(): void
    {
        $lead = Lead::factory()->create();

        $this->assertInstanceOf(Carbon::class, $lead->created_at);
        $this->assertInstanceOf(Carbon::class, $lead->updated_at);
    }

    public function test_deleted_at_is_cast_to_datetime_when_soft_deleted(): void
    {
        $lead = Lead::factory()->create();
        $lead->delete();

        $lead = Lead::withTrashed()->find($lead->id);

        $this->assertInstanceOf(Carbon::class, $lead->deleted_at);
    }

    public function test_factory_creates_lead_with_valid_data(): void
    {
        $lead = Lead::factory()->create();

        $this->assertNotNull($lead->name);
        $this->assertNotNull($lead->phone);
        $this->assertNotNull($lead->address);
        $this->assertNotNull($lead->sales_person);
        $this->assertNotNull($lead->store_team_position);
        $this->assertNotNull($lead->store_branch);
        $this->assertContains($lead->store_team_position->value, ['Kepala Toko', 'Promotor', 'Kasir', 'Frontliner']);
    }

    public function test_phone_update_normalizes_value(): void
    {
        $lead = Lead::factory()->create(['phone' => '08111111111']);

        $lead->update(['phone' => '08222222222']);

        $this->assertEquals('628222222222', $lead->phone);
        $this->assertDatabaseHas('leads', [
            'id'    => $lead->id,
            'phone' => '628222222222',
        ]);
    }

    public function test_name_update_converts_to_uppercase(): void
    {
        $lead = Lead::factory()->create(['name' => 'old name']);

        $lead->update(['name' => 'new name']);

        $this->assertEquals('NEW NAME', $lead->name);
        $this->assertDatabaseHas('leads', [
            'id'   => $lead->id,
            'name' => 'NEW NAME',
        ]);
    }

    public function test_multiple_leads_with_different_phones_can_exist(): void
    {
        Lead::factory()->create(['phone' => '08111111111']);
        Lead::factory()->create(['phone' => '08222222222']);
        Lead::factory()->create(['phone' => '08333333333']);

        $this->assertDatabaseCount('leads', 3);
    }

    public function test_force_delete_permanently_removes_lead(): void
    {
        $lead = Lead::factory()->create();
        $leadId = $lead->id;

        $lead->forceDelete();

        $this->assertDatabaseMissing('leads', ['id' => $leadId]);
        $this->assertNull(Lead::withTrashed()->find($leadId));
    }
}
