<?php

use Cesa\FormTransfer\Filament\Clusters\Configurations;
use Cesa\FormTransfer\Filament\Clusters\Configurations\Resources\ApprovalWorkflowResource;
use Cesa\FormTransfer\Filament\Clusters\Configurations\Resources\BankResource;
use Cesa\FormTransfer\Filament\Clusters\Configurations\Resources\DivisionResource;
use Cesa\FormTransfer\Filament\Clusters\Configurations\Resources\FormTransferResource;
use Cesa\FormTransfer\Filament\Clusters\Configurations\Resources\ReferenceNoteResource;
use Cesa\FormTransfer\Filament\Resources\TransferRequestResource;

return [
    'resources' => [
        'manage' => [
            FormTransferResource::class     => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any'],
            TransferRequestResource::class  => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any'],
            DivisionResource::class         => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any'],
            BankResource::class             => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any'],
            ReferenceNoteResource::class    => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any'],
            ApprovalWorkflowResource::class => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any'],
        ],
        'exclude' => [],
    ],

    'pages' => [
        'exclude' => [
            Configurations::class,
        ],
    ],
];
