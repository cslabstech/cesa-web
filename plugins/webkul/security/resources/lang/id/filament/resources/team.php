<?php

return [
    'title'      => 'Tim',
    'navigation' => [
        'title' => 'Tim',
        'group' => 'Pengaturan',
    ],
    'form' => [
        'fields' => [
            'name' => 'Nama',
        ],
    ],
    'table' => [
        'columns' => [
            'name'       => 'Nama',
            'created-by' => 'Dibuat Oleh',
        ],
        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Tim diperbarui',
                    'body'  => 'Tim telah berhasil diperbarui.',
                ],
            ],
            'delete' => [
                'notification' => [
                    'title' => 'Tim dihapus',
                    'body'  => 'Tim telah berhasil dihapus.',
                ],
            ],
        ],
        'empty-state-actions' => [
            'create' => [
                'notification' => [
                    'title' => 'Tim dibuat',
                    'body'  => 'Tim telah berhasil dibuat.',
                ],
            ],
        ],
    ],
    'infolist' => [
        'entries' => [
            'name' => 'Nama',
        ],
    ],
];
