<?php

namespace Cesa\Shelf\Tests\Feature;

use Cesa\Shelf\ShelfServiceProvider;
use ReflectionClass;
use Tests\TestCase;
use Webkul\PluginManager\Package;

class ShelfInstallCommandTest extends TestCase
{
    public function test_shelf_install_command_installs_dependencies_before_running_migrations(): void
    {
        $package = new Package;
        $provider = new ShelfServiceProvider($this->app);

        $provider->configureCustomPackage($package);

        $installCommand = collect($package->consoleCommands)
            ->first(fn (object $command): bool => $this->readProperty($command, 'signature') === 'shelf:install');

        $this->assertNotNull($installCommand);
        $this->assertTrue((bool) $this->readProperty($installCommand, 'installDependencies'));
        $this->assertTrue((bool) $this->readProperty($installCommand, 'runsMigrations'));
    }

    private function readProperty(object $target, string $name): mixed
    {
        $property = (new ReflectionClass($target))->getProperty($name);
        $property->setAccessible(true);

        return $property->getValue($target);
    }
}
