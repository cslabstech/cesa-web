<?php

return [
    'navigation' => [
        'label' => 'Pipeline',
    ],
    'model' => [
        'singular' => 'Pipeline',
        'plural'   => 'Pipeline',
    ],
    'form' => [
        'sections' => [
            'pipeline_details' => 'Detail Pipeline',
            'stages'           => 'Tahapan Rekrutmen',
        ],
        'descriptions' => [
            'stages' => 'Tentukan tahapan untuk pipeline ini secara berurutan.',
        ],
        'fields' => [
            'name'        => 'Nama',
            'description' => 'Deskripsi',
        ],
        'actions' => [
            'add_stage' => 'Tambah Tahap',
        ],
    ],
    'table' => [
        'columns' => [
            'name'         => 'Nama',
            'stages_count' => 'Total Tahap',
            'description'  => 'Deskripsi',
        ],
    ],
];
