<?php

return [
    'title'      => 'Mata uang',
    'navigation' => [
        'title' => 'Mata uang',
        'group' => 'Pengaturan',
    ],
    'form' => [
        'sections' => [
            'currency-details' => [
                'title'  => 'Informasi Mata Uang',
                'fields' => [
                    'name'         => 'Nama Mata Uang',
                    'name-tooltip' => 'Masukkan nama mata uang resmi',
                    'symbol'       => 'Simbol Mata Uang',
                    'full-name'    => 'Nama Lengkap',
                    'iso-numeric'  => 'Kode Numerik ISO',
                ],
            ],
            'format-information' => [
                'title'  => 'Konfigurasi Format',
                'fields' => [
                    'decimal-places'       => 'Tempat Desimal',
                    'rounding'             => 'Pembulatan Presisi',
                    'rounding-helper-text' => 'Atur presisi pembulatan untuk perhitungan mata uang',
                ],
            ],
            'status-and-configuration-information' => [
                'title'  => 'Status & Konfigurasi',
                'fields' => [
                    'status' => 'Status',
                ],
            ],
            'rates' => [
                'title'       => 'Nilai Mata Uang',
                'description' => 'Kelola nilai tukar historis untuk mata uang ini relatif terhadap mata uang dasar (USD).',
                'fields'      => [
                    'name'              => 'Tanggal',
                    'unit-per-currency' => 'Satuan Per :currency',
                    'currency-per-unit' => ':currency Per Satuan',
                ],
                'add-rate'   => 'Tambahkan Tarif',
                'item-label' => 'Kecepatan',
            ],
        ],
    ],
    'table' => [
        'columns' => [
            'name'           => 'Nama Mata Uang',
            'symbol'         => 'Simbol',
            'full-name'      => 'Nama Lengkap',
            'iso-numeric'    => 'Kode ISO',
            'decimal-places' => 'Tempat Desimal',
            'rounding'       => 'Pembulatan',
            'status'         => 'Status',
            'created-at'     => 'Dibuat Pada',
            'updated-at'     => 'Diperbarui Pada',
        ],
        'groups' => [
            'name'           => 'Nama',
            'status'         => 'Status',
            'decimal-places' => 'Tempat Desimal',
            'creation-date'  => 'Tanggal Pembuatan',
            'last-update'    => 'Pembaruan Terakhir',
        ],
        'filters' => [
            'status' => 'Status',
        ],
        'actions' => [
            'delete' => [
                'notification' => [
                    'title'   => 'Mata uang dihapus',
                    'body'    => 'Mata uang telah berhasil dihapus.',
                    'success' => [
                        'title' => 'Mata uang dihapus',
                        'body'  => 'Mata uang telah berhasil dihapus.',
                    ],
                    'error' => [
                        'title' => 'Mata uang tidak dapat dihapus',
                        'body'  => 'Mata uang tidak dapat dihapus karena sedang digunakan.',
                    ],
                ],
            ],
        ],
        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Mata uang dihapus',
                    'body'  => 'Mata uang telah berhasil dihapus.',
                ],
            ],
        ],
    ],
    'infolist' => [
        'sections' => [
            'currency-details' => [
                'title'   => 'Informasi Mata Uang',
                'entries' => [
                    'name'        => 'Nama Mata Uang',
                    'symbol'      => 'Simbol Mata Uang',
                    'full-name'   => 'Nama Lengkap',
                    'iso-numeric' => 'Kode Numerik ISO',
                ],
            ],
            'format-information' => [
                'title'   => 'Konfigurasi Format',
                'entries' => [
                    'decimal-places' => 'Tempat Desimal',
                    'rounding'       => 'Pembulatan Presisi',
                ],
            ],
            'status-and-configuration-information' => [
                'title'   => 'Status & Konfigurasi',
                'entries' => [
                    'status' => 'Status',
                ],
            ],
            'rates' => [
                'title'   => 'Nilai Mata Uang',
                'entries' => [
                    'name'              => 'Tanggal',
                    'unit-per-currency' => 'Satuan Per :currency',
                    'currency-per-unit' => ':currency Per Satuan',
                ],
            ],
        ],
    ],
];
