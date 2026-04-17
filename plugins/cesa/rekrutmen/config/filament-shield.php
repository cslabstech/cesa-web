<?php

use Cesa\Rekrutmen\Filament\Clusters\Configurations;
use Cesa\Rekrutmen\Filament\Resources\ApproverResource;
use Cesa\Rekrutmen\Filament\Resources\DivisionResource;
use Cesa\Rekrutmen\Filament\Resources\JobApplicationResource;
use Cesa\Rekrutmen\Filament\Resources\JobPostingResource;
use Cesa\Rekrutmen\Filament\Resources\RekrutmenPipelineResource;
use Cesa\Rekrutmen\Filament\Resources\RequestManPowerResource;

return [
    'resources' => [
        'manage' => [
            ApproverResource::class           => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any'],
            DivisionResource::class           => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any'],
            RekrutmenPipelineResource::class  => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any'],
            JobPostingResource::class         => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any'],
            JobApplicationResource::class     => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any'],
            RequestManPowerResource::class    => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any'],
        ],
        'exclude' => [],
    ],

    'pages' => [
        'exclude' => [
            Configurations::class,
        ],
    ],
];
