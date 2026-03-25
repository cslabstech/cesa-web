<?php

return [
    'navigation' => [
        'label' => 'Job Applications',
    ],
    'model' => [
        'singular' => 'Job Application',
        'plural'   => 'Job Applications',
    ],
    'generated' => [
        'unknown_position' => 'unknown-position',
    ],
    'form' => [
        'sections' => [
            'candidate_information' => 'Candidate Information',
            'application_details'   => 'Application Details',
        ],
        'fields' => [
            'job_posting_id'   => 'Job Posting',
            'full_name'        => 'Full Name',
            'email'            => 'Email',
            'phone'            => 'Phone Number',
            'portfolio_url'    => 'Portfolio URL',
            'current_stage_id' => 'Current Stage',
            'status'           => 'Status',
            'resume_path'      => 'Resume',
            'cover_letter'     => 'Cover Letter',
        ],
    ],
    'table' => [
        'columns' => [
            'full_name'     => 'Full Name',
            'job_posting'   => 'Applied For',
            'email'         => 'Email',
            'phone'         => 'Phone Number',
            'current_stage' => 'Stage',
            'status'        => 'Status',
        ],
        'filters' => [
            'job_posting_id' => 'Job Posting',
            'status'         => 'Status',
        ],
        'actions' => [
            'change_stage'    => 'Move Stage',
            'to_stage_id'     => 'Move to Stage',
            'notes'           => 'Notes',
            'download_resume' => 'Resume',
        ],
    ],
];
