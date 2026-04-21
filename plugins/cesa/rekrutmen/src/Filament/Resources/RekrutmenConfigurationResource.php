<?php

namespace Cesa\Rekrutmen\Filament\Resources;

use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Webkul\PluginManager\Package;
use Webkul\Security\Traits\HasResourcePermissionQuery;

abstract class RekrutmenConfigurationResource extends Resource
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
        return Package::isPluginInstalled('rekrutmen');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
