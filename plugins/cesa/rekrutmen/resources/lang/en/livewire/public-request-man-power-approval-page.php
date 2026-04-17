<?php

return [
    'heading'               => 'Manpower Request Approval',
    'subheading'            => 'Review the request as approver: :name.',
    'page_title'            => 'MAN POWER REQUEST APPROVAL',
    'requester_label'       => 'Submitted by',
    'current_status'        => 'Current status',
    'summary_heading'       => 'Request Summary',
    'approval_flow_heading' => 'Approval Steps',
    'action_heading'        => 'Action',
    'action_subheading'     => 'Add notes if needed, then choose your decision.',
    'step_label'            => 'Step :step',
    'fields'                => [
        'position'         => 'Requested Position',
        'division'         => 'Division',
        'business_entity'  => 'Company',
        'estimated_join'   => 'Estimated Join',
        'requirements'     => 'Required Qualifications',
        'job_description'  => 'Job Description',
        'notes'            => 'Approver Notes',
    ],
    'placeholders' => [
        'notes' => 'Add approval or rejection notes if needed.',
    ],
    'actions' => [
        'approve'       => 'Approve',
        'reject'        => 'Reject',
        'view_progress' => 'View Progress',
    ],
    'notifications' => [
        'approved'          => 'The approval has been recorded.',
        'rejected'          => 'The rejection has been recorded.',
        'already_processed' => 'This link can no longer be processed.',
        'link_expired'      => 'The approval link has expired.',
        'rate_limited'      => 'Too many attempts. Try again in :seconds seconds.',
    ],
];
