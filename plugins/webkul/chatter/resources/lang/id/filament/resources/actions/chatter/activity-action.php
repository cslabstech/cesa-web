<?php

return [
    'setup' => [
        'title'               => 'Jadwalkan Aktivitas',
        'submit-action-title' => 'Jadwalkan',
        'form'                => [
            'fields' => [
                'activity-plan' => 'Rencana Aktivitas',
                'plan-date'     => 'Tanggal Rencana',
                'plan-summary'  => 'Ringkasan Rencana',
                'activity-type' => 'Jenis Aktivitas',
                'due-date'      => 'Batas Waktu',
                'summary'       => 'Ringkasan',
                'assigned-to'   => 'Ditugaskan Kepada',
                'log-note'      => 'Catatan Log',
            ],
        ],
        'actions' => [
            'notification' => [
                'success' => [
                    'title' => 'Aktivitas Dibuat',
                    'body'  => 'Aktivitas telah dibuat.',
                ],
                'warning' => [
                    'title' => 'Tidak ada file baru',
                    'body'  => 'Semua file telah diunggah.',
                ],
                'error' => [
                    'title' => 'Gagal membuat aktivitas',
                    'body'  => 'Gagal membuat aktivitas',
                ],
            ],
        ],
    ],
];
