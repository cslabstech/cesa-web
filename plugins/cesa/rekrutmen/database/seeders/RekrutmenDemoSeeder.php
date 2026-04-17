<?php

namespace Cesa\Rekrutmen\Database\Seeders;

use Illuminate\Database\Seeder;

class RekrutmenDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RekrutmenCrosscheckSeeder::class,
        ]);
    }
}
