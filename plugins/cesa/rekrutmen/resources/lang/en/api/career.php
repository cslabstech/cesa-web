<?php

return [
    'messages' => [
        'job_listed'            => 'Job listings retrieved successfully.',
        'job_not_found'         => 'Job posting not found.',
        'job_detail_retrieved'  => 'Job detail retrieved successfully.',
        'job_not_open'          => 'Job posting not found or is no longer open.',
        'application_submitted' => 'Application submitted successfully.',
    ],
    'validation' => [
        'messages' => [
            'full_name.required'       => 'The full name field is required.',
            'email.required'           => 'The email field is required.',
            'email.email'              => 'The email format is invalid.',
            'phone.required'           => 'The phone number field is required.',
            'portfolio_url.url'        => 'The portfolio URL format is invalid.',
            'resume.mimes'             => 'The resume file must be a pdf, doc, or docx file.',
            'resume.max'               => 'The resume file may not be greater than 5 MB.',
            'additional_answers.array' => 'Additional answers must be provided as an array.',
            'required'                 => 'The :attribute field is required.',
        ],
        'attributes' => [
            'full_name'     => 'full name',
            'email'         => 'email',
            'phone'         => 'phone number',
            'portfolio_url' => 'portfolio URL',
            'resume'        => 'resume',
            'cover_letter'  => 'cover letter',
        ],
    ],
    'application' => [
        'additional_answers_prefix' => 'Additional Answers:',
        'submitted_via_public_api'  => 'Application submitted via public API.',
    ],
];
