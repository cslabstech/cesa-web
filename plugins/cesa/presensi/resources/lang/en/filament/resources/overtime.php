<?php

return [
    'navigation' => [
        'label' => 'Overtimes',
    ],
    'model' => [
        'singular' => 'Overtime',
        'plural'   => 'Overtimes',
    ],
    'form' => [
        'fields' => [
            'user_id'    => 'Employee',
            'date'       => 'Date',
            'start_time' => 'Start Time',
            'end_time'   => 'End Time',
            'reason'     => 'Reason',
            'status'     => 'Status',
            'note'       => 'Note',
            'attachment' => 'Attachment',
        ],
        'options' => [
            'pending'  => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
        ],
    ],
    'table' => [
        'columns' => [
            'user'       => 'Employee',
            'date'       => 'Date',
            'start_time' => 'Start Time',
            'end_time'   => 'End Time',
            'reason'     => 'Reason',
            'status'     => 'Status',
        ],
    ],
];
