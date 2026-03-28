<?php

return [
    'approval' => [
        'subject'           => ':prefix Approval Required - :title',
        'greeting'          => 'Hello :name,',
        'action_text'       => 'Open Approval Page',
        'heading'           => 'Transfer Request Approval',
        'intro'             => ':approver, your approval is required for the following transfer request.',
        'summary_heading'   => 'Request Summary',
        'approvals_heading' => 'Approval Details',
        'progress_hint'     => 'Track progress:',
        'progress_link'     => 'view request status',
        'plain'             => [
            'intro'       => 'Please review the following transfer request:',
            'uid'         => 'UID: :uid',
            'requester'   => 'Requester: :requester',
            'division'    => 'Division: :division',
            'amount'      => 'Transfer Amount: Rp :amount',
            'purpose'     => 'Purpose: :purpose',
            'status'      => 'Current status: :status',
            'approvers'   => 'Approver list:',
            'thanks'      => 'Thank you.',
        ],
    ],
    'status' => [
        'subject'           => ':prefix :title - :status',
        'greeting'          => 'Hello :name,',
        'action_text'       => 'View Approval Progress',
        'heading'           => 'Transfer Request Status',
        'intro'             => 'Your transfer request status is currently: :status',
        'summary_heading'   => 'Request Summary',
        'approvals_heading' => 'Approval Details',
        'progress_hint'     => 'View the complete progress here:',
        'progress_link'     => 'check request status',
        'plain'             => [
            'intro'     => 'Your transfer request status has been updated to: :status',
            'uid'       => 'UID: :uid',
            'division'  => 'Division: :division',
            'amount'    => 'Transfer Amount: Rp :amount',
        ],
    ],
];
