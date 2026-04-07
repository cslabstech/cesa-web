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
            'job_posting_id'             => 'Lowongan Kerja',
            'full_name'                  => 'Nama Lengkap (Sesuai KTP)',
            'email'                      => 'Email',
            'gender'                     => 'Jenis Kelamin',
            'birth_date'                 => 'Tanggal Lahir',
            'marital_status'             => 'Status',
            'address_ktp'                => 'Alamat Lengkap Sesuai KTP',
            'address_domicile'           => 'Alamat Lengkap Sesuai Domisili',
            'whatsapp_number'            => 'No Whatsapp',
            'active_phone'               => 'No Telp Aktif',
            'emergency_contact_name'     => 'Nama Kontak Darurat',
            'emergency_contact_relation' => 'Hubungan Kontak Darurat',
            'emergency_contact_phone'    => 'No Kontak Darurat',
            'current_stage_id'           => 'Tahap Saat Ini',
            'status'                     => 'Status',
            'photo_path'                 => 'Photo Diri Terbaru',
            'resume_path'                => 'CV/Resume Terbaru',
        ],
    ],
    'table' => [
        'columns' => [
            'full_name'       => 'Nama Lengkap',
            'job_posting'     => 'Melamar Untuk',
            'email'           => 'Email',
            'whatsapp_number' => 'No Whatsapp',
            'active_phone'    => 'No Telp Aktif',
            'current_stage'   => 'Tahap',
            'status'          => 'Status',
        ],
        'filters' => [
            'job_posting_id' => 'Lowongan Kerja',
            'status'         => 'Status',
        ],
        'actions' => [
            'change_stage'    => 'Pindah Tahap',
            'to_stage_id'     => 'Pindah ke Tahap',
            'mark_hired'      => 'Terima Kandidat',
            'mark_rejected'   => 'Tolak Kandidat',
            'notes'           => 'Catatan',
            'download_resume' => 'Unduh CV',
            'download_photo'  => 'Unduh Foto',
        ],
    ],
    'notifications' => [
        'stage_changed'   => 'Tahap kandidat berhasil diperbarui.',
        'marked_hired'    => 'Kandidat berhasil ditandai diterima.',
        'marked_rejected' => 'Kandidat berhasil ditandai ditolak.',
    ],
    'board' => [
        'heading'             => 'Pipeline Lamaran Kandidat',
        'heading_with_job'    => 'Pipeline Lamaran Kandidat - :job',
        'subheading'          => 'Pantau progres kandidat berdasarkan lowongan yang dipilih.',
        'subheading_with_job' => 'Pantau progres kandidat untuk lowongan :job.',
    ],
    'workflow_notes' => [
        'stage_changed' => 'Tahap kandidat dipindahkan.',
        'stage_synced'  => 'Tahap kandidat disinkronkan dengan pipeline lowongan.',
        'submitted'     => 'Kandidat masuk ke tahap awal.',
        'hired'         => 'Kandidat ditandai diterima.',
        'rejected'      => 'Kandidat ditandai ditolak.',
    ],
    'workflow_errors' => [
        'invalid_stage'            => 'Tahap tujuan tidak sesuai dengan pipeline lowongan.',
        'terminal_stage_locked'    => 'Kandidat yang sudah diputuskan tidak bisa dipindah tahap.',
        'decision_note_required'   => 'Catatan keputusan kandidat wajib diisi.',
        'job_posting_has_no_stage' => 'Lowongan yang dipilih belum memiliki tahap awal.',
    ],
];
