<?php

return [
    'actions' => [
        'start_progress' => [
            'label' => 'Mulai Proses',
        ],
        'close_ticket' => [
            'label'  => 'Tutup Tiket',
            'reason' => 'Alasan Penutupan',
        ],
        'cancel_ticket' => [
            'label'  => 'Batalkan Tiket',
            'reason' => 'Alasan Pembatalan',
        ],
        'reopen_ticket' => [
            'label'  => 'Buka Ulang Tiket',
            'reason' => 'Alasan Buka Ulang',
        ],
    ],
    'errors' => [
        'invalid_user' => 'Pengguna terautentikasi tidak valid.',
    ],
];
