<?php

namespace Cesa\ExitClearance\Filament\Clusters\Configurations\Resources;

use Filament\Resources\Resource;
use Webkul\PluginManager\Package;
use Webkul\Security\Traits\HasResourcePermissionQuery;

abstract class ExitClearanceConfigurationResource extends Resource
{
    use HasResourcePermissionQuery;

    protected static function isArchivedTab($livewire = null): bool
    {
        if (! is_object($livewire) || ! property_exists($livewire, 'activeTab')) {
            return false;
        }

        return $livewire->activeTab === 'archived';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Package::isPluginInstalled('exit-clearance');
    }
}
