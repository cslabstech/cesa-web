<?php

namespace Cesa\LegacySync\Tests;

use Cesa\Document\DocumentServiceProvider;
use Cesa\ExitClearance\ExitClearanceServiceProvider;
use Cesa\FormTransfer\FormTransferServiceProvider;
use Cesa\Helpdesk\HelpdeskServiceProvider;
use Cesa\Kepegawaian\KepegawaianServiceProvider;
use Cesa\Lead\LeadServiceProvider;
use Cesa\LegacySync\LegacySyncServiceProvider;
use Cesa\Presensi\PresensiServiceProvider;
use Cesa\Shelf\ShelfServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Tests\UsesSqliteInMemoryDatabase;

abstract class LegacySyncTestCase extends TestCase
{
    use RefreshDatabase;
    use UsesSqliteInMemoryDatabase;

    protected string $legacyDatabasePath;

    protected function setUp(): void
    {
        $this->useSqliteInMemoryDatabase();

        parent::setUp();

        config([
            'database.default'                     => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        $this->legacyDatabasePath = tempnam(sys_get_temp_dir(), 'legacy-sync-');

        config([
            'legacy-sync.connections.legacy_sync' => [
                'driver'                  => 'sqlite',
                'database'                => $this->legacyDatabasePath,
                'prefix'                  => '',
                'foreign_key_constraints' => true,
            ],
            'database.connections.legacy_sync' => [
                'driver'                  => 'sqlite',
                'database'                => $this->legacyDatabasePath,
                'prefix'                  => '',
                'foreign_key_constraints' => true,
            ],
        ]);

        $this->app->register(DocumentServiceProvider::class);
        $this->app->register(FormTransferServiceProvider::class);
        $this->app->register(ExitClearanceServiceProvider::class);
        $this->app->register(PresensiServiceProvider::class);
        $this->app->register(HelpdeskServiceProvider::class);
        $this->app->register(KepegawaianServiceProvider::class);
        $this->app->register(LeadServiceProvider::class);
        $this->app->register(ShelfServiceProvider::class);
        $this->app->register(LegacySyncServiceProvider::class);

        $this->artisan('migrate', [
            '--path'     => 'database/migrations/0001_01_01_000000_create_users_table.php',
            '--realpath' => false,
        ]);

        $this->artisan('migrate', [
            '--path'     => 'database/migrations/2024_11_26_053234_add_resource_permission_column_to_users_table.php',
            '--realpath' => false,
        ]);

        $this->artisan('migrate', [
            '--path'     => 'database/migrations/2024_11_04_132945_create_permission_tables.php',
            '--realpath' => false,
        ]);

        $this->artisan('migrate', [
            '--path'     => 'plugins/webkul/support/database/migrations/2024_12_06_061927_create_currencies_table.php',
            '--realpath' => false,
        ]);

        $this->artisan('migrate', [
            '--path'     => 'plugins/webkul/support/database/migrations/2024_12_10_092651_create_countries_table.php',
            '--realpath' => false,
        ]);

        $this->artisan('migrate', [
            '--path'     => 'plugins/webkul/support/database/migrations/2024_12_10_092657_create_states_table.php',
            '--realpath' => false,
        ]);

        $this->artisan('migrate', [
            '--path'     => 'plugins/webkul/support/database/migrations/2024_12_10_092657_create_companies_table.php',
            '--realpath' => false,
        ]);

        if (! Schema::hasTable('partners_partners')) {
            $this->artisan('migrate', [
                '--path'     => 'plugins/webkul/partners/database/migrations',
                '--realpath' => false,
            ]);
        }

        $this->artisan('migrate', [
            '--path'     => 'plugins/webkul/support/database/migrations/2025_04_04_061507_add_address_columns_in_companies_table.php',
            '--realpath' => false,
        ]);

        $this->artisan('migrate', [
            '--path'     => 'plugins/webkul/support/database/migrations/2025_04_04_062023_alter_companies_table.php',
            '--realpath' => false,
        ]);

        $this->artisan('migrate', [
            '--path'     => 'plugins/webkul/support/database/migrations/2025_01_07_125015_add_partner_id_to_companies_table.php',
            '--realpath' => false,
        ]);

        $this->artisan('migrate', [
            '--path'     => 'plugins/webkul/support/database/migrations/2024_12_10_100944_create_user_allowed_companies_table.php',
            '--realpath' => false,
        ]);

        $this->artisan('migrate', [
            '--path'     => 'plugins/webkul/security/database/migrations/2024_12_10_101127_add_default_company_id_column_to_users_table.php',
            '--realpath' => false,
        ]);

        $this->artisan('migrate', [
            '--path'     => 'plugins/webkul/security/database/migrations/2024_12_13_130906_add_partner_id_to_users_table.php',
            '--realpath' => false,
        ]);

        $this->artisan('migrate', [
            '--path'     => 'plugins/webkul/security/database/migrations/2025_08_01_073954_alter_users_table.php',
            '--realpath' => false,
        ]);

        $this->artisan('migrate', [
            '--path'     => 'plugins/cesa/document/database/migrations',
            '--realpath' => false,
        ]);

        $this->artisan('migrate', [
            '--path'     => 'plugins/cesa/form-transfer/database/migrations',
            '--realpath' => false,
        ]);

        $this->artisan('migrate', [
            '--path'     => 'plugins/cesa/exit-clearance/database/migrations',
            '--realpath' => false,
        ]);

        $this->artisan('migrate', [
            '--path'     => 'plugins/cesa/presensi/database/migrations',
            '--realpath' => false,
        ]);

        $this->artisan('migrate', [
            '--path'     => 'plugins/cesa/helpdesk/database/migrations',
            '--realpath' => false,
        ]);

        $this->artisan('migrate', [
            '--path'     => 'plugins/webkul/support/database/migrations/2024_12_12_114620_create_activity_plans_table.php',
            '--realpath' => false,
        ]);

        $this->artisan('migrate', [
            '--path'     => 'plugins/webkul/support/database/migrations/2024_12_12_115256_create_activity_types_table.php',
            '--realpath' => false,
        ]);

        $this->artisan('migrate', [
            '--path'     => 'plugins/webkul/support/database/migrations/2025_01_03_105625_create_unit_of_measure_categories_table.php',
            '--realpath' => false,
        ]);

        $this->artisan('migrate', [
            '--path'     => 'plugins/webkul/support/database/migrations/2025_01_03_105627_create_unit_of_measures_table.php',
            '--realpath' => false,
        ]);

        $this->artisan('migrate', [
            '--path'     => 'plugins/webkul/support/database/migrations/2025_01_09_111545_create_utm_mediums_table.php',
            '--realpath' => false,
        ]);

        $this->artisan('migrate', [
            '--path'     => 'plugins/webkul/support/database/migrations/2025_01_09_114324_create_utm_sources_table.php',
            '--realpath' => false,
        ]);

        $this->artisan('migrate', [
            '--path'     => 'plugins/webkul/support/database/migrations/2025_01_10_094256_create_utm_stages_table.php',
            '--realpath' => false,
        ]);

        $this->artisan('migrate', [
            '--path'     => 'plugins/webkul/support/database/migrations/2025_01_10_094325_create_utm_campaigns_table.php',
            '--realpath' => false,
        ]);

        $this->artisan('migrate', [
            '--path'     => 'plugins/webkul/support/database/migrations/2025_08_08_104317_alter_utm_stages_table.php',
            '--realpath' => false,
        ]);

        $this->artisan('migrate', [
            '--path'     => 'plugins/webkul/support/database/migrations/2025_08_08_104814_alter_utm_campaigns_table.php',
            '--realpath' => false,
        ]);

        $this->artisan('migrate', [
            '--path'     => 'plugins/cesa/kepegawaian/database/migrations',
            '--realpath' => false,
        ]);

        $this->artisan('migrate', [
            '--path'     => 'plugins/cesa/lead/database/migrations',
            '--realpath' => false,
        ]);

        $this->artisan('migrate', [
            '--path'     => 'plugins/cesa/shelf/database/migrations',
            '--realpath' => false,
        ]);

        $this->artisan('migrate', [
            '--path'     => 'plugins/cesa/legacy-sync/database/migrations',
            '--realpath' => false,
        ]);

        DB::purge('legacy_sync');
        DB::reconnect('legacy_sync');

        $this->createLegacySchema();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if (isset($this->legacyDatabasePath) && is_file($this->legacyDatabasePath)) {
            @unlink($this->legacyDatabasePath);
        }
    }

    abstract protected function createLegacySchema(): void;
}
