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
            'request_man_power_id'               => 'Source MPP for Autofill',
            'linked_request_man_powers_overview' => 'Related MPP',
            'linked_request_man_power_ids'       => 'Related MPP',
            'rekrutmen_pipeline_id'              => 'Recruitment Pipeline',
            'title'                              => 'Title',
            'slug'                               => 'Slug',
            'location'                           => 'Placement Location',
            'thumbnail_path'                     => 'Thumbnail',
            'closing_date'                       => 'Closing Date',
            'is_published'                       => 'Published for Recruitment',
            'description'                        => 'Job Desk',
            'requirements'                       => 'Qualifications',
        ],
        'helper_texts' => [
            'linked_request_man_power_ids' => 'Choose the MPP requests counted as this job posting need. Removed MPP requests are no longer included in the job posting fulfillment progress.',
        ],
        'errors' => [
            'invalid_request_man_power_selection' => 'The selected MPP is not available for this job posting.',
            'headcount_below_hired'               => 'The related MPP need (:needed) cannot be lower than hired candidates (:hired).',
        ],
        'summaries' => [
            'total_needed' => 'Total needed :count people',
        ],
    ],
    'table' => [
        'columns' => [
            'id'                         => 'Job Posting Code',
            'title'                      => 'Title',
            'rekrutmen_pipeline'         => 'Pipeline',
            'request_man_power'          => 'Manpower Request',
            'request_man_powers_summary' => 'Related MPP',
            'request_man_powers_count'   => 'MPP Requests',
            'requested_headcount_sum'    => 'Total Needed',
            'thumbnail_path'             => 'Thumbnail',
            'location'                   => 'Placement Location',
            'applications_count'         => 'Applicants',
            'is_published'               => 'Published',
            'closing_date'               => 'Closing Date',
        ],
        'placeholders' => [
            'request_man_power'          => '-',
            'request_man_powers_summary' => '-',
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
    'relations' => [
        'request_man_powers' => [
            'title'   => 'Related MPP',
            'actions' => [
                'view' => 'View MPP',
            ],
        ],
    ],
];
