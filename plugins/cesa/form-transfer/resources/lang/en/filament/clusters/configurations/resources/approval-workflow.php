<?php

return [
    'navigation' => [
        'group' => 'Form-Specific Settings',
        'label' => 'Approval Workflows',
    ],
    'fields' => [
        'form_transfer'      => 'Form Transfer',
        'division'           => 'Division',
        'division_hint'      => 'Only divisions related to the selected form are available.',
        'description'        => 'Description',
        'is_active'          => 'Active',
        'steps'              => 'Approval Steps',
        'step_label'         => 'Step Label',
        'step_default_name'  => 'Default Approver Name',
        'step_default_email' => 'Default Approver Email',
        'step_default_title' => 'Default Approver Title',
        'step_default_phone' => 'Default Approver Phone',
        'step_is_mandatory'  => 'Mandatory Step',
    ],
    'columns' => [
        'form_transfer' => 'Form Transfer',
        'division'      => 'Division',
        'steps'         => 'Steps',
        'step_summary'  => 'Step Summary',
        'is_active'     => 'Active',
    ],
    'filters' => [
        'form_transfer' => 'Form Transfer',
        'division'      => 'Division',
        'is_active'     => 'Active Status',
    ],
    'actions' => [
        'add_step' => 'Add Approval Step',
    ],
];
