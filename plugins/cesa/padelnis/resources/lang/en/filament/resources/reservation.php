<?php

return [
    'navigation' => [
        'title' => 'Reservations',
        'group' => 'Padelnis',
    ],

    'singular' => 'Reservation',
    'plural'   => 'Reservations',

    'fields' => [
        'id_reff'          => 'Reference ID',
        'customer_name'    => 'Customer Name',
        'reservation_date' => 'Reservation Date',
        'court'            => 'Court',
        'reservation_time' => 'Time',
        'blocked_slots'    => 'Blocked Slot Details',
        'transfer_amount'  => 'Transfer Amount',
        'transfer_date'    => 'Transfer Date',
        'notes'            => 'Notes',
        'created_at'       => 'Created At',
    ],

    'form' => [
        'sections' => [
            'reservation' => [
                'title' => 'Reservation Details',
            ],
        ],

        'placeholders' => [
            'customer_name'    => 'Enter customer name',
            'reservation_date' => 'Select reservation date',
            'court'            => 'Select court',
            'reservation_time' => 'Select start time - end time',
            'transfer_amount'  => 'Enter transfer amount',
            'transfer_date'    => 'Select transfer date',
            'notes'            => 'Add notes if needed',
        ],
    ],

    'table' => [
        'columns' => [
            'id_reff'          => 'Reference ID',
            'customer_name'    => 'Customer Name',
            'reservation_date' => 'Date',
            'reservation_time' => 'Time',
            'blocked_slots'    => 'Blocked Slot Details',
            'court'            => 'Court',
            'transfer_amount'  => 'Transfer Amount',
            'transfer_date'    => 'Transfer Date',
            'notes'            => 'Notes',
            'created_at'       => 'Created At',
        ],
    ],

    'filters' => [
        'reservation_from'        => 'Date From',
        'reservation_until'       => 'Date Until',
        'reservation_time'        => 'Time',
        'reservation_range'       => 'Reservation: :from - :until',
        'reservation_from_value'  => 'Reservation from: :date',
        'reservation_until_value' => 'Reservation until: :date',
        'court'                   => 'Court',
    ],

    'actions' => [
        'copy_id_reff' => 'Reference ID copied.',
    ],

    'validation' => [
        'active_slot_unique' => 'This slot is already reserved for the selected court and date.',
    ],

    'exports' => [
        'notifications' => [
            'completed_body' => 'The reservation export finished with :success exported row(s) and :failed failed row(s).',
        ],
    ],

    'pages' => [
        'list' => [
            'header_actions' => [
                'create' => [
                    'label' => 'Create Reservation',
                ],
                'export' => [
                    'label' => 'Export Reservations',
                ],
            ],
        ],
    ],
];
