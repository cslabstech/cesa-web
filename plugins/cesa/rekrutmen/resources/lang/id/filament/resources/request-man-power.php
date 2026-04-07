<?php

return [
    'navigation' => [
        'label' => 'Permintaan Tenaga Kerja',
    ],
    'model' => [
        'singular' => 'Permintaan Tenaga Kerja',
        'plural'   => 'Permintaan Tenaga Kerja',
    ],
    'form' => [
        'sections' => [
            'applicant_information' => 'Informasi Pengaju',
            'requirement_details'   => 'Detail Kebutuhan',
            'qualifications'        => 'Kualifikasi & Deskripsi Pekerjaan',
            'approval_status'       => 'Status Persetujuan',
        ],
        'fields' => [
            'nama_pengaju'               => 'Nama Pengaju',
            'posisi_pengaju'             => 'Posisi Pengaju',
            'email_address'              => 'Email Pengaju',
            'tanggal_pengajuan'          => 'Tanggal Pengajuan',
            'divisi'                     => 'Divisi',
            'badan_usaha'                => 'Badan Usaha',
            'posisi_dibutuhkan'          => 'Posisi yang Dibutuhkan',
            'lokasi_penempatan'          => 'Lokasi Penempatan',
            'status_kebutuhan'           => 'Status Kebutuhan',
            'level_pekerjaan'            => 'Level Pekerjaan',
            'jumlah_karyawan_dibutuhkan' => 'Jumlah Karyawan Dibutuhkan',
            'estimasi_tanggal_join'      => 'Estimasi Tanggal Join',
            'nama_karyawan_replacement'  => 'Nama Karyawan yang Akan Digantikan',
            'requirements_kualifikasi'   => 'Kualifikasi yang Dibutuhkan',
            'job_description'            => 'Deskripsi Pekerjaan',
            'keterangan'                 => 'Keterangan Tambahan',
            'status'                     => 'Status Persetujuan',
            'approved_by'                => 'Disetujui Oleh',
        ],
        'helper_texts' => [
            'nama_karyawan_replacement' => 'Untuk kebutuhan replacement, isi nama karyawan yang akan digantikan.',
        ],
    ],
    'table' => [
        'columns' => [
            'nama_pengaju'               => 'Nama Pengaju',
            'posisi_dibutuhkan'          => 'Posisi Dibutuhkan',
            'divisi'                     => 'Divisi',
            'status_kebutuhan'           => 'Status Kebutuhan',
            'nama_karyawan_replacement'  => 'Karyawan Pengganti',
            'jumlah_karyawan_dibutuhkan' => 'Jumlah',
            'tanggal_pengajuan'          => 'Tanggal Pengajuan',
            'status'                     => 'Status Persetujuan',
        ],
        'placeholders' => [
            'nama_karyawan_replacement' => '-',
        ],
        'filters' => [
            'status'           => 'Status Persetujuan',
            'status_kebutuhan' => 'Status Kebutuhan',
            'divisi'           => 'Divisi',
        ],
        'actions' => [
            'approve'     => 'Setujui',
            'reject'      => 'Tolak',
            'set_pending' => 'Set Pending',
        ],
    ],
    'errors' => [
        'default_pipeline_not_configured' => 'Pipeline rekrutmen default belum dikonfigurasi.',
        'approval_failed'                 => 'Aksi persetujuan tidak dapat diselesaikan.',
    ],
];
