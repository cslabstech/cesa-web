<?php

return [
    'navigation' => [
        'label' => 'Payroll Periods',
    ],
    'model' => [
        'singular' => 'Payroll Period',
        'plural'   => 'Payroll Periods',
    ],
    'form' => [
        'sections' => [
            'period_details' => 'Period Details',
        ],
        'fields' => [
            'name'                 => 'Name',
            'start_date'           => 'Start Date',
            'end_date'             => 'End Date',
            'status'               => 'Status',
            'auto_generate'        => 'Automatically Generate Payroll',
            'auto_generate_helper' => 'Generate payroll immediately after creating the period for employees who have attendance or approved overtime data. Uncheck to generate manually later.',
        ],
    ],
    'table' => [
        'columns' => [
            'name'       => 'Name',
            'start_date' => 'Start Date',
            'end_date'   => 'End Date',
            'status'     => 'Status',
            'created_at' => 'Created At',
        ],
        'actions' => [
            'generate_payroll' => [
                'label'             => 'Generate Payroll',
                'modal_heading'     => 'Generate Payroll',
                'modal_description' => 'Are you sure? This will calculate payroll only for employees who have attendance or approved overtime data in this period. Existing payroll records for this period will be regenerated using the latest data.',
            ],
            'mark_as_paid' => [
                'label'             => 'Mark as Paid',
                'modal_description' => 'Are you sure? This will mark this payroll period as paid. This action cannot be undone.',
            ],
        ],
    ],
    'notifications' => [
        'payroll_generated' => [
            'title' => 'Success',
            'body'  => 'Payroll generated successfully.',
        ],
        'marked_as_paid' => [
            'title' => 'Success',
            'body'  => 'Payroll period has been marked as paid.',
        ],
        'generate_failed' => [
            'title' => 'Error',
            'body'  => 'Failed to generate payroll: :message',
        ],
    ],
];
