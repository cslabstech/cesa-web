<?php

return [
    'form' => [
        'name'      => 'Nama',
        'full-name' => 'Nama Lengkap',
    ],
    'table' => [
        'columns' => [
            'name'       => 'Nama',
            'full-name'  => 'Nama Lengkap',
            'created-at' => 'Dibuat Pada',
            'updated-at' => 'Diperbarui Pada',
        ],
        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Industri diperbarui',
                    'body'  => 'Industri ini telah berhasil diperbarui.',
                ],
            ],
            'restore' => [
                'notification' => [
                    'title' => 'Industri dipulihkan',
                    'body'  => 'Industri ini telah berhasil dipulihkan.',
                ],
            ],
            'delete' => [
                'notification' => [
                    'title' => 'Industri dihapus',
                    'body'  => 'Industri ini telah berhasil dihapus.',
                ],
            ],
            'force-delete' => [
                'notification' => [
                    'title' => 'Industri dihapus paksa',
                    'body'  => 'Industri ini telah berhasil dihapus secara paksa.',
                ],
            ],
        ],
        'bulk-actions' => [
            'restore' => [
                'notification' => [
                    'title' => 'Industri dipulihkan',
                    'body'  => 'Industri telah berhasil dipulihkan.',
                ],
            ],
            'delete' => [
                'notification' => [
                    'title' => 'Industri dihapus',
                    'body'  => 'Industri telah berhasil dihapus.',
                ],
            ],
            'force-delete' => [
                'notification' => [
                    'title' => 'Industri dihapus paksa',
                    'body'  => 'Industri telah berhasil dihapus secara paksa.',
                ],
            ],
        ],
    ],
];
