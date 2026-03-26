<?php

namespace Cesa\Document;

use Filament\Contracts\Plugin;
use Filament\Panel;
use ReflectionClass;
use Webkul\PluginManager\Package;

class DocumentPlugin implements Plugin
{
    public function getId(): string
    {
        return 'document';
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
                    ->discoverResources(in: $this->getPluginBasePath('/Filament/Resources'), for: 'Cesa\\Document\\Filament\\Resources')
                    ->discoverPages(in: $this->getPluginBasePath('/Filament/Pages'), for: 'Cesa\\Document\\Filament\\Pages')
                    ->discoverWidgets(in: $this->getPluginBasePath('/Filament/Widgets'), for: 'Cesa\\Document\\Filament\\Widgets');
            });
    }

    public function boot(Panel $panel): void {}

    protected function getPluginBasePath($path = null): string
    {
        $reflector = new ReflectionClass(get_class($this));

        return dirname($reflector->getFileName()).($path ?? '');
    }
}
