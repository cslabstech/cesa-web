<?php

return [
    'navigation' => [
        'label' => 'Lowongan Kerja',
    ],
    'model' => [
        'singular' => 'Lowongan Kerja',
        'plural'   => 'Lowongan Kerja',
    ],
    'generated' => [
        'title' => 'Lowongan Kerja #:id',
    ],
    'form' => [
        'sections' => [
            'job_information' => 'Informasi Lowongan',
            'details'         => 'Detail',
            'settings'        => 'Pengaturan',
        ],
        'fields' => [
            'request_man_power_id'  => 'Permintaan Tenaga Kerja Terkait (Opsional)',
            'rekrutmen_pipeline_id' => 'Pipeline Rekrutmen',
            'title'                 => 'Judul',
            'slug'                  => 'Slug',
            'location'              => 'Lokasi Penempatan',
            'thumbnail_path'        => 'Thumbnail',
            'closing_date'          => 'Tanggal Penutupan',
            'is_published'          => 'Dipublikasikan untuk Rekrutmen',
            'description'           => 'Job Desk',
            'requirements'          => 'Kualifikasi',
        ],
    ],
    'table' => [
        'columns' => [
            'title'              => 'Judul',
            'rekrutmen_pipeline' => 'Pipeline',
            'request_man_power'  => 'Permintaan Tenaga Kerja',
            'thumbnail_path'     => 'Thumbnail',
            'location'           => 'Lokasi Penempatan',
            'applications_count' => 'Pelamar',
            'is_published'       => 'Dipublikasikan',
            'closing_date'       => 'Tanggal Penutupan',
        ],
        'placeholders' => [
            'request_man_power' => '-',
        ],
        'filters' => [
            'is_published'          => 'Dipublikasikan',
            'rekrutmen_pipeline_id' => 'Pipeline',
            'request_man_power_id'  => 'Permintaan Tenaga Kerja',
            'availability'          => 'Ketersediaan',
        ],
        'filter_options' => [
            'availability' => [
                'open'    => 'Masih Dibuka',
                'expired' => 'Sudah Ditutup',
            ],
        ],
        'actions' => [
            'open_pipeline' => 'Buka Pipeline Kandidat',
        ],
    ],
];
