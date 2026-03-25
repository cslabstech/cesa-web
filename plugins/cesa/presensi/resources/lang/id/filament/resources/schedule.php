<?php

return [
    'navigation' => [
        'label' => 'Jadwal',
    ],
    'model' => [
        'singular' => 'Jadwal',
        'plural'   => 'Jadwal',
    ],
    'form' => [
        'fields' => [
            'user_id'   => 'Pegawai',
            'shift_id'  => 'Shift',
            'office_id' => 'Kantor',
            'is_wfa'    => 'Izinkan WFA',
            'is_banned' => 'Nonaktifkan Presensi',
        ],
    ],
    'table' => [
        'columns' => [
            'user_name'  => 'Nama',
            'user_email' => 'Email',
            'is_wfa'     => 'WFA',
            'shift'      => 'Shift',
            'office'     => 'Kantor',
        ],
    ],
];
