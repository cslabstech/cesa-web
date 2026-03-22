<?php

return [
    'setup' => [
        'title'        => 'Catatan Log',
        'submit-title' => 'Catatan',
        'form'         => [
            'fields' => [
                'hide-subject'            => 'Sembunyikan Subjek',
                'add-subject'             => 'Tambahkan Subjek',
                'subject'                 => 'Subjek',
                'write-message-here'      => 'Tulis pesan Anda di sini',
                'attachments-helper-text' => 'Ukuran file maksimal: 10MB. Jenis yang diperbolehkan: Gambar, PDF, Word, Excel, Teks',
            ],
        ],
        'actions' => [
            'notification' => [
                'success' => [
                    'title' => 'Catatan Log ditambahkan',
                    'body'  => 'Catatan Log Anda berhasil ditambahkan.',
                ],
                'error' => [
                    'title' => 'Kesalahan penambahan log',
                    'body'  => 'Gagal menambahkan catatan log Anda',
                ],
            ],
        ],
    ],
];
