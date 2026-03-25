<?php

use Cesa\Shelf\Filament\Clusters\Configurations;
use Cesa\Shelf\Filament\Resources\ApprovalLevelResource;
use Cesa\Shelf\Filament\Resources\AssetLocationResource;
use Cesa\Shelf\Filament\Resources\AssetRequestResource;
use Cesa\Shelf\Filament\Resources\AssetResource;
use Cesa\Shelf\Filament\Resources\AssetTransferResource;
use Cesa\Shelf\Filament\Resources\BrandResource;
use Cesa\Shelf\Filament\Resources\CategoryResource;
use Cesa\Shelf\Filament\Resources\CompanyDocumentSettingResource;
use Cesa\Shelf\Filament\Resources\CustomAssetAttributeResource;
use Cesa\Shelf\Filament\Resources\TaskResource;
use Cesa\Shelf\Filament\Resources\VehicleChecksheetResource;
use Cesa\Shelf\Filament\Resources\VendorResource;

return [
    'resources' => [
        'manage' => [
            ApprovalLevelResource::class          => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any'],
            AssetLocationResource::class          => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any'],
            AssetResource::class                  => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any'],
            AssetTransferResource::class          => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any'],
            BrandResource::class                  => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any'],
            CategoryResource::class               => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any'],
            CompanyDocumentSettingResource::class => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any'],
            CustomAssetAttributeResource::class   => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any'],
            AssetRequestResource::class           => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any'],
            TaskResource::class                   => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any'],
            VehicleChecksheetResource::class      => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any'],
            VendorResource::class                 => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any'],
        ],
        'exclude' => [],
    ],

    'pages' => [
        'exclude' => [
            Configurations::class,
        ],
    ],

    'widgets' => [
        'exclude' => [],
    ],
];
