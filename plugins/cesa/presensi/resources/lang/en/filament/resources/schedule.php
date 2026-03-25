<?php

return [
    'navigation' => [
        'label' => 'Schedules',
    ],
    'model' => [
        'singular' => 'Schedule',
        'plural'   => 'Schedules',
    ],
    'form' => [
        'fields' => [
            'user_id'   => 'Employee',
            'shift_id'  => 'Shift',
            'office_id' => 'Office',
            'is_wfa'    => 'Allow WFA',
            'is_banned' => 'Disable Attendance',
        ],
    ],
    'table' => [
        'columns' => [
            'user_name'  => 'Name',
            'user_email' => 'Email',
            'is_wfa'     => 'WFA',
            'shift'      => 'Shift',
            'office'     => 'Office',
        ],
    ],
];
