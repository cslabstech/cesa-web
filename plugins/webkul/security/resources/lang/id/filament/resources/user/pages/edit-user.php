<?php

return [
    'notification' => [
        'title' => 'Pengguna diperbarui',
        'body'  => 'Pengguna telah berhasil diperbarui.',
    ],
    'header-actions' => [
        'change-password' => [
            'label'        => 'Ubah Kata Sandi',
            'notification' => [
                'title' => 'Kata sandi diubah',
                'body'  => 'Kata sandi telah berhasil diubah.',
            ],
            'form' => [
                'new-password'         => 'Kata Sandi Baru',
                'confirm-new-password' => 'Konfirmasi Kata Sandi Baru',
            ],
        ],
        'delete' => [
            'notification' => [
                'title' => 'Pengguna dihapus',
                'body'  => 'Pengguna telah berhasil dihapus.',
                'error' => [
                    'title' => 'Pengguna Tidak Dapat Dihapus',
                    'body'  => 'Ini adalah pengguna default atau Anda tidak dapat menghapusnya sendiri.',
                ],
            ],
        ],
    ],
];
