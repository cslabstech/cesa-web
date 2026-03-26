<?php

use Cesa\Document\Filament\Resources\DocumentResource;

return [
    'resources' => [
        'manage' => [
            DocumentResource::class => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any', 'reorder'],
        ],
        'exclude' => [],
    ],

    'pages' => [
        'exclude' => [],
    ],
];
