<?php

return [
    'navigation' => [
        'group' => 'Pengaturan Global',
        'label' => 'Bank',
    ],
    'fields' => [
        'code'            => 'Kode Bank',
        'code_hint'       => 'Gunakan kode bank resmi (mis. BCA).',
        'name'            => 'Nama Bank',
        'short_name'      => 'Nama Singkat',
        'short_name_hint' => 'Singkatan opsional yang ditampilkan kepada pengguna.',
        'sort_order'      => 'Urutan',
        'is_active'       => 'Aktif',
    ],
    'columns' => [
        'code'       => 'Kode',
        'name'       => 'Nama',
        'short_name' => 'Nama Singkat',
        'sort_order' => 'Urutan',
        'is_active'  => 'Aktif',
    ],
    'filters' => [
        'is_active' => 'Status Keaktifan',
    ],
];
