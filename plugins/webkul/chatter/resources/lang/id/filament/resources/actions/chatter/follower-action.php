<?php

return [
    'setup' => [
        'title'               => 'Pengikut',
        'submit-action-title' => 'Tambahkan Pengikut',
        'tooltip'             => 'Tambahkan Pengikut',
        'form'                => [
            'fields' => [
                'recipients'  => 'Penerima',
                'notify-user' => 'Beritahu Pengguna',
                'add-a-note'  => 'Tambahkan catatan',
            ],
        ],
        'actions' => [
            'notification' => [
                'success' => [
                    'title' => 'Pengikut Ditambahkan',
                    'body'  => 'Pengikut telah berhasil ditambahkan.',
                ],
                'partial_message' => [
                    'title'    => 'Pesan dikirim dengan pemberitahuan',
                    'single'   => ':count pengikut tidak diberitahu karena email hilang: :names',
                    'multiple' => ':count pengikut tidak diberi tahu karena email hilang: :names',
                ],
                'error' => [
                    'title' => 'Kesalahan penambahan pengikut',
                    'body'  => 'Gagal menjadi ":partner" sebagai pengikut',
                ],
            ],
            'mail' => [
                'subject' => 'Undangan untuk mengikuti :model: :department',
            ],
        ],
    ],
];
