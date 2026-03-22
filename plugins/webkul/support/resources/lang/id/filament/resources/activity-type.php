<?php

return [
    'title'      => 'Jenis Aktivitas',
    'navigation' => [
        'title' => 'Jenis Aktivitas',
        'group' => 'Pengaturan',
    ],
    'form' => [
        'sections' => [
            'activity-type-details' => [
                'title'  => 'Informasi Umum',
                'fields' => [
                    'name'         => 'Jenis Aktivitas',
                    'name-tooltip' => 'Masukkan nama jenis aktivitas resmi',
                    'action'       => 'Tindakan',
                    'default-user' => 'Pengguna Bawaan',
                    'summary'      => 'Ringkasan',
                    'note'         => 'Catatan',
                ],
            ],
            'delay-information' => [
                'title'  => 'Informasi Penundaan',
                'fields' => [
                    'delay-count'            => 'Hitungan Penundaan',
                    'delay-unit'             => 'Satuan Penundaan',
                    'delay-form'             => 'Sumber Penundaan',
                    'delay-form-helper-text' => 'Sumber perhitungan penundaan',
                ],
            ],
            'advanced-information' => [
                'title'  => 'Informasi Lanjutan',
                'fields' => [
                    'icon'            => 'Ikon',
                    'decoration-type' => 'Jenis Dekorasi',
                    'chaining-type'   => 'Tipe Rantai',
                    'suggest'         => 'Sarankan',
                    'trigger'         => 'Pemicu',
                ],
            ],
            'status-and-configuration-information' => [
                'title'  => 'Status & Konfigurasi',
                'fields' => [
                    'status'               => 'Status',
                    'keep-done-activities' => 'Simpan Aktivitas Selesai',
                ],
            ],
        ],
    ],
    'table' => [
        'columns' => [
            'name'       => 'Jenis Aktivitas',
            'summary'    => 'Ringkasan',
            'planned-in' => 'Direncanakan Dalam',
            'type'       => 'Jenis',
            'action'     => 'Tindakan',
            'status'     => 'Status',
            'created-at' => 'Dibuat Pada',
            'updated-at' => 'Diperbarui Pada',
        ],
        'groups' => [
            'name'             => 'Nama',
            'action-category'  => 'Kategori Tindakan',
            'status'           => 'Status',
            'delay-count'      => 'Hitungan Penundaan',
            'delay-unit'       => 'Satuan Penundaan',
            'delay-source'     => 'Sumber Penundaan',
            'associated-model' => 'Model Terkait',
            'chaining-type'    => 'Tipe Rantai',
            'decoration-type'  => 'Jenis Dekorasi',
            'default-user'     => 'Pengguna Bawaan',
            'creation-date'    => 'Tanggal Pembuatan',
            'last-update'      => 'Pembaruan Terakhir',
        ],
        'filters' => [
            'action'    => 'Tindakan',
            'status'    => 'Status',
            'has-delay' => 'Memiliki Penundaan',
        ],
        'actions' => [
            'restore' => [
                'notification' => [
                    'title' => 'Jenis aktivitas dipulihkan',
                    'body'  => 'Jenis aktivitas telah berhasil dipulihkan.',
                ],
            ],
            'delete' => [
                'notification' => [
                    'title' => 'Jenis aktivitas dihapus',
                    'body'  => 'Jenis aktivitas telah berhasil dihapus.',
                ],
            ],
            'force-delete' => [
                'notification' => [
                    'success' => [
                        'title' => 'Jenis aktivitas dihapus secara paksa',
                        'body'  => 'Jenis Aktivitas telah berhasil dihapus paksa.',
                    ],
                    'error' => [
                        'title' => 'Jenis aktivitas tidak dapat dihapus',
                        'body'  => 'Jenis Aktivitas tidak dapat dihapus karena sedang digunakan.',
                    ],
                ],
            ],
        ],
        'bulk-actions' => [
            'restore' => [
                'notification' => [
                    'title' => 'Jenis aktivitas dipulihkan',
                    'body'  => 'Jenis aktivitas telah berhasil dipulihkan.',
                ],
            ],
            'delete' => [
                'notification' => [
                    'title' => 'Jenis aktivitas dihapus',
                    'body'  => 'Jenis aktivitas telah berhasil dihapus.',
                ],
            ],
            'force-delete' => [
                'notification' => [
                    'title' => 'Jenis aktivitas dihapus paksa',
                    'body'  => 'Jenis aktivitas telah berhasil dihapus paksa.',
                ],
            ],
        ],
    ],
    'infolist' => [
        'sections' => [
            'activity-type-details' => [
                'title'   => 'Informasi Umum',
                'entries' => [
                    'name'         => 'Jenis Aktivitas',
                    'name-tooltip' => 'Masukkan nama jenis aktivitas resmi',
                    'action'       => 'Tindakan',
                    'default-user' => 'Pengguna Bawaan',
                    'plugin'       => 'Plugin',
                    'summary'      => 'Ringkasan',
                    'note'         => 'Catatan',
                ],
            ],
            'delay-information' => [
                'title'   => 'Informasi Penundaan',
                'entries' => [
                    'delay-count'            => 'Hitungan Penundaan',
                    'delay-unit'             => 'Satuan Penundaan',
                    'delay-form'             => 'Sumber Penundaan',
                    'delay-form-helper-text' => 'Sumber perhitungan penundaan',
                ],
            ],
            'advanced-information' => [
                'title'   => 'Informasi Lanjutan',
                'entries' => [
                    'icon'            => 'Ikon',
                    'decoration-type' => 'Jenis Dekorasi',
                    'chaining-type'   => 'Tipe Rantai',
                    'suggest'         => 'Sarankan',
                    'trigger'         => 'Pemicu',
                ],
            ],
            'status-and-configuration-information' => [
                'title'   => 'Status & Konfigurasi',
                'entries' => [
                    'status'               => 'Status',
                    'keep-done-activities' => 'Simpan Aktivitas Selesai',
                ],
            ],
        ],
    ],
];
