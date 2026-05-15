<?php

return [
    'title' => 'Leads',

    'navigation' => [
        'title' => 'Leads',
        'group' => null,
    ],

    'singular' => 'Lead',
    'plural'   => 'Leads',

    'form' => [
        'sections' => [
            'basic_information' => [
                'title' => 'Basic Information',

                'fields' => [
                    'name'    => 'Full Name',
                    'phone'   => 'Phone Number',
                    'address' => 'Address',
                ],
            ],
            'store_information' => [
                'title' => 'Store Information',

                'fields' => [
                    'sales_person'            => 'Sales Person',
                    'store_team_position'     => 'Store Team Position',
                    'store_branch'            => 'Store Branch',
                    'phone_transaction_range' => 'Phone Transaction Range',
                ],
            ],
        ],

        'placeholders' => [
            'name'                    => 'Enter the lead full name',
            'phone'                   => 'Example: 08123456789 or 628123456789',
            'address'                 => 'Enter the complete address',
            'sales_person'            => 'Enter the sales person name',
            'choose'                  => 'Select an option',
            'store_branch'            => 'Select a store branch',
            'phone_transaction_range' => 'Select a price range',
        ],
    ],

    'fields' => [
        'name'                    => 'Full Name',
        'phone'                   => 'Phone Number',
        'address'                 => 'Address',
        'sales_person'            => 'Sales Person',
        'store_team_position'     => 'Store Team Position',
        'store_branch'            => 'Store Branch',
        'phone_transaction_range' => 'Phone Transaction Range',
        'creator_id'              => 'Created By',
        'created_at'              => 'Created At',
    ],

    'options' => [
        'store_team_position' => [
            'kepala_toko' => 'Store Manager',
            'promotor'    => 'Promoter',
            'kasir'       => 'Cashier',
            'frontliner'  => 'Frontliner',
        ],
        'phone_transaction_range' => [
            'below_2m' => 'Below IDR 2 million',
            '2m_3m'    => 'IDR 2 to 3 million',
            '3m_4m'    => 'IDR 3 to 4 million',
            '4m_7m'    => 'IDR 4 to 7 million',
            'above_7m' => 'Above IDR 7 million',
        ],
    ],

    'table' => [
        'columns' => [
            'name'                    => 'Name',
            'phone'                   => 'Phone Number',
            'sales_person'            => 'Sales Person',
            'store_team_position'     => 'Store Team Position',
            'store_branch'            => 'Store Branch',
            'phone_transaction_range' => 'Price Range',
            'created_at'              => 'Created At',
        ],
    ],

    'filters' => [
        'created_from'            => 'From',
        'created_until'           => 'Until',
        'date_range'              => 'Date Range',
        'store_team_position'     => 'Store Team Position',
        'store_branch'            => 'Store Branch',
        'phone_transaction_range' => 'Phone Transaction Range',
    ],

    'actions' => [
        'copy_phone' => 'Phone number copied.',
    ],

    'imports' => [
        'columns' => [
            'name'                    => 'Name',
            'phone'                   => 'Phone Number',
            'address'                 => 'Address',
            'sales_person'            => 'Sales Person',
            'store_team_position'     => 'Store Team Position',
            'store_branch'            => 'Store Branch',
            'phone_transaction_range' => 'Phone Transaction Range',
        ],
        'notifications' => [
            'completed_title' => 'Lead import completed',
            'completed_body'  => 'The lead import finished with :success successful row(s) and :failed failed row(s).',
        ],
    ],

    'exports' => [
        'notifications' => [
            'completed_body' => 'The lead export finished with :success exported row(s) and :failed failed row(s).',
        ],
    ],

    'notifications' => [
        'created' => [
            'title' => 'Lead created',
            'body'  => 'The lead has been created successfully.',
        ],
        'updated' => [
            'title' => 'Lead updated',
            'body'  => 'The lead has been updated successfully.',
        ],
        'deleted' => [
            'title' => 'Lead deleted',
            'body'  => 'The lead has been deleted successfully.',
        ],
    ],

    'validation' => [
        'phone_required' => 'Phone number is required.',
        'phone_format'   => 'The phone number must use the 62xxxxxxxxxx format and contain at least 10 digits.',
        'phone_unique'   => 'The phone number has already been registered.',
    ],
];
