<?php

return [
    'setup' => [
        'title'   => 'Lampiran',
        'tooltip' => 'Unggah Lampiran',
        'form'    => [
            'fields' => [
                'files'                  => 'File',
                'attachment-helper-text' => 'Ukuran file maksimal: 10MB. Jenis yang diperbolehkan: Gambar, PDF, Word, Excel, Teks',
                'actions'                => [
                    'delete' => [
                        'title' => 'Berkas dihapus',
                        'body'  => 'File telah berhasil dihapus.',
                    ],
                ],
            ],
        ],
        'actions' => [
            'notification' => [
                'success' => [
                    'title' => 'Lampiran Diunggah',
                    'body'  => 'Lampiran berhasil diunggah.',
                ],
                'warning' => [
                    'title' => 'Tidak ada file baru',
                    'body'  => 'Semua file telah diunggah.',
                ],
                'error' => [
                    'title' => 'Kesalahan pengunggahan lampiran',
                    'body'  => 'Gagal mengunggah lampiran',
                ],
            ],
        ],
    ],
];
