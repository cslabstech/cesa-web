<?php

return [
    'title' => 'Schedules',
    'form'  => [
        'fields' => [
            'office_id' => 'Office',
            'shift_id'  => 'Shift',
            'is_wfa'    => 'Is WFA',
            'is_banned' => 'Is Banned',
        ],
    ],
    'table' => [
        'columns' => [
            'office_name' => 'Office Name',
            'shift_name'  => 'Shift Name',
            'is_wfa'      => 'WFA Status',
            'is_banned'   => 'Banned Status',
        ],
    ],
];
