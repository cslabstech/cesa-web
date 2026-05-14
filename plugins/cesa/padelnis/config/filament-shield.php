<?php

use Cesa\Padelnis\Filament\Resources\ReservationResource;

return [
    'resources' => [
        'manage' => [
            ReservationResource::class => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any'],
        ],
        'exclude' => [],
    ],

    'pages' => [
        'exclude' => [],
    ],
];
