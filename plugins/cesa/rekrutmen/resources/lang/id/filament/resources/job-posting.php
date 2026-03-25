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
        ],
        'fields' => [
            'request_man_power_id'  => 'Permintaan Tenaga Kerja Terkait (Opsional)',
            'rekrutmen_pipeline_id' => 'Pipeline Rekrutmen',
            'title'                 => 'Judul',
            'slug'                  => 'Slug',
            'location'              => 'Lokasi',
            'closing_date'          => 'Tanggal Penutupan',
            'is_published'          => 'Dipublikasikan untuk Rekrutmen',
            'description'           => 'Deskripsi',
            'requirements'          => 'Persyaratan',
        ],
    ],
    'table' => [
        'columns' => [
            'title'        => 'Judul',
            'location'     => 'Lokasi',
            'is_published' => 'Dipublikasikan',
            'closing_date' => 'Tanggal Penutupan',
        ],
        'filters' => [
            'is_published' => 'Dipublikasikan',
        ],
    ],
];
