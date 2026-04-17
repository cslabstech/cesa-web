<?php

return [
    'navigation' => [
        'label' => 'Job Postings',
    ],
    'model' => [
        'singular' => 'Job Posting',
        'plural'   => 'Job Postings',
    ],
    'generated' => [
        'title' => 'Job Posting #:id',
    ],
    'form' => [
        'sections' => [
            'job_information' => 'Job Information',
            'details'         => 'Details',
            'settings'        => 'Settings',
        ],
        'fields' => [
            'request_man_power_id'  => 'Related Manpower Request (Optional)',
            'rekrutmen_pipeline_id' => 'Recruitment Pipeline',
            'title'                 => 'Title',
            'slug'                  => 'Slug',
            'location'              => 'Placement Location',
            'thumbnail_path'        => 'Thumbnail',
            'closing_date'          => 'Closing Date',
            'is_published'          => 'Published for Recruitment',
            'description'           => 'Job Desk',
            'requirements'          => 'Qualifications',
        ],
    ],
    'table' => [
        'columns' => [
            'title'              => 'Title',
            'rekrutmen_pipeline' => 'Pipeline',
            'request_man_power'  => 'Manpower Request',
            'thumbnail_path'     => 'Thumbnail',
            'location'           => 'Placement Location',
            'applications_count' => 'Applicants',
            'is_published'       => 'Published',
            'closing_date'       => 'Closing Date',
        ],
        'placeholders' => [
            'request_man_power' => '-',
        ],
        'filters' => [
            'is_published'          => 'Published',
            'rekrutmen_pipeline_id' => 'Pipeline',
            'request_man_power_id'  => 'Manpower Request',
            'availability'          => 'Availability',
        ],
        'filter_options' => [
            'availability' => [
                'open'    => 'Open',
                'expired' => 'Expired',
            ],
        ],
        'actions' => [
            'open_pipeline' => 'Open Candidate Pipeline',
        ],
    ],
];
