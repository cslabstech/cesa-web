<?php

return [
    'navigation' => [
        'label' => 'Lamaran Kerja',
    ],
    'model' => [
        'singular' => 'Lamaran Kerja',
        'plural'   => 'Lamaran Kerja',
    ],
    'generated' => [
        'unknown_position' => 'posisi-tidak-diketahui',
    ],
    'form' => [
        'sections' => [
            'candidate_information' => 'Informasi Kandidat',
            'application_details'   => 'Detail Lamaran',
        ],
        'fields' => [
            'job_posting_id'   => 'Lowongan Kerja',
            'full_name'        => 'Nama Lengkap',
            'email'            => 'Email',
            'phone'            => 'Nomor Telepon',
            'portfolio_url'    => 'URL Portofolio',
            'current_stage_id' => 'Tahap Saat Ini',
            'status'           => 'Status',
            'resume_path'      => 'CV',
            'cover_letter'     => 'Surat Lamaran',
        ],
    ],
    'table' => [
        'columns' => [
            'full_name'     => 'Nama Lengkap',
            'job_posting'   => 'Melamar Untuk',
            'email'         => 'Email',
            'phone'         => 'Nomor Telepon',
            'current_stage' => 'Tahap',
            'status'        => 'Status',
        ],
        'filters' => [
            'job_posting_id' => 'Lowongan Kerja',
            'status'         => 'Status',
        ],
        'actions' => [
            'change_stage'    => 'Pindah Tahap',
            'to_stage_id'     => 'Pindah ke Tahap',
            'notes'           => 'Catatan',
            'download_resume' => 'CV',
        ],
    ],
];
