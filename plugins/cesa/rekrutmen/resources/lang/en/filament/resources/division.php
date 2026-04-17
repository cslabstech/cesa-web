<?php

return [
    'navigation' => [
        'label' => 'Recruitment Divisions',
    ],
    'model' => [
        'singular' => 'Recruitment Division',
        'plural'   => 'Recruitment Divisions',
    ],
    'form' => [
        'sections' => [
            'identity' => 'Division Identity',
        ],
        'fields' => [
            'company_id' => 'Company',
            'name'       => 'Division Name',
            'is_active'  => 'Active',
        ],
    ],
    'table' => [
        'columns' => [
            'company_id' => 'Company',
            'name'       => 'Division Name',
            'is_active'  => 'Active',
        ],
        'filters' => [
            'company_id' => 'Company',
            'is_active'  => 'Active Status',
        ],
    ],
];
