<?php

return [
    'navigation' => [
        'label' => 'Leaves',
    ],
    'model' => [
        'singular' => 'Leave',
        'plural'   => 'Leaves',
    ],
    'form' => [
        'fields' => [
            'user_id'    => 'Employee',
            'type'       => 'Type',
            'start_date' => 'Start Date',
            'end_date'   => 'End Date',
            'reason'     => 'Reason',
            'status'     => 'Status',
            'note'       => 'Note',
            'attachment' => 'Attachment',
        ],
        'options' => [
            'pending'  => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'izin'     => 'Permit',
            'sakit'    => 'Sick',
            'cuti'     => 'Annual Leave',
        ],
    ],
    'table' => [
        'columns' => [
            'user'       => 'Employee',
            'type'       => 'Type',
            'start_date' => 'Start Date',
            'end_date'   => 'End Date',
            'reason'     => 'Reason',
            'status'     => 'Status',
        ],
    ],
];
