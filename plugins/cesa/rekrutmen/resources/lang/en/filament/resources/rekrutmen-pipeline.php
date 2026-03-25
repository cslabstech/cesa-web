<?php

return [
    'navigation' => [
        'label' => 'Recruitment Pipelines',
    ],
    'model' => [
        'singular' => 'Recruitment Pipeline',
        'plural'   => 'Recruitment Pipelines',
    ],
    'form' => [
        'sections' => [
            'pipeline_details' => 'Pipeline Details',
            'stages'           => 'Recruitment Stages',
        ],
        'descriptions' => [
            'stages' => 'Define the stages for this pipeline in order.',
        ],
        'fields' => [
            'name'        => 'Name',
            'description' => 'Description',
        ],
        'actions' => [
            'add_stage' => 'Add Stage',
        ],
    ],
    'table' => [
        'columns' => [
            'name'         => 'Name',
            'stages_count' => 'Total Stages',
        ],
    ],
];
