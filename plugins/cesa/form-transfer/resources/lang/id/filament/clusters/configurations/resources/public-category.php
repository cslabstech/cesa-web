<?php

return [
    'navigation' => [
        'group' => 'Pengaturan Global',
        'label' => 'Kategori Form',
    ],
    'actions' => [
        'open_form' => 'Buka Form',
    ],
    'fields' => [
        'name'        => 'Nama Kategori',
        'slug'        => 'Slug URL',
        'slug_helper' => 'Slug menjadi URL form publik. Contoh: retail menjadi /form/retail.',
        'description' => 'Deskripsi',
        'is_active'   => 'Aktif',
    ],
    'columns' => [
        'name'      => 'Nama',
        'slug'      => 'URL Publik',
        'is_active' => 'Aktif',
    ],
    'filters' => [
        'is_active' => 'Status Keaktifan',
    ],
    'validation' => [
        'slug'            => 'Slug URL tidak valid atau memakai path yang dicadangkan sistem.',
        'slug_unique'     => 'Slug URL sudah digunakan.',
        'built_in_slug'   => 'Slug kategori form bawaan tidak dapat diubah.',
        'built_in_active' => 'Kategori form bawaan harus tetap aktif.',
        'built_in_delete' => 'Kategori form bawaan tidak dapat dihapus.',
    ],
];
