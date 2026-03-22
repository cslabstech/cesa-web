<?php

return [
    'tooltip' => 'Filter',
    'fields'  => [
        'search'             => 'Pencarian',
        'search-placeholder' => 'Telusuri pesan...',
        'type'               => 'Jenis',
        'date'               => 'Tanggal',
        'sort-by'            => 'Urutkan berdasarkan',
        'pinned-only'        => 'Hanya disematkan',
    ],
    'type-options' => [
        'all'          => 'Semua jenis',
        'note'         => 'Catatan',
        'comment'      => 'Komentar',
        'notification' => 'Pemberitahuan',
        'activity'     => 'Kegiatan',
    ],
    'date-options' => [
        ''          => 'Kapan pun',
        'today'     => 'Hari ini',
        'yesterday' => 'Kemarin',
        'week'      => '7 hari terakhir',
        'month'     => '30 hari terakhir',
        'quarter'   => '3 bulan terakhir',
        'year'      => 'Tahun lalu',
    ],
    'sort-options' => [
        'created_at_desc' => 'Yang terbaru dulu',
        'created_at_asc'  => 'Terlama lebih dulu',
        'updated_at_desc' => 'Baru-baru ini diperbarui',
        'priority'        => 'Prioritas',
    ],
    'actions' => [
        'apply' => 'Terapkan filter',
    ],
];
