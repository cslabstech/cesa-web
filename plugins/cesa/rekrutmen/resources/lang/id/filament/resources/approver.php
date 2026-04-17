<?php

return [
    'navigation' => [
        'label' => 'Approver',
    ],
    'model' => [
        'singular' => 'Approver',
        'plural'   => 'Approver',
    ],
    'form' => [
        'sections' => [
            'identity' => 'Identitas Approver',
            'scope'    => 'Cakupan Persetujuan',
        ],
        'fields' => [
            'name'           => 'Nama',
            'email'          => 'Email',
            'phone'          => 'Telepon',
            'title'          => 'Jabatan',
            'approval_order' => 'Urutan Persetujuan',
            'company_id'     => 'Perusahaan',
            'division_id'    => 'Divisi',
            'is_active'      => 'Aktif',
        ],
        'helpers' => [
            'company_id'     => 'Kosongkan jika approver berlaku untuk semua perusahaan.',
            'division_id'    => 'Kosongkan jika approver berlaku untuk semua divisi pada cakupan perusahaan yang dipilih.',
            'approval_order' => 'Gunakan angka kecil untuk approver yang harus memproses lebih dahulu.',
        ],
    ],
    'table' => [
        'columns' => [
            'approval_order' => 'Urutan',
            'name'           => 'Nama',
            'email'          => 'Email',
            'phone'          => 'Telepon',
            'title'          => 'Jabatan',
            'company_id'     => 'Perusahaan',
            'division_id'    => 'Divisi',
            'is_active'      => 'Aktif',
        ],
        'placeholders' => [
            'company_id'  => 'Semua Perusahaan',
            'division_id' => 'Semua Divisi',
        ],
        'filters' => [
            'company_id'  => 'Perusahaan',
            'division_id' => 'Divisi',
            'is_active'   => 'Status Aktif',
        ],
    ],
];
