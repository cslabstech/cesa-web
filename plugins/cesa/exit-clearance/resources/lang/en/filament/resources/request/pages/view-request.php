<?php

return [
    'actions' => [
        'resend_pending_approvers' => [
            'label'                   => 'Resend to pending approvers',
            'modal_heading'           => 'Resend notification',
            'modal_description'       => 'Notifications will be resent to approvers whose status is still pending.',
            'approver_id'             => 'Approver',
            'approver_id_placeholder' => 'All pending approvers',
            'approver_id_helper'      => 'Pick one approver, or leave empty to send to every pending approver.',
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
