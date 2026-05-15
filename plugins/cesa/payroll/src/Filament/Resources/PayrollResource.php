<?php

namespace Cesa\Payroll\Filament\Resources;

use BackedEnum;
use Filament\Resources\Resource;
use Webkul\PluginManager\Package;
use Webkul\Security\Traits\HasResourcePermissionQuery;

abstract class PayrollResource extends Resource
{
    use HasResourcePermissionQuery;

    protected static BackedEnum|string|null $navigationIcon = null;

    protected static ?int $navigationSort = null;

    protected static bool $navigationConfigured = false;

    public static function shouldRegisterNavigation(): bool
    {
        return Package::isPluginInstalled('payroll');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.presensi');
    }
}
