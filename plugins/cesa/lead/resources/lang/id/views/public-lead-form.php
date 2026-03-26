<?php

return [
    'title'       => 'Form Lead',
    'description' => 'Silakan lengkapi detail lead di bawah ini.',
    'required'    => '* Pertanyaan wajib diisi',

    'actions' => [
        'submit'         => 'Kirim',
        'submit_another' => 'Kirim lead lain',
    ],

    'pagination' => [
        'single_page' => 'Halaman :current dari :total',
    ],

    'summary' => [
        'title'              => 'Lead Berhasil Dikirim',
        'description'        => 'Gunakan ringkasan berikut untuk memastikan data lead yang dikirim sudah sesuai.',
        'submitted_by'       => 'Dikirim oleh',
        'current_status'     => 'Status saat ini',
        'status_submitted'   => 'Terkirim',
        'submission_summary' => 'Ringkasan Pengajuan',
        'fields'             => [
            'response_id'  => 'ID Lead',
            'form'         => 'Form',
            'submitted_at' => 'Waktu Pengiriman',
            'address'      => 'Alamat',
        ],
    ],

    'messages' => [
        'success' => 'Terima kasih. Lead Anda berhasil dikirim.',
        'generic' => 'Terjadi kesalahan saat mengirim formulir. Silakan coba lagi.',
    ],

    'notifications' => [
        'submitted' => [
            'title' => 'Lead terkirim',
            'body'  => 'Terima kasih, lead berhasil disimpan.',
        ],
    ],

    'whatsapp_validation' => [
        'action'         => 'Cek WhatsApp',
        'hint'           => 'Gunakan pengecekan ini untuk memastikan nomor terdaftar di WhatsApp.',
        'success'        => 'Nomor ini terdaftar di WhatsApp.',
        'not_registered' => 'Nomor ini tidak terdaftar di WhatsApp.',
        'invalid'        => 'Nomor tidak valid.',
        'rate_limited'   => 'Terlalu banyak percobaan. Silakan coba lagi sebentar.',
        'failed'         => 'Validasi WhatsApp gagal. Silakan coba lagi.',
    ],
];
