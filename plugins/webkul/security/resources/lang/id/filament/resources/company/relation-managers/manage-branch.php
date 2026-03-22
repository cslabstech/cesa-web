<?php

return [
    'form' => [
        'tabs' => [
            'general-information' => [
                'title'    => 'Informasi Umum',
                'sections' => [
                    'branch-information' => [
                        'title'  => 'Informasi Cabang',
                        'fields' => [
                            'company-name'        => 'Nama Perusahaan',
                            'registration-number' => 'Nomor pendaftaran',
                            'tax-id'              => 'Nomor Pajak',
                            'tax-id-tooltip'      => 'Nomor Pajak adalah pengidentifikasi unik untuk perusahaan Anda.',
                            'color'               => 'Warna',
                            'company-id'          => 'ID Perusahaan',
                            'company-id-tooltip'  => 'ID Perusahaan adalah pengidentifikasi unik untuk perusahaan Anda.',
                        ],
                    ],
                    'branding' => [
                        'title'  => 'Merek',
                        'fields' => [
                            'branch-logo' => 'Logo Cabang',
                        ],
                    ],
                ],
            ],
            'address-information' => [
                'title'    => 'Informasi Alamat',
                'sections' => [
                    'address-information' => [
                        'title'  => 'Informasi Alamat',
                        'fields' => [
                            'street1'                => 'Jalan 1',
                            'street2'                => 'Jalan 2',
                            'city'                   => 'Kota',
                            'zip'                    => 'Kode pos',
                            'country'                => 'Negara',
                            'country-currency-name'  => 'Nama Mata Uang',
                            'country-phone-code'     => 'Kode Telepon',
                            'country-code'           => 'Kode',
                            'country-name'           => 'Nama Negara',
                            'country-state-required' => 'Provinsi wajib diisi',
                            'country-zip-required'   => 'Kode pos wajib diisi',
                            'country-create'         => 'Buat Negara',
                            'state'                  => 'Provinsi',
                            'state-name'             => 'Nama Provinsi',
                            'state-code'             => 'Kode Provinsi',
                            'zip-code'               => 'Kode pos',
                            'state-create'           => 'Buat Provinsi',
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
                            'currency-create'         => 'Buat Mata Uang',
                            'company-foundation-date' => 'Tanggal Pendirian Perusahaan',
                            'status'                  => 'Status',
                        ],
                    ],
                ],
            ],
            'contact-information' => [
                'title'    => 'Informasi Kontak',
                'sections' => [
                    'contact-information' => [
                        'title'  => 'Informasi Kontak',
                        'fields' => [
                            'email-address' => 'Alamat Email',
                            'phone-number'  => 'Nomor Telepon',
                            'mobile-number' => 'Nomor Ponsel',
                        ],
                    ],
                ],
            ],
        ],
    ],
    'table' => [
        'columns' => [
            'logo'         => 'Logo',
            'company-name' => 'Nama Cabang',
            'branches'     => 'Cabang',
            'email'        => 'Email',
            'city'         => 'Kota',
            'country'      => 'Negara',
            'currency'     => 'Mata uang',
            'status'       => 'Status',
            'created-at'   => 'Dibuat Pada',
            'updated-at'   => 'Diperbarui Pada',
        ],
        'groups' => [
            'company-name' => 'Nama Cabang',
            'city'         => 'Kota',
            'country'      => 'Negara',
            'state'        => 'Provinsi',
            'email'        => 'Email',
            'phone'        => 'Telepon',
            'currency'     => 'Mata uang',
            'created-at'   => 'Dibuat Pada',
            'updated-at'   => 'Diperbarui Pada',
        ],
        'filters' => [
            'trashed' => 'Dibuang',
            'status'  => 'Status',
            'country' => 'Negara',
        ],
        'header-actions' => [
            'create' => [
                'notification' => [
                    'title' => 'Cabang dibuat',
                    'body'  => 'Cabang telah berhasil dibuat.',
                ],
            ],
        ],
        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Cabang diperbarui',
                    'body'  => 'Cabang telah berhasil diperbarui.',
                ],
            ],
            'delete' => [
                'notification' => [
                    'title' => 'Cabang dihapus',
                    'body'  => 'Cabang telah berhasil dihapus.',
                ],
            ],
            'restore' => [
                'notification' => [
                    'title' => 'Cabang dipulihkan',
                    'body'  => 'Cabang telah berhasil dipulihkan.',
                ],
            ],
        ],
        'bulk-actions' => [
            'restore' => [
                'notification' => [
                    'title' => 'Cabang dipulihkan',
                    'body'  => 'Cabang-cabang telah berhasil dipulihkan.',
                ],
            ],
            'delete' => [
                'notification' => [
                    'title' => 'Cabang dihapus',
                    'body'  => 'Cabang telah berhasil dihapus.',
                ],
            ],
            'force-delete' => [
                'notification' => [
                    'title' => 'Cabang dihapus paksa',
                    'body'  => 'Cabang telah berhasil dihapus secara paksa.',
                ],
            ],
        ],
    ],
    'infolist' => [
        'tabs' => [
            'general-information' => [
                'title'    => 'Informasi Umum',
                'sections' => [
                    'branch-information' => [
                        'title'   => 'Informasi Cabang',
                        'entries' => [
                            'company-name'                => 'Nama Perusahaan',
                            'registration-number'         => 'Nomor pendaftaran',
                            'registration-number-tooltip' => 'Nomor Pajak adalah pengidentifikasi unik untuk perusahaan Anda.',
                            'color'                       => 'Warna',
                        ],
                    ],
                    'branding' => [
                        'title'   => 'Merek',
                        'entries' => [
                            'branch-logo' => 'Logo Cabang',
                        ],
                    ],
                ],
            ],
            'address-information' => [
                'title'    => 'Informasi Alamat',
                'sections' => [
                    'address-information' => [
                        'title'   => 'Informasi Alamat',
                        'entries' => [
                            'street1'                => 'Jalan 1',
                            'street2'                => 'Jalan 2',
                            'city'                   => 'Kota',
                            'zip'                    => 'Kode pos',
                            'country'                => 'Negara',
                            'country-currency-name'  => 'Nama Mata Uang',
                            'country-phone-code'     => 'Kode Telepon',
                            'country-code'           => 'Kode',
                            'country-name'           => 'Nama Negara',
                            'country-state-required' => 'Provinsi wajib diisi',
                            'country-zip-required'   => 'Kode pos wajib diisi',
                            'country-create'         => 'Buat Negara',
                            'state'                  => 'Provinsi',
                            'state-name'             => 'Nama Provinsi',
                            'state-code'             => 'Kode Provinsi',
                            'zip-code'               => 'Kode pos',
                            'state-create'           => 'Buat Provinsi',
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
                            'currency-create'         => 'Buat Mata Uang',
                            'company-foundation-date' => 'Tanggal Pendirian Perusahaan',
                            'status'                  => 'Status',
                        ],
                    ],
                ],
            ],
            'contact-information' => [
                'title'    => 'Informasi Kontak',
                'sections' => [
                    'contact-information' => [
                        'title'   => 'Informasi Kontak',
                        'entries' => [
                            'email-address' => 'Alamat Email',
                            'phone-number'  => 'Nomor Telepon',
                            'mobile-number' => 'Nomor Ponsel',
                        ],
                    ],
                ],
            ],
        ],
    ],
];
