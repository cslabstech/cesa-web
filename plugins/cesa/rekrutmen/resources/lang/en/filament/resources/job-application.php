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
            'job_posting_id'             => 'Job Posting',
            'full_name'                  => 'Full Name (As Per ID Card)',
            'email'                      => 'Email',
            'gender'                     => 'Gender',
            'birth_date'                 => 'Birth Date',
            'marital_status'             => 'Marital Status',
            'address_ktp'                => 'Full Address As Per ID Card',
            'address_domicile'           => 'Current Domicile Address',
            'whatsapp_number'            => 'WhatsApp Number',
            'active_phone'               => 'Active Phone Number',
            'emergency_contact_name'     => 'Emergency Contact Name',
            'emergency_contact_relation' => 'Emergency Contact Relation',
            'emergency_contact_phone'    => 'Emergency Contact Phone',
            'current_stage_id'           => 'Current Stage',
            'status'                     => 'Status',
            'photo_path'                 => 'Latest Personal Photo',
            'resume_path'                => 'Latest CV/Resume',
        ],
    ],
    'table' => [
        'columns' => [
            'full_name'       => 'Full Name',
            'job_posting'     => 'Applied For',
            'email'           => 'Email',
            'whatsapp_number' => 'WhatsApp Number',
            'active_phone'    => 'Active Phone Number',
            'current_stage'   => 'Stage',
            'status'          => 'Status',
        ],
        'filters' => [
            'job_posting_id' => 'Job Posting',
            'status'         => 'Status',
        ],
        'actions' => [
            'change_stage'    => 'Move Stage',
            'to_stage_id'     => 'Move to Stage',
            'mark_hired'      => 'Accept Candidate',
            'mark_rejected'   => 'Reject Candidate',
            'notes'           => 'Notes',
            'download_resume' => 'Download Resume',
            'download_photo'  => 'Download Photo',
        ],
    ],
    'notifications' => [
        'stage_changed'   => 'Candidate stage updated successfully.',
        'marked_hired'    => 'Candidate marked as accepted successfully.',
        'marked_rejected' => 'Candidate marked as rejected successfully.',
    ],
    'board' => [
        'heading'             => 'Job Application Pipeline',
        'heading_with_job'    => 'Job Application Pipeline - :job',
        'subheading'          => 'Track candidate progress for the selected job posting.',
        'subheading_with_job' => 'Track candidate progress for job posting :job.',
    ],
    'workflow_notes' => [
        'stage_changed' => 'Candidate stage was moved.',
        'stage_synced'  => 'Candidate stage was synchronized with the job posting pipeline.',
        'hired'         => 'Candidate was marked as accepted.',
        'rejected'      => 'Candidate was marked as rejected.',
    ],
    'workflow_errors' => [
        'invalid_stage'          => 'The target stage does not belong to the selected job posting pipeline.',
        'terminal_stage_locked'  => 'A decided candidate cannot be moved to another stage.',
        'decision_note_required' => 'Decision notes are required.',
    ],
];
