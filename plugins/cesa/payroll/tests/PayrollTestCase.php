<?php

namespace Cesa\Payroll\Tests;

use Cesa\Payroll\PayrollServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\UsesSqliteInMemoryDatabase;
use Webkul\PluginManager\Package;

abstract class PayrollTestCase extends TestCase
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

        foreach (['presensi', 'payroll'] as $pluginName) {
            DB::table('plugins')->updateOrInsert([
                'name' => $pluginName,
            ], [
                'author'       => 'tests',
                'summary'      => 'tests',
                'description'  => 'tests',
                'is_active'    => true,
                'is_installed' => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }

        Package::$plugins = [];

        $this->app->register(PayrollServiceProvider::class, true);

        $this->artisan('migrate', [
            '--path'     => 'plugins/cesa/presensi/database/migrations',
            '--realpath' => false,
        ]);

        $this->artisan('migrate', [
            '--path'     => 'plugins/cesa/payroll/database/migrations',
            '--realpath' => false,
        ]);
    }

    protected function tearDown(): void
    {
        Package::$plugins = [];

        parent::tearDown();
    }
}
