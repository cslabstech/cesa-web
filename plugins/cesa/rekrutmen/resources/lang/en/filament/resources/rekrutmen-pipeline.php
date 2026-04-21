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
            'stages' => 'Define the stages for this pipeline in order. The final Hired stage will always stay locked at the end.',
        ],
        'fields' => [
            'name'        => 'Name',
            'description' => 'Description',
        ],
        'helpers' => [
            'final_hired_stage_locked' => 'The final Hired stage is locked, cannot be renamed, and will always stay at the last position.',
        ],
        'actions' => [
            'add_stage' => 'Add Stage',
        ],
    ],
    'table' => [
        'columns' => [
            'name'         => 'Name',
            'stages_count' => 'Total Stages',
            'description'  => 'Description',
        ],
    ],
    'errors' => [
        'final_hired_stage_locked'    => 'The final Hired stage cannot be deleted or changed.',
        'duplicate_final_hired_stage' => 'A pipeline may only have one final Hired stage.',
    ],
];
