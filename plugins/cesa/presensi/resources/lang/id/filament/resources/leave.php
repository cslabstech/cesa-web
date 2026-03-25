<?php

return [
    'navigation' => [
        'label' => 'Cuti dan Izin',
    ],
    'model' => [
        'singular' => 'Cuti dan Izin',
        'plural'   => 'Cuti dan Izin',
    ],
    'form' => [
        'fields' => [
            'user_id'    => 'Pegawai',
            'type'       => 'Jenis',
            'start_date' => 'Tanggal Mulai',
            'end_date'   => 'Tanggal Selesai',
            'reason'     => 'Alasan',
            'status'     => 'Status',
            'note'       => 'Catatan',
            'attachment' => 'Lampiran',
        ],
        'options' => [
            'pending'  => 'Menunggu',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'izin'     => 'Izin',
            'sakit'    => 'Sakit',
            'cuti'     => 'Cuti',
        ],
    ],
    'table' => [
        'columns' => [
            'user'       => 'Pegawai',
            'type'       => 'Jenis',
            'start_date' => 'Tanggal Mulai',
            'end_date'   => 'Tanggal Selesai',
            'reason'     => 'Alasan',
            'status'     => 'Status',
        ],
    ],
];
