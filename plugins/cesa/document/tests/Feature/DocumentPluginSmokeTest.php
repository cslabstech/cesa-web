<?php

namespace Cesa\Document\Tests\Feature;

use Cesa\Document\DocumentPlugin;
use Cesa\Document\DocumentServiceProvider;
use Cesa\Document\Filament\Resources\DocumentResource;
use Tests\TestCase;
use Webkul\PluginManager\Package;

class DocumentPluginSmokeTest extends TestCase
{
    public function test_it_uses_the_document_identity(): void
    {
        $this->assertSame('document', DocumentServiceProvider::$name);
        $this->assertSame('document', app(DocumentPlugin::class)->getId());
    }

    public function test_it_registers_all_plugin_migrations(): void
    {
        $package = new Package;

        (new DocumentServiceProvider($this->app))->configureCustomPackage($package);

        $expectedMigrations = collect(glob(dirname(__DIR__, 2).'/database/migrations/*.php') ?: [])
            ->map(static fn (string $path): string => basename($path, '.php'))
            ->sort()
            ->values()
            ->all();

        $registeredMigrations = collect($package->migrationFileNames)
            ->sort()
            ->values()
            ->all();

        $this->assertSame($expectedMigrations, $registeredMigrations);
    }

    public function test_it_declares_permissions_via_filament_shield_config(): void
    {
        $config = require base_path('plugins/cesa/document/config/filament-shield.php');
        $manage = $config['resources']['manage'] ?? [];

        $this->assertSame(
            ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any', 'reorder'],
            $manage[DocumentResource::class] ?? null,
        );
    }
}
