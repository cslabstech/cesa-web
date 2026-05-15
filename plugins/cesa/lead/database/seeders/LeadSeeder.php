<?php

namespace Cesa\Lead\Database\Seeders;

use Cesa\Lead\Models\Lead;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LeadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userId = DB::table('users')->first()?->id ?? 1;

        Lead::factory()->count(20)->create(['creator_id' => $userId]);
    }
}
