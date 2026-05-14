<?php

namespace Cesa\Padelnis\Tests;

use Cesa\Padelnis\PadelnisServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\UsesSqliteInMemoryDatabase;
use Webkul\PluginManager\Package;

abstract class PadelnisTestCase extends TestCase
{
    use RefreshDatabase;
    use UsesSqliteInMemoryDatabase;

    protected function setUp(): void
    {
        $this->useSqliteInMemoryDatabase();

        parent::setUp();

        $this->artisan('migrate', [
            '--path'     => 'plugins/webkul/plugin-manager/database/migrations/2024_11_05_105102_create_plugins_table.php',
            '--realpath' => false,
        ]);

        DB::table('plugins')->insert([
            'name'         => 'padelnis',
            'author'       => 'tests',
            'is_active'    => true,
            'is_installed' => true,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        Package::$plugins = [];

        $this->app->register(PadelnisServiceProvider::class);

        $this->artisan('migrate', [
            '--path'     => 'plugins/cesa/padelnis/database/migrations',
            '--realpath' => false,
        ]);

        $routes = app('router')->getRoutes();

        if (! $routes->hasNamedRoute('padelnis.public.form') || ! $routes->hasNamedRoute('padelnis.public.success')) {
            require base_path('plugins/cesa/padelnis/routes/web.php');

            $routes = app('router')->getRoutes();
            $routes->refreshNameLookups();
            $routes->refreshActionLookups();
        }
    }

    protected function tearDown(): void
    {
        Package::$plugins = [];

        parent::tearDown();
    }
}
