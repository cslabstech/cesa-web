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
            'stages' => 'Tentukan tahapan untuk pipeline ini secara berurutan. Tahap final Hired akan selalu dikunci di posisi paling akhir.',
        ],
        'fields' => [
            'name'        => 'Nama',
            'description' => 'Deskripsi',
        ],
        'helpers' => [
            'final_hired_stage_locked' => 'Tahap final Hired dikunci, tidak bisa diubah nama dan akan selalu berada di urutan paling akhir.',
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
    'errors' => [
        'final_hired_stage_locked'    => 'Tahap final Hired tidak bisa dihapus atau diubah.',
        'duplicate_final_hired_stage' => 'Pipeline hanya boleh memiliki satu tahap final Hired.',
    ],
];
