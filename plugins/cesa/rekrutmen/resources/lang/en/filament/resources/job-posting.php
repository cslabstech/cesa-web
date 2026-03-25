<?php

return [
    'navigation' => [
        'label' => 'Job Postings',
    ],
    'model' => [
        'singular' => 'Job Posting',
        'plural'   => 'Job Postings',
    ],
    'generated_title' => 'Job Posting #:id',
    'form'            => [
        'sections' => [
            'job_information' => 'Job Information',
            'details'         => 'Details',
        ],
        'fields' => [
            'request_man_power_id'  => 'Related Manpower Request (Optional)',
            'rekrutmen_pipeline_id' => 'Recruitment Pipeline',
            'title'                 => 'Title',
            'slug'                  => 'Slug',
            'location'              => 'Location',
            'closing_date'          => 'Closing Date',
            'is_published'          => 'Published for Recruitment',
            'description'           => 'Description',
            'requirements'          => 'Requirements',
        ],
    ],
    'table' => [
        'columns' => [
            'title'        => 'Title',
            'location'     => 'Location',
            'is_published' => 'Published',
            'closing_date' => 'Closing Date',
        ],
        'filters' => [
            'is_published' => 'Published',
        ],
    ],
];
