<?php

return [
    'title'    => 'RECRUITMENT PROGRESS',
    'subtitle' => 'Tracking recruitment activities and progress',

    'filters' => [
        'period'         => 'Period',
        'position'       => 'Position / Job Posting',
        'stage'          => 'Activity Stage',
        'company'        => 'Company',
        'all_positions'  => 'All Positions',
        'all_stages'     => 'All Stages',
        'all_companies'  => 'All Companies',
    ],

    'summary' => [
        'active_positions'  => 'Active Positions',
        'total_applicants'  => 'Total Applicants',
        'activities'        => 'Activities This Period',
        'hired'             => 'Hired',
        'rejected'          => 'Rejected',
        'openings_label'    => 'openings',
        'applicants_label'  => 'in process',
        'activities_label'  => 'activities done',
        'hired_label'       => 'candidates hired',
        'rejected_label'    => 'candidates rejected',
    ],

    'tabs' => [
        'timeline'     => 'Timeline',
        'per_position' => 'Per Position',
        'overview'     => 'Overview',
    ],

    'labels' => [
        'stage'            => 'Stage',
        'by'               => 'by',
        'passed'           => 'Passed',
        'failed'           => 'Failed',
        'pending'          => 'Pending',
        'activities_count' => ':count activities',
        'view_candidates'  => 'View :count candidates detail',
        'company'          => 'Company',
        'location'         => 'Location',
        'needed'           => 'Needed',
        'est_join'         => 'Est. Join',
        'open'             => 'Open',
        'closed'           => 'Closed',
        'pipeline_funnel'  => 'Pipeline Funnel',
        'activity_history' => 'Activity History',
        'total'            => 'Total',
    ],

    'table' => [
        'position'      => 'Position',
        'company'       => 'Company',
        'needed'        => 'Needed',
        'applicants'    => 'Applicants',
        'process'       => 'In Progress',
        'accepted'      => 'Hired',
        'rejected'      => 'Rejected',
        'last_activity' => 'Last Activity',
        'fulfillment'   => 'Fulfillment',
        'total'         => 'TOTAL',
        'candidate'     => 'Candidate Name',
        'result'        => 'Result',
        'notes'         => 'Notes',
    ],

    'empty' => [
        'no_activities' => 'No activities found for this period.',
        'no_positions'  => 'No positions found.',
    ],

    'summary_text' => [
        'total_candidates' => ':count Candidates',
        'passed'           => ':count Passed',
        'failed'           => ':count Failed',
        'pending'          => ':count Pending',
    ],
];
