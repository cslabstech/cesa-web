<?php

return [
    'heading'               => 'Persetujuan Permintaan Tenaga Kerja',
    'subheading'            => 'Tinjau permintaan sebagai approver: :name.',
    'page_title'            => 'PERSETUJUAN REQUEST MAN POWER',
    'requester_label'       => 'Diajukan oleh',
    'current_status'        => 'Status saat ini',
    'summary_heading'       => 'Ringkasan Permintaan',
    'approval_flow_heading' => 'Tahapan Persetujuan',
    'action_heading'        => 'Tindak Lanjut',
    'action_subheading'     => 'Tambahkan catatan bila diperlukan, lalu pilih keputusan Anda.',
    'step_label'            => 'Tahap :step',
    'fields'                => [
        'position'         => 'Posisi yang Dibutuhkan',
        'division'         => 'Divisi',
        'business_entity'  => 'Perusahaan',
        'estimated_join'   => 'Estimasi Join',
        'requirements'     => 'Kualifikasi yang Dibutuhkan',
        'job_description'  => 'Deskripsi Pekerjaan',
        'notes'            => 'Catatan Approver',
    ],
    'placeholders' => [
        'notes' => 'Tambahkan catatan persetujuan atau penolakan bila diperlukan.',
    ],
    'actions' => [
        'approve'       => 'Setujui',
        'reject'        => 'Tolak',
        'view_progress' => 'Lihat Progres',
    ],
    'notifications' => [
        'approved'          => 'Persetujuan berhasil direkam.',
        'rejected'          => 'Penolakan berhasil direkam.',
        'already_processed' => 'Tautan ini sudah tidak dapat diproses.',
        'link_expired'      => 'Tautan persetujuan sudah kedaluwarsa.',
        'rate_limited'      => 'Terlalu banyak percobaan. Coba lagi dalam :seconds detik.',
    ],
];
