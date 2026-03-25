<?php

return [
    'navigation' => [
        'label' => 'Periode Penggajian',
    ],
    'model' => [
        'singular' => 'Periode Penggajian',
        'plural'   => 'Periode Penggajian',
    ],
    'form' => [
        'sections' => [
            'period_details' => 'Detail Periode',
        ],
        'fields' => [
            'name'                 => 'Nama',
            'start_date'           => 'Tanggal Mulai',
            'end_date'             => 'Tanggal Selesai',
            'status'               => 'Status',
            'auto_generate'        => 'Otomatis Buat Penggajian',
            'auto_generate_helper' => 'Buat penggajian otomatis setelah periode dibuat hanya untuk karyawan yang punya data presensi atau lembur disetujui. Hilangkan centang untuk membuat manual nanti.',
        ],
    ],
    'table' => [
        'columns' => [
            'name'       => 'Nama',
            'start_date' => 'Tanggal Mulai',
            'end_date'   => 'Tanggal Selesai',
            'status'     => 'Status',
            'created_at' => 'Dibuat Pada',
        ],
        'actions' => [
            'generate_payroll' => [
                'label'             => 'Buat Penggajian',
                'modal_heading'     => 'Buat Penggajian',
                'modal_description' => 'Apakah Anda yakin? Ini akan menghitung penggajian hanya untuk karyawan yang memiliki data presensi atau lembur disetujui pada periode ini. Data penggajian yang sudah ada untuk periode ini akan dibuat ulang menggunakan data terbaru.',
            ],
            'mark_as_paid' => [
                'label'             => 'Tandai Sudah Dibayar',
                'modal_description' => 'Apakah Anda yakin? Ini akan menandai periode penggajian sebagai sudah dibayar. Tindakan ini tidak dapat dibatalkan.',
            ],
        ],
    ],
    'notifications' => [
        'payroll_generated' => [
            'title' => 'Berhasil',
            'body'  => 'Penggajian berhasil dibuat.',
        ],
        'marked_as_paid' => [
            'title' => 'Berhasil',
            'body'  => 'Periode penggajian telah ditandai sebagai sudah dibayar.',
        ],
        'generate_failed' => [
            'title' => 'Error',
            'body'  => 'Gagal membuat penggajian: :message',
        ],
    ],
];
