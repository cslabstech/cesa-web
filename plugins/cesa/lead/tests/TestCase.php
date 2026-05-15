<?php

namespace Cesa\Lead\Tests;

use Cesa\Lead\LeadServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase as BaseTestCase;
use Tests\UsesSqliteInMemoryDatabase;
use Webkul\PluginManager\Package;

abstract class TestCase extends BaseTestCase
{
    use UsesSqliteInMemoryDatabase;

    protected string $sqliteDatabasePath;

    protected function setUp(): void
    {
        $this->useSqliteInMemoryDatabase();

        parent::setUp();

        $this->sqliteDatabasePath = tempnam(sys_get_temp_dir(), 'lead-test-');

        config([
            'database.default'            => 'sqlite',
            'database.connections.sqlite' => [
                'driver'                  => 'sqlite',
                'database'                => $this->sqliteDatabasePath,
                'prefix'                  => '',
                'foreign_key_constraints' => true,
            ],
        ]);

        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');
        DB::reconnect('sqlite');

        $this->runRequiredMigrations();

        DB::table('plugins')->insert([
            'name'           => 'lead',
            'author'         => 'test',
            'summary'        => 'test',
            'description'    => 'test',
            'latest_version' => '1.0.0',
            'license'        => null,
            'is_active'      => true,
            'is_installed'   => true,
            'sort'           => 1,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        Package::$plugins = [];

        $this->app->register(LeadServiceProvider::class);

        config()->set('lead.whatsapp_validation.enabled', false);

        if (! Route::has('lead.public.form') || ! Route::has('lead.public.show')) {
            require base_path('plugins/cesa/lead/routes/web.php');
        }

        Route::getRoutes()->refreshNameLookups();
        $this->app['url']->setRoutes(Route::getRoutes());
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if (isset($this->sqliteDatabasePath) && is_file($this->sqliteDatabasePath)) {
            @unlink($this->sqliteDatabasePath);
        }

        Package::$plugins = [];
    }

    protected function runRequiredMigrations(): void
    {
        foreach ([
            'database/migrations/0001_01_01_000000_create_users_table.php',
            'database/migrations/2024_11_04_132945_create_permission_tables.php',
            'database/migrations/2024_11_26_053234_add_resource_permission_column_to_users_table.php',
            'plugins/webkul/plugin-manager/database/migrations/2024_11_05_105102_create_plugins_table.php',
            'plugins/webkul/support/database/migrations/2024_12_06_061927_create_currencies_table.php',
            'plugins/webkul/support/database/migrations/2024_12_10_092657_create_companies_table.php',
            'plugins/webkul/partners/database/migrations/2024_12_11_101127_create_partners_industries_table.php',
            'plugins/webkul/partners/database/migrations/2024_12_11_101127_create_partners_titles_table.php',
            'plugins/webkul/partners/database/migrations/2024_12_11_101220_create_partners_partners_table.php',
            'plugins/webkul/security/database/migrations/2024_12_13_130906_add_partner_id_to_users_table.php',
            'plugins/webkul/security/database/migrations/2025_08_01_073954_alter_users_table.php',
        ] as $migrationPath) {
            $this->artisan('migrate', [
                '--path'     => $migrationPath,
                '--realpath' => false,
            ]);
        }

        $this->artisan('migrate', [
            '--path'     => 'plugins/cesa/lead/database/migrations',
            '--realpath' => false,
        ]);
    }
}
