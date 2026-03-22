<?php

return [
    'title'      => 'Perusahaan',
    'navigation' => [
        'title' => 'Perusahaan',
        'group' => 'Pengaturan',
    ],
    'global-search' => [
        'email' => 'Email',
    ],
    'form' => [
        'sections' => [
            'company-information' => [
                'title'  => 'Informasi Perusahaan',
                'fields' => [
                    'name'                => 'Nama Perusahaan',
                    'registration-number' => 'Nomor pendaftaran',
                    'company-id'          => 'ID Perusahaan',
                    'tax-id'              => 'Nomor Pajak',
                    'tax-id-tooltip'      => 'Nomor Pajak adalah pengidentifikasi unik untuk perusahaan Anda.',
                    'website'             => 'Situs web',
                ],
            ],
            'address-information' => [
                'title'  => 'Informasi Alamat',
                'fields' => [
                    'street1'        => 'Jalan 1',
                    'street2'        => 'Jalan 2',
                    'city'           => 'Kota',
                    'zipcode'        => 'Kode pos',
                    'country'        => 'Negara',
                    'currency-name'  => 'Nama Mata Uang',
                    'phone-code'     => 'Kode Telepon',
                    'code'           => 'Kode',
                    'country-name'   => 'Nama Negara',
                    'state-required' => 'Provinsi wajib diisi',
                    'zip-required'   => 'Kode pos wajib diisi',
                    'create-country' => 'Buat Negara',
                    'state'          => 'Provinsi',
                    'state-name'     => 'Nama Provinsi',
                    'state-code'     => 'Kode Provinsi',
                    'create-state'   => 'Buat Provinsi',
                ],
            ],
            'additional-information' => [
                'title'  => 'Informasi Tambahan',
                'fields' => [
                    'default-currency'        => 'Mata Uang Bawaan',
                    'currency-name'           => 'Nama Mata Uang',
                    'currency-full-name'      => 'Nama Lengkap Mata Uang',
                    'currency-symbol'         => 'Simbol Mata Uang',
                    'currency-iso-numeric'    => 'Numerik ISO Mata Uang',
                    'currency-decimal-places' => 'Tempat Desimal Mata Uang',
                    'currency-rounding'       => 'Pembulatan Mata Uang',
                    'currency-status'         => 'Status Mata Uang',
                    'company-foundation-date' => 'Tanggal Pendirian Perusahaan',
                    'currency-create'         => 'Buat Mata Uang',
                    'status'                  => 'Status',
                ],
            ],
            'branding' => [
                'title'  => 'Merek',
                'fields' => [
                    'company-logo' => 'Logo Perusahaan',
                    'color'        => 'Warna',
                ],
            ],
            'contact-information' => [
                'title'  => 'Informasi Kontak',
                'fields' => [
                    'email'  => 'Alamat Email',
                    'phone'  => 'Nomor Telepon',
                    'mobile' => 'Nomor Ponsel',
                ],
            ],
        ],
    ],
    'table' => [
        'columns' => [
            'logo'         => 'Logo',
            'company-name' => 'Nama Perusahaan',
            'branches'     => 'Cabang',
            'email'        => 'Email',
            'city'         => 'Kota',
            'country'      => 'Negara',
            'currency'     => 'Mata uang',
            'created-by'   => 'Dibuat Oleh',
            'status'       => 'Status',
            'created-at'   => 'Dibuat Pada',
            'updated-at'   => 'Diperbarui Pada',
        ],
        'groups' => [
            'company-name' => 'Nama Perusahaan',
            'city'         => 'Kota',
            'country'      => 'Negara',
            'state'        => 'Provinsi',
            'email'        => 'Email',
            'phone'        => 'Telepon',
            'currency'     => 'Mata uang',
            'created-by'   => 'Dibuat Oleh',
            'created-at'   => 'Dibuat Pada',
            'updated-at'   => 'Diperbarui Pada',
        ],
        'filters' => [
            'status'  => 'Status',
            'country' => 'Negara',
        ],
        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Perusahaan diperbarui',
                    'body'  => 'Perusahaan berhasil diperbarui.',
                ],
            ],
            'delete' => [
                'notification' => [
                    'title'           => 'Perusahaan dihapus',
                    'body'            => 'Perusahaan telah berhasil dihapus.',
                    'default-company' => [
                        'title' => 'Perusahaan tidak dapat dihapus',
                        'body'  => 'Perusahaan ini ditetapkan sebagai perusahaan default di pengaturan Kelola Pengguna. Silakan ubah perusahaan default sebelum menghapus.',
                    ],
                ],
            ],
            'restore' => [
                'notification' => [
                    'title' => 'Perusahaan dipulihkan',
                    'body'  => 'Perusahaan telah berhasil dipulihkan.',
                ],
            ],
            'force-delete' => [
                'notification' => [
                    'success' => [
                        'title' => 'Kekuatan perusahaan dihapus',
                        'body'  => 'Perusahaan telah berhasil dihapus secara paksa.',
                    ],
                    'error' => [
                        'title' => 'Tidak dapat menghapus paksa perusahaan',
                        'body'  => 'Perusahaan ini dikaitkan dengan catatan yang ada dan tidak dapat dihapus.',
                    ],
                ],
            ],
        ],
        'bulk-actions' => [
            'restore' => [
                'notification' => [
                    'title' => 'Perusahaan dipulihkan',
                    'body'  => 'Perusahaan telah berhasil dipulihkan.',
                ],
            ],
            'delete' => [
                'notification' => [
                    'title' => 'Perusahaan dihapus',
                    'body'  => 'Perusahaan telah berhasil dihapus.',
                ],
            ],
            'force-delete' => [
                'notification' => [
                    'title' => 'Perusahaan dihapus paksa',
                    'body'  => 'Perusahaan telah berhasil dihapus secara paksa.',
                    'error' => [
                        'title' => 'Tidak dapat menghapus paksa perusahaan',
                        'body'  => 'Satu atau lebih perusahaan dikaitkan dengan catatan yang ada dan tidak dapat dihapus.',
                    ],
                ],
            ],
        ],
        'empty-state-actions' => [
            'create' => [
                'notification' => [
                    'title' => 'Perusahaan dibuat',
                    'body'  => 'Perusahaan telah berhasil didirikan.',
                ],
            ],
        ],
    ],
    'infolist' => [
        'sections' => [
            'company-information' => [
                'title'   => 'Informasi Perusahaan',
                'entries' => [
                    'name'                => 'Nama Perusahaan',
                    'registration-number' => 'Nomor pendaftaran',
                    'company-id'          => 'ID Perusahaan',
                    'tax-id'              => 'Nomor Pajak',
                    'tax-id-tooltip'      => 'Nomor Pajak adalah pengidentifikasi unik untuk perusahaan Anda.',
                    'website'             => 'Situs web',
                ],
            ],
            'address-information' => [
                'title'   => 'Informasi Alamat',
                'entries' => [
                    'street1'        => 'Jalan 1',
                    'street2'        => 'Jalan 2',
                    'city'           => 'Kota',
                    'zipcode'        => 'Kode pos',
                    'country'        => 'Negara',
                    'currency-name'  => 'Nama Mata Uang',
                    'phone-code'     => 'Kode Telepon',
                    'code'           => 'Kode',
                    'country-name'   => 'Nama Negara',
                    'state-required' => 'Provinsi wajib diisi',
                    'zip-required'   => 'Kode pos wajib diisi',
                    'create-country' => 'Buat Negara',
                    'state'          => 'Provinsi',
                    'state-name'     => 'Nama Provinsi',
                    'state-code'     => 'Kode Provinsi',
                    'create-state'   => 'Buat Provinsi',
                ],
            ],
            'additional-information' => [
                'title'   => 'Informasi Tambahan',
                'entries' => [
                    'default-currency'        => 'Mata Uang Bawaan',
                    'currency-name'           => 'Nama Mata Uang',
                    'currency-full-name'      => 'Nama Lengkap Mata Uang',
                    'currency-symbol'         => 'Simbol Mata Uang',
                    'currency-iso-numeric'    => 'Numerik ISO Mata Uang',
                    'currency-decimal-places' => 'Tempat Desimal Mata Uang',
                    'currency-rounding'       => 'Pembulatan Mata Uang',
                    'currency-status'         => 'Status Mata Uang',
                    'company-foundation-date' => 'Tanggal Pendirian Perusahaan',
                    'currency-create'         => 'Buat Mata Uang',
                    'status'                  => 'Status',
                ],
            ],
            'branding' => [
                'title'   => 'Merek',
                'entries' => [
                    'company-logo' => 'Logo Perusahaan',
                    'color'        => 'Warna',
                ],
            ],
            'contact-information' => [
                'title'   => 'Informasi Kontak',
                'entries' => [
                    'email'  => 'Alamat Email',
                    'phone'  => 'Nomor Telepon',
                    'mobile' => 'Nomor Ponsel',
                ],
            ],
        ],
    ],
];
