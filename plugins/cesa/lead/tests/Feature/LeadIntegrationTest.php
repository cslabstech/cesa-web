<?php

namespace Cesa\Lead\Tests\Feature;

use Cesa\Lead\Models\Lead;
use Cesa\Lead\Tests\TestCase;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Webkul\Security\Models\User;

class LeadIntegrationTest extends TestCase
{
    public function test_lead_creation_with_all_fields(): void
    {
        $user = User::factory()->create();

        $lead = Lead::create([
            'name'                         => 'john doe',
            'phone'                        => '08123456789',
            'address'                      => 'Jl. Test No. 123, Jakarta',
            'sales_person'                 => 'Jane Doe',
            'store_team_position'          => 'Kepala Toko',
            'store_branch'                 => 'Complete Selular Babakan',
            'phone_transaction_range'      => 'Harga di bawah 2 juta',
            'creator_id'                   => $user->id,
        ]);

        $this->assertNotNull($lead->id);
        $this->assertEquals('JOHN DOE', $lead->name);
        $this->assertEquals('628123456789', $lead->phone);
        $this->assertEquals('Jl. Test No. 123, Jakarta', $lead->address);
        $this->assertEquals('Jane Doe', $lead->sales_person);
        $this->assertEquals('Kepala Toko', $lead->store_team_position->value);
        $this->assertEquals('Complete Selular Babakan', $lead->store_branch);
        $this->assertEquals('Harga di bawah 2 juta', $lead->phone_transaction_range->value);
        $this->assertEquals($user->id, $lead->creator_id);
    }

    public function test_concurrent_phone_normalization_creates_unique_records(): void
    {
        $phone1 = Lead::factory()->create(['phone' => '08123456789']);
        $phone2 = Lead::factory()->create(['phone' => '08987654321']);

        $this->assertEquals('628123456789', $phone1->phone);
        $this->assertEquals('628987654321', $phone2->phone);
        $this->assertNotEquals($phone1->phone, $phone2->phone);
    }

    public function test_lead_with_null_phone_transaction_range(): void
    {
        $lead = Lead::factory()->create([
            'phone_transaction_range' => null,
        ]);

        $this->assertNull($lead->phone_transaction_range);
        $this->assertDatabaseHas('leads', [
            'id'                        => $lead->id,
            'phone_transaction_range'   => null,
        ]);
    }

    public function test_lead_timestamps_are_set_correctly(): void
    {
        $before = now()->subSecond();
        $lead = Lead::factory()->create();
        $after = now()->addSecond();

        $this->assertTrue($lead->created_at->between($before, $after));
        $this->assertTrue($lead->updated_at->between($before, $after));
    }

    public function test_lead_updated_at_changes_on_update(): void
    {
        $lead = Lead::factory()->create();
        $originalUpdatedAt = $lead->updated_at;

        sleep(1);

        $lead->update(['name' => 'updated name']);

        $this->assertTrue($lead->updated_at->greaterThan($originalUpdatedAt));
    }

    public function test_lead_cascade_delete_behavior_with_user(): void
    {
        $this->markTestSkipped(
            'SQLite does not enforce foreign key constraints by default. '.
            'This test verifies nullOnDelete() behavior which works in production (MySQL/PostgreSQL) '.
            'but requires additional configuration in SQLite test environment.'
        );

        $user = User::factory()->create();
        $lead = Lead::factory()->create(['creator_id' => $user->id]);

        $this->assertEquals($user->id, $lead->creator->id);

        $user->delete();
        $lead->refresh();

        // Migration uses nullOnDelete(), so creator_id should be set to null
        $this->assertNull($lead->creator_id);
        $this->assertNull($lead->creator);
    }

    public function test_multiple_leads_same_store_branch(): void
    {
        Lead::factory()->count(5)->create(['store_branch' => 'Complete Selular Babakan']);

        $leads = Lead::where('store_branch', 'Complete Selular Babakan')->get();

        $this->assertCount(5, $leads);
    }

    public function test_multiple_leads_same_store_team_position(): void
    {
        Lead::factory()->count(3)->create(['store_team_position' => 'Kasir']);

        $leads = Lead::where('store_team_position', 'Kasir')->get();

        $this->assertCount(3, $leads);
    }

    public function test_query_leads_by_created_at_range(): void
    {
        $old = Lead::factory()->create(['created_at' => now()->subWeek()]);
        $recent = Lead::factory()->create(['created_at' => now()]);

        $leads = Lead::whereBetween('created_at', [now()->subDay(), now()->addDay()])->get();

        $this->assertTrue($leads->contains($recent));
        $this->assertFalse($leads->contains($old));
    }

    public function test_lead_database_indexes_exist(): void
    {
        $this->markTestSkipped(
            'Index introspection query (SHOW INDEXES) is MySQL-specific and not compatible with SQLite. '.
            'Indexes are properly defined in migration and work in production database.'
        );

        $indexes = DB::select('SHOW INDEXES FROM leads');

        $indexNames = collect($indexes)->pluck('Key_name')->unique();

        $this->assertTrue($indexNames->contains('leads_phone_unique'));
        $this->assertTrue($indexNames->contains('leads_creator_id_foreign'));
    }

    public function test_soft_delete_maintains_data_integrity(): void
    {
        $lead = Lead::factory()->create([
            'name'  => 'test lead',
            'phone' => '628123456789',
        ]);

        $leadId = $lead->id;
        $lead->delete();

        $this->assertDatabaseHas('leads', [
            'id'    => $leadId,
            'name'  => 'TEST LEAD',
            'phone' => '628123456789',
        ]);

        $this->assertNotNull(Lead::withTrashed()->find($leadId)->deleted_at);
    }

    public function test_restore_after_soft_delete_preserves_data(): void
    {
        $lead = Lead::factory()->create([
            'name'  => 'preserved lead',
            'phone' => '628123456789',
        ]);

        $originalData = $lead->toArray();
        $lead->delete();
        $lead->restore();

        $restoredLead = Lead::find($lead->id);

        $this->assertEquals($originalData['name'], $restoredLead->name);
        $this->assertEquals($originalData['phone'], $restoredLead->phone);
        $this->assertEquals($originalData['address'], $restoredLead->address);
    }

    public function test_phone_uniqueness_constraint_violation_throws_exception(): void
    {
        Lead::factory()->create(['phone' => '628123456789']);

        $this->expectException(QueryException::class);

        Lead::factory()->create(['phone' => '628123456789']);
    }

    public function test_enum_constraint_for_store_team_position(): void
    {
        $this->expectException(QueryException::class);

        DB::table('leads')->insert([
            'name'                => 'TEST',
            'phone'               => '628123456789',
            'address'             => 'Test',
            'sales_person'        => 'Test',
            'store_team_position' => 'Invalid Position',
            'store_branch'        => 'Test',
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);
    }

    public function test_enum_constraint_for_phone_transaction_range(): void
    {
        $this->expectException(QueryException::class);

        DB::table('leads')->insert([
            'name'                         => 'TEST',
            'phone'                        => '628123456789',
            'address'                      => 'Test',
            'sales_person'                 => 'Test',
            'store_team_position'          => 'Kasir',
            'store_branch'                 => 'Test',
            'phone_transaction_range'      => 'Invalid Range',
            'created_at'                   => now(),
            'updated_at'                   => now(),
        ]);
    }

    public function test_factory_state_consistency(): void
    {
        $leads = Lead::factory()->count(10)->create();

        foreach ($leads as $lead) {
            $this->assertNotNull($lead->name);
            $this->assertNotNull($lead->phone);
            $this->assertNotNull($lead->address);
            $this->assertNotNull($lead->sales_person);
            $this->assertContains($lead->store_team_position->value, ['Kepala Toko', 'Promotor', 'Kasir', 'Frontliner']);
            $this->assertStringStartsWith('62', $lead->phone);
            $this->assertEquals($lead->name, mb_strtoupper($lead->name));
        }
    }

    public function test_lead_relationship_eager_loading(): void
    {
        $user = User::factory()->create();
        Lead::factory()->count(3)->create(['creator_id' => $user->id]);

        $leads = Lead::with('creator')->get();

        $this->assertCount(3, $leads);
        foreach ($leads as $lead) {
            $this->assertNotNull($lead->creator);
            $this->assertEquals($user->id, $lead->creator->id);
        }
    }

    public function test_lead_without_creator_id_relationship(): void
    {
        $lead = Lead::factory()->create(['creator_id' => null]);

        $this->assertNull($lead->creator);
    }

    public function test_complex_query_with_multiple_conditions(): void
    {
        Lead::factory()->create([
            'store_team_position'          => 'Kasir',
            'store_branch'                 => 'Complete Selular Babakan',
            'phone_transaction_range'      => 'Harga di bawah 2 juta',
        ]);

        Lead::factory()->create([
            'store_team_position'          => 'Promotor',
            'store_branch'                 => 'Complete Selular Tegal',
            'phone_transaction_range'      => 'Harga 4 - 7 juta',
        ]);

        $results = Lead::where('store_team_position', 'Kasir')
            ->where('store_branch', 'Complete Selular Babakan')
            ->where('phone_transaction_range', 'Harga di bawah 2 juta')
            ->get();

        $this->assertCount(1, $results);
    }

    public function test_phone_normalization_edge_case_with_database_constraint(): void
    {
        $lead = Lead::factory()->create(['phone' => '+62 (812) 345-6789']);

        $this->assertEquals('628123456789', $lead->phone);
        $this->assertDatabaseHas('leads', [
            'id'    => $lead->id,
            'phone' => '628123456789',
        ]);
    }

    public function test_all_store_branch_options_can_be_stored(): void
    {
        $cabangOptions = [
            'Complete Selular Babakan',
            'Complete Selular Cilacap',
            'Complete Selular Ciledug',
            'Complete Selular Gebang',
            'Complete Selular Jatiwangi',
            'Complete Selular Jatiwangi2 (Cibolerang)',
            'Complete Selular Kroya',
            'Complete Selular Pabuaran',
            'Complete Selular Patrol',
            'Complete Selular Perumnas',
            'Complete Selular Plaza Cell',
            'Complete Selular Sindang',
            'Complete Selular Surya 1',
            'Complete Selular Tegal',
            'Complete Selular Tuparev',
            'Global Selular Jatibarang',
            'HP Mart Ciledug',
            'HP Mart Sindang',
            'Intiphone',
            'Mi Shop Ciledug',
            'Oppo Store Tentara Pelajar',
            'Selular Plus Jatibarang',
            'Unboxing Megu',
        ];

        foreach ($cabangOptions as $index => $cabang) {
            $lead = Lead::factory()->create([
                'store_branch' => $cabang,
                'phone'        => '62812345'.sprintf('%05d', $index),
            ]);

            $this->assertEquals($cabang, $lead->store_branch);
        }

        $this->assertDatabaseCount('leads', count($cabangOptions));
    }
}
