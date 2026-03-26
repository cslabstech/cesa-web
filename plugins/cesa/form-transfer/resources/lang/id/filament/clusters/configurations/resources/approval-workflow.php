<?php

return [
    'navigation' => [
        'group' => 'Pengaturan Khusus Form',
        'label' => 'Alur Persetujuan',
    ],
    'fields' => [
        'form_transfer'      => 'Form Transfer',
        'division'           => 'Divisi',
        'division_hint'      => 'Hanya divisi yang terkait dengan form terpilih yang tersedia.',
        'description'        => 'Deskripsi',
        'is_active'          => 'Aktif',
        'steps'              => 'Tahapan Persetujuan',
        'step_label'         => 'Nama Tahap',
        'step_default_name'  => 'Nama Pengesah Bawaan',
        'step_default_email' => 'Email Pengesah Bawaan',
        'step_default_title' => 'Jabatan Pengesah Bawaan',
        'step_default_phone' => 'Telepon Pengesah Bawaan',
        'step_is_mandatory'  => 'Tahap Wajib',
    ],
    'columns' => [
        'form_transfer' => 'Form Transfer',
        'division'      => 'Divisi',
        'steps'         => 'Jumlah Tahap',
        'step_summary'  => 'Ringkasan Tahap',
        'is_active'     => 'Aktif',
    ],
    'filters' => [
        'form_transfer' => 'Form Transfer',
        'division'      => 'Divisi',
        'is_active'     => 'Status Keaktifan',
    ],
    'actions' => [
        'add_step' => 'Tambah Tahap Persetujuan',
    ],
];
