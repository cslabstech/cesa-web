<?php

return [
    'navigation' => [
        'label' => 'Offices',
    ],
    'model' => [
        'singular' => 'Office',
        'plural'   => 'Offices',
    ],
    'form' => [
        'fields' => [
            'name'      => 'Name',
            'latitude'  => 'Latitude',
            'longitude' => 'Longitude',
            'radius'    => 'Radius',
        ],
    ],
    'table' => [
        'columns' => [
            'name'      => 'Name',
            'latitude'  => 'Latitude',
            'longitude' => 'Longitude',
            'radius'    => 'Radius',
        ],
    ],
];
