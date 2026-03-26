<?php

return [
    'title'       => 'Lead Form',
    'description' => 'Please complete the lead details below.',
    'required'    => '* Required question',

    'actions' => [
        'submit'         => 'Submit',
        'submit_another' => 'Submit another lead',
    ],

    'pagination' => [
        'single_page' => 'Page :current of :total',
    ],

    'messages' => [
        'success' => 'Thank you. Your lead has been submitted successfully.',
        'generic' => 'Something went wrong while submitting the form. Please try again.',
    ],

    'notifications' => [
        'submitted' => [
            'title' => 'Lead submitted',
            'body'  => 'Thank you, the lead has been saved successfully.',
        ],
    ],

    'whatsapp_validation' => [
        'action'         => 'Check WhatsApp',
        'hint'           => 'Use this check to confirm the number is registered on WhatsApp.',
        'success'        => 'This number is registered on WhatsApp.',
        'not_registered' => 'This number is not registered on WhatsApp.',
        'invalid'        => 'The number is invalid.',
        'rate_limited'   => 'Too many attempts. Please try again in a moment.',
        'failed'         => 'WhatsApp validation failed. Please try again.',
    ],
];
