<?php

use Cesa\Helpdesk\Filament\Clusters\Configurations;
use Cesa\Helpdesk\Filament\Clusters\Configurations\Resources\PriorityResource;
use Cesa\Helpdesk\Filament\Clusters\Configurations\Resources\ProblemCategoryResource;
use Cesa\Helpdesk\Filament\Clusters\Configurations\Resources\TicketStatusResource;
use Cesa\Helpdesk\Filament\Clusters\Configurations\Resources\UnitResource;
use Cesa\Helpdesk\Filament\Resources\TicketResource;

return [
    'resources' => [
        'manage' => [
            TicketResource::class          => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any'],
            PriorityResource::class        => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any'],
            TicketStatusResource::class    => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any'],
            UnitResource::class            => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any'],
            ProblemCategoryResource::class => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any'],
        ],
        'exclude' => [],
    ],

    'pages' => [
        'exclude' => [
            Configurations::class,
        ],
    ],
];
