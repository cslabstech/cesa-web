<?php

return [
    'setup' => [
        'title'        => 'Kirim Pesan',
        'submit-title' => 'Mengirim',
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
                    'title' => 'Pesan terkirim',
                    'body'  => 'Pesan Anda telah berhasil dikirim.',
                ],
                'error' => [
                    'title' => 'Kesalahan pengiriman pesan',
                    'body'  => 'Gagal mengirim pesan Anda',
                ],
            ],
            'mail' => [
                'subject' => ':record_name',
            ],
        ],
    ],
];
