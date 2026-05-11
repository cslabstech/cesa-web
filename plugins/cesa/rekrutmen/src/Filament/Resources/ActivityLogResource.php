<?php

namespace Cesa\Rekrutmen\Filament\Resources;

use Cesa\Rekrutmen\Filament\Resources\ActivityLogResource\Pages;
use Cesa\Rekrutmen\Models\JobApplicationHistory;
use Filament\Resources\Resource;

class ActivityLogResource extends Resource
{
    protected static ?string $model = JobApplicationHistory::class;

    protected static \BackedEnum|string|null $navigationIcon = null;

    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): string
    {
        return __('admin.navigation.rekrutmen');
    }

    public static function getNavigationLabel(): string
    {
        return __('rekrutmen::filament/resources/activity-log.navigation.label');
    }

    public static function getModelLabel(): string
    {
        return __('rekrutmen::filament/resources/activity-log.model.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('rekrutmen::filament/resources/activity-log.model.plural');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('viewAny', JobApplicationHistory::class) ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create', JobApplicationHistory::class) ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListActivityLogs::route('/'),
            'create' => Pages\CreateActivityLog::route('/create'),
        ];
    }
}
