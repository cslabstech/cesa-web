<?php

return [
    'navigation' => [
        'label' => 'Lembur',
    ],
    'model' => [
        'singular' => 'Lembur',
        'plural'   => 'Lembur',
    ],
    'form' => [
        'fields' => [
            'user_id'    => 'Pegawai',
            'date'       => 'Tanggal',
            'start_time' => 'Waktu Mulai',
            'end_time'   => 'Waktu Selesai',
            'reason'     => 'Alasan',
            'status'     => 'Status',
            'note'       => 'Catatan',
            'attachment' => 'Lampiran',
        ],
        'options' => [
            'pending'  => 'Menunggu',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
        ],
    ],
    'table' => [
        'columns' => [
            'user'       => 'Pegawai',
            'date'       => 'Tanggal',
            'start_time' => 'Waktu Mulai',
            'end_time'   => 'Waktu Selesai',
            'reason'     => 'Alasan',
            'status'     => 'Status',
        ],
    ],
];
