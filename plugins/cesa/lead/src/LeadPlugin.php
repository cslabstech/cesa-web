<?php

namespace Cesa\Lead;

use Filament\Contracts\Plugin;
use Filament\Panel;
use ReflectionClass;
use Webkul\PluginManager\Package;

class LeadPlugin implements Plugin
{
    public function getId(): string
    {
        return 'lead';
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public function register(Panel $panel): void
    {
        if (! Package::isPluginInstalled($this->getId())) {
            return;
        }

        $panel
            ->when($panel->getId() === 'admin', function (Panel $panel) {
                $panel
                    ->discoverResources(in: $this->getPluginBasePath('/Filament/Resources'), for: 'Cesa\\Lead\\Filament\\Resources')
                    ->discoverPages(in: $this->getPluginBasePath('/Filament/Pages'), for: 'Cesa\\Lead\\Filament\\Pages')
                    ->discoverWidgets(in: $this->getPluginBasePath('/Filament/Widgets'), for: 'Cesa\\Lead\\Filament\\Widgets');
            });
    }

    public function boot(Panel $panel): void {}

    protected function getPluginBasePath($path = null): string
    {
        $reflector = new ReflectionClass(get_class($this));

        return dirname($reflector->getFileName()).($path ?? '');
    }
}
