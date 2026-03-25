<?php

return [
    'label' => [
        'single' => 'Ticket',
        'plural' => 'Tickets',
    ],
    'form' => [
        'sections' => [
            'ticket_detail' => 'Ticket Detail',
            'assignment'    => 'Assignment',
        ],
        'fields' => [
            'unit_id'                 => 'Unit',
            'problem_category_id'     => 'Problem Category',
            'title'                   => 'Title',
            'description'             => 'Description',
            'supporting_attachments'  => 'Supporting Attachments',
            'priority_id'             => 'Priority',
            'company_id'              => 'Company',
            'ticket_status_name'      => 'Status',
            'responsible_id'          => 'Responsible',
            'owner_name'              => 'Owner',
            'approved_at'             => 'Approved At',
            'solved_at'               => 'Solved At',
            'close_reason'            => 'Close Reason',
            'cancel_reason'           => 'Cancel Reason',
            'reopen_reason'           => 'Reopen Reason',
        ],
        'placeholders' => [
            'open' => 'Open',
            'dash' => '-',
        ],
    ],
    'table' => [
        'columns' => [
            'unit'         => 'Unit',
            'category'     => 'Category',
            'priority'     => 'Priority',
            'status'       => 'Status',
            'responsible'  => 'Responsible',
            'created_at'   => 'Created At',
        ],
        'filters' => [
            'unit_id'         => 'Unit',
            'ticket_status_id'=> 'Status',
            'priority_id'     => 'Priority',
            'responsible_id'  => 'Responsible',
        ],
        'placeholders' => [
            'dash' => '-',
        ],
    ],
    'infolist' => [
        'sections' => [
            'ticket_detail' => 'Ticket Detail',
            'attachments'   => 'Attachments',
        ],
        'entries' => [
            'unit'                  => 'Unit',
            'category'              => 'Category',
            'priority'              => 'Priority',
            'status'                => 'Status',
            'company'               => 'Company',
            'owner'                 => 'Owner',
            'responsible'           => 'Responsible',
            'approved_at'           => 'Approved At',
            'solved_at'             => 'Solved At',
            'close_reason'          => 'Close Reason',
            'cancel_reason'         => 'Cancel Reason',
            'reopen_reason'         => 'Reopen Reason',
            'supporting_attachments'=> 'Supporting Attachments',
        ],
        'placeholders' => [
            'dash' => '-',
        ],
    ],
];
