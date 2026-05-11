<?php

return [
    'navigation' => [
        'label' => 'Record Recruitment Activity',
    ],
    'model' => [
        'singular' => 'Recruitment Activity',
        'plural'   => 'Recruitment Activities',
    ],
    'form' => [
        'sections' => [
            'activity_details' => 'Activity Details',
            'candidates'       => 'Participating Candidates',
            'info'             => 'Information',
        ],
        'fields' => [
            'job_posting_id'  => 'Job Posting',
            'stage_id'        => 'Stage',
            'activity_date'   => 'Activity Date',
            'generated_title' => 'Activity Title',
            'candidate'       => 'Candidate',
            'result'          => 'Result',
            'notes'           => 'Notes',
        ],
        'actions' => [
            'add_candidate' => 'Add Candidate',
            'create'        => 'Record Activity',
        ],
        'helpers' => [
            'info_note'                   => 'Candidates marked "Passed" will automatically advance to the next stage. Candidates marked "Failed" will be automatically rejected.',
            'failed_requires_notes'       => 'Notes are required when a candidate is marked as failed.',
            'generated_title_placeholder' => 'The title will be generated automatically from the pipeline stage and activity date.',
            'create_subheading'           => 'Record stage activities without leaving the core recruitment pipeline and application flow.',
        ],
    ],
    'table' => [
        'columns' => [
            'activity_date'     => 'Date',
            'job_posting'       => 'Job Posting',
            'title'             => 'Title',
            'stage'             => 'Stage',
            'performed_by'      => 'Performed By',
            'summary'           => 'Summary',
            'recent_activities' => 'Recent Activities',
        ],
        'filters' => [
            'job_posting_id'     => 'Job Posting',
            'stage_id'           => 'Activity Stage',
            'activity_date'      => 'Activity Date',
            'date_from'          => 'From',
            'date_until'         => 'Until',
            'all_job_postings'   => 'All Job Postings',
            'all_stages'         => 'All Stages',
        ],
        'actions' => [
            'reset_filters'       => 'Reset Filters',
            'delete'              => 'Delete',
            'delete_confirmation' => 'Delete this grouped recruitment activity?',
        ],
    ],
    'notifications' => [
        'activity_recorded' => 'Recruitment activity recorded successfully. Passed candidates have been advanced to the next stage.',
        'no_candidates'     => 'Select at least one candidate to record the activity.',
        'deleted'           => 'Recruitment activity deleted successfully.',
    ],
    'errors' => [
        'invalid_stage'      => 'The selected stage does not belong to the selected job posting pipeline.',
        'invalid_candidates' => 'Selected candidates must belong to the active job posting and stage.',
        'invalid_result'     => 'Activity result must be Passed, Failed, or Pending.',
    ],
    'relation-managers' => [
        'entries' => [
            'title'   => 'Candidate Entries',
            'columns' => [
                'candidate'  => 'Candidate',
                'result'     => 'Result',
                'notes'      => 'Notes',
            ],
        ],
    ],
];
