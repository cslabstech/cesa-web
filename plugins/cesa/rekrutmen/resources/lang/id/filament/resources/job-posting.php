<?php

return [
    'navigation' => [
        'label' => 'Lowongan',
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
            'request_man_power_id'               => 'MPP Sumber untuk Autofill',
            'linked_request_man_powers_overview' => 'MPP Terkait',
            'linked_request_man_power_ids'       => 'MPP Terkait',
            'rekrutmen_pipeline_id'              => 'Pipeline Rekrutmen',
            'title'                              => 'Judul',
            'slug'                               => 'Slug',
            'location'                           => 'Lokasi Penempatan',
            'thumbnail_path'                     => 'Thumbnail',
            'closing_date'                       => 'Tanggal Penutupan',
            'is_published'                       => 'Dipublikasikan untuk Rekrutmen',
            'description'                        => 'Job Desk',
            'requirements'                       => 'Kualifikasi',
        ],
        'helper_texts' => [
            'linked_request_man_power_ids' => 'Pilih MPP yang dihitung sebagai kebutuhan lowongan ini. MPP yang dilepas tidak lagi dihitung dalam progress pemenuhan lowongan.',
        ],
        'errors' => [
            'invalid_request_man_power_selection' => 'MPP yang dipilih tidak tersedia untuk lowongan ini.',
            'headcount_below_hired'               => 'Total kebutuhan MPP terkait (:needed) tidak boleh lebih kecil dari kandidat diterima (:hired).',
        ],
        'summaries' => [
            'total_needed' => 'Total kebutuhan :count orang',
        ],
    ],
    'table' => [
        'columns' => [
            'id'                         => 'Kode Lowongan',
            'title'                      => 'Judul',
            'rekrutmen_pipeline'         => 'Pipeline',
            'request_man_power'          => 'Permintaan Tenaga Kerja',
            'request_man_powers_summary' => 'MPP Terkait',
            'request_man_powers_count'   => 'Jumlah MPP',
            'requested_headcount_sum'    => 'Total Kebutuhan',
            'thumbnail_path'             => 'Thumbnail',
            'location'                   => 'Lokasi Penempatan',
            'applications_count'         => 'Pelamar',
            'is_published'               => 'Dipublikasikan',
            'closing_date'               => 'Tanggal Penutupan',
        ],
        'placeholders' => [
            'request_man_power'          => '-',
            'request_man_powers_summary' => '-',
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
    'relations' => [
        'request_man_powers' => [
            'title'   => 'MPP Terkait',
            'actions' => [
                'view' => 'Lihat MPP',
            ],
        ],
    ],
];
