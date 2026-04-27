<?php

return [
    'title'   => 'Riwayat',
    'form'    => [
        'fields' => [
            'activity_date' => 'Tanggal Aktivitas',
            'notes'         => 'Catatan',
        ],
    ],
    'columns' => [
        'from_stage'    => 'Dari Tahap',
        'to_stage'      => 'Ke Tahap',
        'status'        => 'Status',
        'notes'         => 'Catatan',
        'performed_by'  => 'Dilakukan Oleh',
        'activity_date' => 'Tanggal Aktivitas',
        'recorded_at'   => 'Dicatat Pada',
    ],
    'actions' => [
        'edit' => [
            'label'   => 'Edit Tanggal',
            'heading' => 'Edit Tanggal Riwayat',
        ],
    ],
    'notifications' => [
        'updated' => 'Tanggal riwayat aktivitas berhasil diperbarui.',
    ],
    'placeholders' => [
        'from_stage' => 'Awal',
        'to_stage'   => 'N/A',
    ],
];
