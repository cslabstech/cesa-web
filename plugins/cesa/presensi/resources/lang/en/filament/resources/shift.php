<?php

return [
    'navigation' => [
        'label' => 'Shifts',
    ],
    'model' => [
        'singular' => 'Shift',
        'plural'   => 'Shifts',
    ],
    'form' => [
        'fields' => [
            'name'       => 'Name',
            'start_time' => 'Start Time',
            'end_time'   => 'End Time',
        ],
    ],
    'table' => [
        'columns' => [
            'name'       => 'Name',
            'start_time' => 'Start Time',
            'end_time'   => 'End Time',
        ],
    ],
];
