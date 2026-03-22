<?php

return [
    'form' => [
        'name'  => 'Nama',
        'color' => 'Warna',
    ],
    'table' => [
        'columns' => [
            'name'       => 'Nama',
            'color'      => 'Warna',
            'created-at' => 'Dibuat Pada',
            'updated-at' => 'Diperbarui Pada',
        ],
        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Tag diperbarui',
                    'body'  => 'Tag telah berhasil diperbarui.',
                ],
            ],
            'restore' => [
                'notification' => [
                    'title' => 'Tag dipulihkan',
                    'body'  => 'Tag telah berhasil dipulihkan.',
                ],
            ],
            'delete' => [
                'notification' => [
                    'title' => 'Tag dihapus',
                    'body'  => 'Tag telah berhasil dihapus.',
                ],
            ],
            'force-delete' => [
                'notification' => [
                    'title' => 'Kekuatan tag dihapus',
                    'body'  => 'Tag telah berhasil dihapus paksa.',
                ],
            ],
        ],
        'bulk-actions' => [
            'restore' => [
                'notification' => [
                    'title' => 'Tag dipulihkan',
                    'body'  => 'Tag telah berhasil dipulihkan.',
                ],
            ],
            'delete' => [
                'notification' => [
                    'title' => 'Tag dihapus',
                    'body'  => 'Tag telah berhasil dihapus.',
                ],
            ],
            'force-delete' => [
                'notification' => [
                    'title' => 'Tag dihapus paksa',
                    'body'  => 'Tag telah berhasil dihapus secara paksa.',
                ],
            ],
        ],
    ],
];
