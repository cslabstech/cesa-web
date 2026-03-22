<?php

return [
    'form' => [
        'partner' => 'Mitra',
        'name'    => 'Nama',
        'email'   => 'Email',
        'phone'   => 'Telepon',
        'mobile'  => 'Seluler',
        'type'    => 'Jenis',
        'address' => 'Alamat',
        'city'    => 'Kota',
        'street1' => 'Jalan 1',
        'street2' => 'Jalan 2',
        'state'   => 'Provinsi',
        'zip'     => 'Ritsleting',
        'code'    => 'Kode',
        'country' => 'Negara',
    ],
    'table' => [
        'header-actions' => [
            'create' => [
                'label'        => 'Tambahkan Alamat',
                'notification' => [
                    'title' => 'Alamat dibuat',
                    'body'  => 'Alamat telah berhasil dibuat.',
                ],
            ],
        ],
        'columns' => [
            'type'    => 'Jenis',
            'name'    => 'Nama Kontak',
            'address' => 'Alamat',
            'city'    => 'Kota',
            'street1' => 'Jalan 1',
            'street2' => 'Jalan 2',
            'state'   => 'Provinsi',
            'zip'     => 'Ritsleting',
            'country' => 'Negara',
        ],
        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Alamat diperbarui',
                    'body'  => 'Alamat telah berhasil diperbarui.',
                ],
            ],
            'delete' => [
                'notification' => [
                    'title' => 'Alamat dihapus',
                    'body'  => 'Alamat telah berhasil dihapus.',
                ],
            ],
        ],
        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Alamat dihapus',
                    'body'  => 'Alamat telah berhasil dihapus.',
                ],
            ],
        ],
    ],
];
