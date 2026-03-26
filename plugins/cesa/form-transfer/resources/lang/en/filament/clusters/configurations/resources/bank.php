<?php

return [
    'navigation' => [
        'group' => 'Global Settings',
        'label' => 'Banks',
    ],
    'fields' => [
        'code'            => 'Bank Code',
        'code_hint'       => 'Use the official bank code (e.g., BCA).',
        'name'            => 'Bank Name',
        'short_name'      => 'Short Name',
        'short_name_hint' => 'Optional abbreviation displayed to users.',
        'sort_order'      => 'Sort Order',
        'is_active'       => 'Active',
    ],
    'columns' => [
        'code'       => 'Code',
        'name'       => 'Name',
        'short_name' => 'Short Name',
        'sort_order' => 'Order',
        'is_active'  => 'Active',
    ],
    'filters' => [
        'is_active' => 'Active Status',
    ],
];
