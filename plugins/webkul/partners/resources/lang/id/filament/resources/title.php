<?php

return [
    'form' => [
        'name'       => 'Nama',
        'short-name' => 'Nama Pendek',
    ],
    'table' => [
        'columns' => [
            'name'       => 'Nama',
            'short-name' => 'Nama Pendek',
            'created-at' => 'Dibuat Pada',
            'updated-at' => 'Diperbarui Pada',
        ],
        'filters' => [
            'creator' => 'Dibuat Oleh',
        ],
        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Judul diperbarui',
                    'body'  => 'Judul telah berhasil diperbarui.',
                ],
            ],
            'delete' => [
                'notification' => [
                    'title' => 'Judul dihapus',
                    'body'  => 'Judul telah berhasil dihapus.',
                ],
            ],
        ],
        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Judul dihapus',
                    'body'  => 'Judul telah berhasil dihapus.',
                ],
            ],
        ],
    ],
];
