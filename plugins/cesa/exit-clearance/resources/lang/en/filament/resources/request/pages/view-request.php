<?php

return [
    'actions' => [
        'resend_pending_approvers' => [
            'label'             => 'Resend to pending approvers',
            'modal_heading'     => 'Resend notification',
            'modal_description' => 'Notifications will be resent to approvers whose status is still pending.',
        ],
    ],
    'notifications' => [
        'form_completed' => [
            'title' => 'Form already completed',
            'body'  => 'The form status is no longer pending.',
        ],
        'no_pending_approvers' => [
            'title' => 'No pending approvers',
            'body'  => 'All approvers have already been processed.',
        ],
        'notifications_resent' => [
            'title' => 'Notifications resent',
            'body'  => 'Sent to :count pending approver(s).',
        ],
    ],
];
