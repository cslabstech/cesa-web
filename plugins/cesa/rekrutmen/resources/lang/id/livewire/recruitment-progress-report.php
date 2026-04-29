<?php

return [
    'title'    => 'Progres Rekrutmen',
    'subtitle' => 'Pelacakan aktivitas dan progress rekrutmen',

    'filters' => [
        'period'         => 'Periode',
        'period_from'    => 'Periode Mulai',
        'period_to'      => 'Periode Selesai',
        'position'       => 'Posisi / Lowongan',
        'stage'          => 'Tahap Aktivitas',
        'company'        => 'Perusahaan',
        'all_positions'  => 'Semua Posisi',
        'all_stages'     => 'Semua Tahap',
        'all_companies'  => 'Semua Perusahaan',
    ],

    'summary' => [
        'active_positions'  => 'Posisi Aktif',
        'total_applicants'  => 'Total Pelamar',
        'activities'        => 'Aktivitas Periode Ini',
        'hired'             => 'Diterima',
        'rejected'          => 'Ditolak',
        'openings_label'    => 'lowongan dibuka',
        'applicants_label'  => 'dalam proses',
        'activities_label'  => 'aktivitas dilakukan',
        'hired_label'       => 'kandidat hired',
        'rejected_label'    => 'kandidat rejected',
    ],

    'tabs' => [
        'timeline'     => 'Timeline',
        'per_position' => 'Per Posisi',
        'overview'     => 'Ringkasan',
    ],

    'labels' => [
        'stage'            => 'Tahap',
        'by'               => 'oleh',
        'passed'           => 'Lolos',
        'failed'           => 'Tidak Lolos',
        'pending'          => 'Menunggu',
        'activities_count' => ':count aktivitas',
        'view_candidates'  => 'Lihat detail :count kandidat',
        'company'          => 'Perusahaan',
        'location'         => 'Lokasi',
        'needed'           => 'Butuh',
        'est_join'         => 'Est. Join',
        'open'             => 'Dibuka',
        'closed'           => 'Ditutup',
        'hired_candidates' => 'Karyawan Hired',
        'pipeline_funnel'  => 'Funnel Pipeline',
        'activity_history' => 'Riwayat Aktivitas',
        'total'            => 'Total',
    ],

    'table' => [
        'position'      => 'Posisi',
        'company'       => 'Perusahaan',
        'needed'        => 'Butuh',
        'applicants'    => 'Pelamar',
        'process'       => 'Proses',
        'accepted'      => 'Diterima',
        'rejected'      => 'Ditolak',
        'last_activity' => 'Aktivitas Terakhir',
        'fulfillment'   => 'Pemenuhan',
        'total'         => 'TOTAL',
        'candidate'     => 'Nama Kandidat',
        'result'        => 'Hasil',
        'notes'         => 'Catatan',
    ],

    'empty' => [
        'no_activities' => 'Tidak ada aktivitas pada periode ini.',
        'no_positions'  => 'Tidak ada posisi yang ditemukan.',
    ],

    'summary_text' => [
        'total_candidates' => ':count Orang',
        'passed'           => ':count Lolos',
        'failed'           => ':count Tidak Lolos',
        'pending'          => ':count Menunggu',
    ],
];
