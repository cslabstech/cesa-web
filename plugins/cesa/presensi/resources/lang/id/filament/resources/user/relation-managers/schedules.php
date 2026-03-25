<?php

return [
    'title' => 'Jadwal',
    'form'  => [
        'fields' => [
            'office_id' => 'Kantor',
            'shift_id'  => 'Shift',
            'is_wfa'    => 'WFA',
            'is_banned' => 'Diblokir',
        ],
    ],
    'table' => [
        'columns' => [
            'office_name' => 'Nama Kantor',
            'shift_name'  => 'Nama Shift',
            'is_wfa'      => 'Status WFA',
            'is_banned'   => 'Status Blokir',
        ],
    ],
];
