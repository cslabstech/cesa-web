<?php

namespace Cesa\ExitClearance\Tests;

use Cesa\ExitClearance\ExitClearanceServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use ReflectionClass;
use Tests\TestCase;
use Tests\UsesSqliteInMemoryDatabase;
use Webkul\PluginManager\Package;
use Webkul\Security\Bouncer;

abstract class ExitClearanceTestCase extends TestCase
{
    use RefreshDatabase;
    use UsesSqliteInMemoryDatabase;

    protected function setUp(): void
    {
        $this->useSqliteInMemoryDatabase();

        parent::setUp();

        $this->resetBouncerAuthorizedUserIdsCache();

        $this->artisan('migrate', [
            '--path'     => 'plugins/webkul/plugin-manager/database/migrations/2024_11_05_105102_create_plugins_table.php',
            '--realpath' => false,
        ]);

        DB::table('plugins')->insert([
            'name'         => 'exit-clearance',
            'author'       => 'tests',
            'is_active'    => true,
            'is_installed' => true,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        Package::$plugins = [];

        $this->app->register(ExitClearanceServiceProvider::class);

        $this->artisan('migrate', [
            '--path'     => 'plugins/cesa/exit-clearance/database/migrations',
            '--realpath' => false,
        ]);
    }

    protected function tearDown(): void
    {
        $this->resetBouncerAuthorizedUserIdsCache();

        parent::tearDown();
    }

    protected function resetBouncerAuthorizedUserIdsCache(): void
    {
        $reflection = new ReflectionClass(Bouncer::class);
        $property = $reflection->getProperty('authorizedUserIdsCache');
        $property->setValue(null, null);
    }
}
