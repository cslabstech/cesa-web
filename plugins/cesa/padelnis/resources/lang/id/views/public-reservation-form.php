<?php

return [
    'title'       => 'TICKETING RESERVASI PADELNIS',
    'description' => 'Silakan lengkapi data reservasi di bawah ini.',
    'required'    => '* Pertanyaan wajib diisi',

    'placeholders' => [
        'customer_name'    => 'Masukkan nama customer',
        'reservation_date' => 'Pilih tanggal reservasi',
        'court'            => 'Pilih lapangan',
        'reservation_time' => 'Pilih jam',
        'transfer_amount'  => 'Contoh: 150.000',
    ],

    'actions' => [
        'submit'         => 'Kirim Reservasi',
        'submit_another' => 'Kirim reservasi lain',
    ],

    'pagination' => [
        'single_page' => 'Halaman :current dari :total',
    ],

    'summary' => [
        'title'       => 'Reservasi Berhasil Dikirim',
        'description' => 'Data reservasi sudah masuk ke menu Reservasi.',
    ],

    'messages' => [
        'generic' => 'Terjadi kesalahan saat mengirim reservasi. Silakan coba lagi.',
    ],

    'notifications' => [
        'submitted' => [
            'title' => 'Reservasi terkirim',
            'body'  => 'Reservasi berhasil disimpan dengan ID Reff :id_reff.',
        ],
        'failed' => [
            'title' => 'Reservasi gagal dikirim',
            'body'  => 'Reservasi belum bisa disimpan. Silakan coba lagi.',
        ],
    ],
];
