<?php

namespace Cesa\Payroll\Tests\Feature;

use Cesa\Payroll\PayrollServiceProvider;
use Cesa\Payroll\Tests\PayrollTestCase;
use ReflectionClass;
use Webkul\PluginManager\Package;

class PayrollInstallCommandTest extends PayrollTestCase
{
    public function test_payroll_install_command_installs_dependencies_before_running_migrations(): void
    {
        $package = new Package;
        $provider = new PayrollServiceProvider($this->app);

        $provider->configureCustomPackage($package);

        $installCommand = collect($package->consoleCommands)
            ->first(fn (object $command): bool => $this->readProperty($command, 'signature') === 'payroll:install');

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
