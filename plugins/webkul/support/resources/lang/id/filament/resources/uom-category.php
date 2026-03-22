<?php

return [
    'navigation' => [
        'group' => 'Pengaturan',
        'title' => 'Kategori UOM',
    ],
    'form' => [
        'sections' => [
            'general' => [
                'title'  => 'Umum',
                'fields' => [
                    'name' => 'Nama',
                ],
            ],
            'uoms' => [
                'title'  => 'Satuan Ukuran',
                'fields' => [
                    'uoms'     => 'Satuan',
                    'type'     => 'Jenis',
                    'name'     => 'Nama',
                    'factor'   => 'Faktor',
                    'rounding' => 'Pembulatan Presisi',
                ],
                'actions' => [
                    'add' => 'Tambahkan Satuan',
                ],
            ],
        ],
    ],
    'table' => [
        'columns' => [
            'name'       => 'Nama',
            'uoms-count' => 'Satuan',
            'created-at' => 'Dibuat Pada',
            'updated-at' => 'Diperbarui Pada',
        ],
        'groups' => [
            'created-at' => 'Dibuat Pada',
        ],
        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Kategori UOM diperbarui',
                    'body'  => 'Kategori UOM telah berhasil diperbarui.',
                ],
            ],
            'delete' => [
                'notification' => [
                    'title' => 'Kategori UOM dihapus',
                    'body'  => 'Kategori UOM telah berhasil dihapus.',
                ],
            ],
        ],
        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Kategori UOM dihapus',
                    'body'  => 'Kategori UOM telah berhasil dihapus.',
                ],
            ],
        ],
    ],
];
