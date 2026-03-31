<?php

return [
    'navigation' => [
        'label' => 'Lowongan Kerja',
    ],
    'model' => [
        'singular' => 'Lowongan Kerja',
        'plural'   => 'Lowongan Kerja',
    ],
    'generated_title' => 'Lowongan Kerja #:id',
    'form'            => [
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
            'description'           => 'JOB DESK',
            'requirements'          => 'KUALIFIKASI',
        ],
    ],
    'table' => [
        'columns' => [
            'title'          => 'Judul',
            'thumbnail_path' => 'Thumbnail',
            'location'       => 'Lokasi Penempatan',
            'is_published'   => 'Dipublikasikan',
            'closing_date'   => 'Tanggal Penutupan',
        ],
        'filters' => [
            'is_published' => 'Dipublikasikan',
        ],
    ],
];
