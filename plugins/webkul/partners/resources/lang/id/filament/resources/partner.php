<?php

return [
    'global-search' => [
        'email' => 'Email',
        'phone' => 'Telepon',
    ],
    'form' => [
        'sections' => [
            'general' => [
                'title'  => 'Umum',
                'fields' => [
                    'company'    => 'Perusahaan',
                    'avatar'     => 'Avatar',
                    'tax-id'     => 'Nomor Pajak',
                    'job-title'  => 'Jabatan',
                    'phone'      => 'Telepon',
                    'mobile'     => 'Seluler',
                    'email'      => 'Email',
                    'website'    => 'Situs web',
                    'title'      => 'Judul',
                    'name'       => 'Nama',
                    'short-name' => 'Nama Pendek',
                    'tags'       => 'Tag',
                    'color'      => 'Warna',
                ],
                'address' => [
                    'title'  => 'Alamat',
                    'fields' => [
                        'street1' => 'Jalan 1',
                        'street2' => 'Jalan 2',
                        'city'    => 'Kota',
                        'zip'     => 'Kode Pos',
                        'state'   => 'Provinsi',
                        'country' => 'Negara',
                        'name'    => 'Nama',
                        'code'    => 'Kode',
                    ],
                ],
            ],
        ],
        'tabs' => [
            'sales-purchase' => [
                'title'  => 'Penjualan dan Pembelian',
                'fields' => [
                    'responsible'           => 'Penanggung Jawab',
                    'responsible-hint-text' => 'Ini adalah sales internal yang bertanggung jawab atas pelanggan ini.',
                    'company-id'            => 'ID Perusahaan',
                    'company-id-hint-text'  => 'Nomor registrasi perusahaan. Gunakan jika berbeda dengan NPWP. Nilai ini harus unik untuk semua mitra di negara yang sama.',
                    'reference'             => 'Referensi',
                    'industry'              => 'Industri',
                ],
            ],
        ],
    ],
    'table' => [
        'columns' => [
            'parent' => 'Induk',
        ],
        'groups' => [
            'account-type' => 'Jenis Akun',
            'parent'       => 'Induk',
            'title'        => 'Judul',
            'job-title'    => 'Jabatan',
            'industry'     => 'Industri',
        ],
        'filters' => [
            'account-type'     => 'Jenis Akun',
            'name'             => 'Nama',
            'email'            => 'Email',
            'parent'           => 'Induk',
            'title'            => 'Judul',
            'tax-id'           => 'Nomor Pajak',
            'phone'            => 'Telepon',
            'mobile'           => 'Seluler',
            'job-title'        => 'Jabatan',
            'website'          => 'Situs web',
            'company-registry' => 'Nomor Registrasi Perusahaan',
            'responsible'      => 'Penanggung Jawab',
            'reference'        => 'Referensi',
            'creator'          => 'Dibuat Oleh',
            'company'          => 'Perusahaan',
            'industry'         => 'Industri',
        ],
        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Kontak diperbarui',
                    'body'  => 'Kontak telah berhasil diperbarui.',
                ],
            ],
            'restore' => [
                'notification' => [
                    'title' => 'Kontak dipulihkan',
                    'body'  => 'Kontak telah berhasil dipulihkan.',
                ],
            ],
            'delete' => [
                'notification' => [
                    'title' => 'Kontak dihapus',
                    'body'  => 'Kontak telah berhasil dihapus.',
                ],
            ],
            'force-delete' => [
                'notification' => [
                    'success' => [
                        'title' => 'Kontak paksa dihapus',
                        'body'  => 'Kontak telah berhasil dihapus secara paksa.',
                    ],
                    'error' => [
                        'title' => 'Kontak tidak dapat dihapus',
                        'body'  => 'Kontak tidak dapat dihapus karena sedang digunakan.',
                    ],
                ],
            ],
        ],
        'bulk-actions' => [
            'restore' => [
                'notification' => [
                    'title' => 'Kontak dipulihkan',
                    'body'  => 'Kontak telah berhasil dipulihkan.',
                ],
            ],
            'delete' => [
                'notification' => [
                    'title' => 'Kontak dihapus',
                    'body'  => 'Kontak telah berhasil dihapus.',
                ],
            ],
            'force-delete' => [
                'notification' => [
                    'success' => [
                        'title' => 'Kontak dihapus paksa',
                        'body'  => 'Kontak telah berhasil dihapus secara paksa.',
                    ],
                    'error' => [
                        'title' => 'Kontak tidak dapat dihapus',
                        'body'  => 'Kontak tidak dapat dihapus karena sedang digunakan.',
                    ],
                ],
            ],
        ],
    ],
    'infolist' => [
        'sections' => [
            'general' => [
                'title'  => 'Umum',
                'fields' => [
                    'company'    => 'Perusahaan',
                    'avatar'     => 'Avatar',
                    'tax-id'     => 'Nomor Pajak',
                    'job-title'  => 'Jabatan',
                    'phone'      => 'Telepon',
                    'mobile'     => 'Seluler',
                    'email'      => 'Email',
                    'website'    => 'Situs web',
                    'title'      => 'Judul',
                    'name'       => 'Nama',
                    'short-name' => 'Nama Pendek',
                    'tags'       => 'Tag',
                ],
                'address' => [
                    'title'  => 'Alamat',
                    'fields' => [
                        'street1' => 'Jalan 1',
                        'street2' => 'Jalan 2',
                        'city'    => 'Kota',
                        'zip'     => 'Kode Pos',
                        'state'   => 'Provinsi',
                        'country' => 'Negara',
                        'name'    => 'Nama',
                        'code'    => 'Kode',
                    ],
                ],
            ],
        ],
        'tabs' => [
            'sales-purchase' => [
                'title'  => 'Penjualan dan Pembelian',
                'fields' => [
                    'responsible'           => 'Penanggung Jawab',
                    'responsible-hint-text' => 'Ini adalah sales internal yang bertanggung jawab atas pelanggan ini.',
                    'company-id'            => 'ID Perusahaan',
                    'company-id-hint-text'  => 'Nomor registrasi perusahaan. Gunakan jika berbeda dengan NPWP. Nilai ini harus unik untuk semua mitra di negara yang sama.',
                    'reference'             => 'Referensi',
                    'industry'              => 'Industri',
                ],
            ],
        ],
    ],
];
