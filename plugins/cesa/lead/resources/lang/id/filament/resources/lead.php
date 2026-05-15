<?php

return [
    'title' => 'Lead',

    'navigation' => [
        'title' => 'Lead',
        'group' => null,
    ],

    'singular' => 'Lead',
    'plural'   => 'Lead',

    'form' => [
        'sections' => [
            'basic_information' => [
                'title' => 'Informasi Dasar',

                'fields' => [
                    'name'    => 'Nama Lengkap',
                    'phone'   => 'Nomor Telepon',
                    'address' => 'Alamat',
                ],
            ],
            'store_information' => [
                'title' => 'Informasi Toko',

                'fields' => [
                    'sales_person'            => 'Sales Person',
                    'store_team_position'     => 'Jabatan Tim Toko',
                    'store_branch'            => 'Cabang Toko',
                    'phone_transaction_range' => 'Rentang Transaksi Handphone',
                ],
            ],
        ],

        'placeholders' => [
            'name'                    => 'Masukkan nama lengkap lead',
            'phone'                   => 'Contoh: 08123456789 atau 628123456789',
            'address'                 => 'Masukkan alamat lengkap',
            'sales_person'            => 'Masukkan nama sales person',
            'choose'                  => 'Pilih salah satu',
            'store_branch'            => 'Pilih cabang toko',
            'phone_transaction_range' => 'Pilih rentang harga',
        ],
    ],

    'fields' => [
        'name'                    => 'Nama Lengkap',
        'phone'                   => 'Nomor Telepon',
        'address'                 => 'Alamat',
        'sales_person'            => 'Sales Person',
        'store_team_position'     => 'Jabatan Tim Toko',
        'store_branch'            => 'Cabang Toko',
        'phone_transaction_range' => 'Rentang Transaksi Handphone',
        'creator_id'              => 'Dibuat Oleh',
        'created_at'              => 'Dibuat Pada',
    ],

    'options' => [
        'store_team_position' => [
            'kepala_toko' => 'Kepala Toko',
            'promotor'    => 'Promotor',
            'kasir'       => 'Kasir',
            'frontliner'  => 'Frontliner',
        ],
        'phone_transaction_range' => [
            'below_2m' => 'Di bawah Rp2 juta',
            '2m_3m'    => 'Rp2 juta sampai Rp3 juta',
            '3m_4m'    => 'Rp3 juta sampai Rp4 juta',
            '4m_7m'    => 'Rp4 juta sampai Rp7 juta',
            'above_7m' => 'Di atas Rp7 juta',
        ],
    ],

    'table' => [
        'columns' => [
            'name'                    => 'Nama',
            'phone'                   => 'Nomor Telepon',
            'sales_person'            => 'Sales Person',
            'store_team_position'     => 'Jabatan Tim Toko',
            'store_branch'            => 'Cabang Toko',
            'phone_transaction_range' => 'Rentang Harga',
            'created_at'              => 'Dibuat Pada',
        ],
    ],

    'filters' => [
        'created_from'            => 'Dari',
        'created_until'           => 'Sampai',
        'date_range'              => 'Rentang Tanggal',
        'store_team_position'     => 'Jabatan Tim Toko',
        'store_branch'            => 'Cabang Toko',
        'phone_transaction_range' => 'Rentang Transaksi Handphone',
    ],

    'actions' => [
        'copy_phone' => 'Nomor telepon berhasil disalin.',
    ],

    'imports' => [
        'columns' => [
            'name'                    => 'Nama',
            'phone'                   => 'Nomor Telepon',
            'address'                 => 'Alamat',
            'sales_person'            => 'Sales Person',
            'store_team_position'     => 'Jabatan Tim Toko',
            'store_branch'            => 'Cabang Toko',
            'phone_transaction_range' => 'Rentang Transaksi Handphone',
        ],
        'notifications' => [
            'completed_title' => 'Impor lead selesai',
            'completed_body'  => 'Impor lead selesai dengan :success baris berhasil dan :failed baris gagal.',
        ],
    ],

    'exports' => [
        'notifications' => [
            'completed_body' => 'Ekspor lead selesai dengan :success baris berhasil diekspor dan :failed baris gagal diekspor.',
        ],
    ],

    'notifications' => [
        'created' => [
            'title' => 'Lead berhasil dibuat',
            'body'  => 'Lead berhasil ditambahkan.',
        ],
        'updated' => [
            'title' => 'Lead berhasil diperbarui',
            'body'  => 'Data lead berhasil diperbarui.',
        ],
        'deleted' => [
            'title' => 'Lead berhasil dihapus',
            'body'  => 'Lead berhasil dihapus.',
        ],
    ],

    'validation' => [
        'phone_required' => 'Nomor telepon wajib diisi.',
        'phone_format'   => 'Nomor telepon harus menggunakan format 62xxxxxxxxxx dan minimal terdiri dari 10 digit.',
        'phone_unique'   => 'Nomor telepon sudah terdaftar.',
    ],
];
