<?php

return [
    'navigation' => [
        'label' => 'Catat Aktivitas Rekrutmen',
    ],
    'model' => [
        'singular' => 'Aktivitas Rekrutmen',
        'plural'   => 'Aktivitas Rekrutmen',
    ],
    'form' => [
        'sections' => [
            'activity_details' => 'Detail Aktivitas',
            'candidates'       => 'Kandidat Peserta',
            'info'             => 'Informasi',
        ],
        'fields' => [
            'job_posting_id'  => 'Lowongan Kerja',
            'stage_id'        => 'Tahapan',
            'activity_date'   => 'Tanggal Aktivitas',
            'generated_title' => 'Judul Aktivitas',
            'candidate'       => 'Kandidat',
            'result'          => 'Hasil',
            'notes'           => 'Catatan',
        ],
        'actions' => [
            'add_candidate' => 'Tambah Kandidat',
            'create'        => 'Catat Aktivitas',
        ],
        'helpers' => [
            'info_note'                   => 'Kandidat yang "Lolos" akan otomatis dipindah ke tahap selanjutnya. Kandidat yang "Tidak Lolos" akan otomatis ditolak.',
            'failed_requires_notes'       => 'Catatan wajib diisi jika kandidat tidak lolos.',
            'generated_title_placeholder' => 'Judul akan dibuat otomatis dari tahap pipeline dan tanggal aktivitas.',
            'create_subheading'           => 'Catat aktivitas per tahapan tanpa keluar dari proses pipeline dan lamaran kandidat.',
        ],
    ],
    'table' => [
        'columns' => [
            'activity_date' => 'Tanggal',
            'job_posting'   => 'Lowongan',
            'title'         => 'Judul',
            'stage'         => 'Tahapan',
            'performed_by'  => 'Pelaksana',
            'summary'       => 'Ringkasan',
        ],
        'filters' => [
            'job_posting_id'     => 'Lowongan Kerja',
            'stage_id'           => 'Tahapan Aktivitas',
            'activity_date'      => 'Tanggal Aktivitas',
            'date_from'          => 'Dari',
            'date_until'         => 'Sampai',
            'all_job_postings'   => 'Semua Lowongan',
            'all_stages'         => 'Semua Tahapan',
        ],
        'actions' => [
            'reset_filters'       => 'Reset Filter',
            'delete'              => 'Hapus',
            'delete_confirmation' => 'Hapus grup aktivitas rekrutmen ini?',
        ],
    ],
    'notifications' => [
        'activity_recorded' => 'Aktivitas rekrutmen berhasil dicatat. Kandidat yang lolos telah dipindah ke tahap selanjutnya.',
        'no_candidates'     => 'Pilih minimal satu kandidat untuk mencatat aktivitas.',
        'deleted'           => 'Aktivitas rekrutmen berhasil dihapus.',
    ],
    'errors' => [
        'invalid_stage'      => 'Tahapan yang dipilih tidak sesuai dengan pipeline lowongan kerja.',
        'invalid_candidates' => 'Kandidat yang dipilih harus berasal dari lowongan dan tahapan yang sedang aktif.',
    ],
    'relation-managers' => [
        'entries' => [
            'title'   => 'Detail Kandidat',
            'columns' => [
                'candidate'  => 'Kandidat',
                'result'     => 'Hasil',
                'notes'      => 'Catatan',
            ],
        ],
    ],
];
