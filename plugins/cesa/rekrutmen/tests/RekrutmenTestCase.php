<?php

namespace Cesa\Rekrutmen\Tests;

use Cesa\Rekrutmen\RekrutmenServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;
use Tests\UsesSqliteInMemoryDatabase;
use Webkul\PluginManager\Package;

abstract class RekrutmenTestCase extends TestCase
{
    use RefreshDatabase;
    use UsesSqliteInMemoryDatabase;

    protected function setUp(): void
    {
        $this->useSqliteInMemoryDatabase();

        parent::setUp();
        $this->withoutVite();

        $this->artisan('migrate', [
            '--path'     => 'plugins/webkul/plugin-manager/database/migrations/2024_11_05_105102_create_plugins_table.php',
            '--realpath' => false,
        ]);

        DB::table('plugins')->updateOrInsert([
            'name' => 'rekrutmen',
        ], [
            'author'       => 'tests',
            'summary'      => 'tests',
            'description'  => 'tests',
            'is_active'    => true,
            'is_installed' => true,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        Package::$plugins = [];

        $this->app->register(RekrutmenServiceProvider::class);

        if (! Route::has('rekrutmen.public.request-man-power.progress')) {
            require base_path('plugins/cesa/rekrutmen/routes/web.php');
        }

        if (! Route::has('api.rekrutmen.job-postings.index')) {
            require base_path('plugins/cesa/rekrutmen/routes/api.php');
        }

        $this->artisan('migrate', [
            '--path'     => 'plugins/cesa/rekrutmen/database/migrations',
            '--realpath' => false,
        ]);
    }
}
