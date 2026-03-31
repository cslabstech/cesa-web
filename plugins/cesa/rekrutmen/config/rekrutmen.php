<?php

return [
    'application_form' => [
        'default_fields' => [
            [
                'name'     => 'full_name',
                'label'    => 'rekrutmen::config/application-form.fields.full_name',
                'type'     => 'text',
                'required' => true,
            ],
            [
                'name'     => 'email',
                'label'    => 'rekrutmen::config/application-form.fields.email',
                'type'     => 'email',
                'required' => true,
            ],
            [
                'name'     => 'gender',
                'label'    => 'rekrutmen::config/application-form.fields.gender',
                'type'     => 'select',
                'required' => true,
                'options'  => [
                    [
                        'value' => 'male',
                        'label' => 'rekrutmen::enums/job-application-gender.male',
                    ],
                    [
                        'value' => 'female',
                        'label' => 'rekrutmen::enums/job-application-gender.female',
                    ],
                ],
            ],
            [
                'name'     => 'birth_date',
                'label'    => 'rekrutmen::config/application-form.fields.birth_date',
                'type'     => 'date',
                'required' => true,
            ],
            [
                'name'     => 'marital_status',
                'label'    => 'rekrutmen::config/application-form.fields.marital_status',
                'type'     => 'select',
                'required' => true,
                'options'  => [
                    [
                        'value' => 'single',
                        'label' => 'rekrutmen::enums/job-application-marital-status.single',
                    ],
                    [
                        'value' => 'married',
                        'label' => 'rekrutmen::enums/job-application-marital-status.married',
                    ],
                    [
                        'value' => 'divorced',
                        'label' => 'rekrutmen::enums/job-application-marital-status.divorced',
                    ],
                ],
            ],
            [
                'name'     => 'address_ktp',
                'label'    => 'rekrutmen::config/application-form.fields.address_ktp',
                'type'     => 'textarea',
                'required' => true,
            ],
            [
                'name'     => 'address_domicile',
                'label'    => 'rekrutmen::config/application-form.fields.address_domicile',
                'type'     => 'textarea',
                'required' => true,
            ],
            [
                'name'     => 'whatsapp_number',
                'label'    => 'rekrutmen::config/application-form.fields.whatsapp_number',
                'type'     => 'text',
                'required' => true,
            ],
            [
                'name'     => 'active_phone',
                'label'    => 'rekrutmen::config/application-form.fields.active_phone',
                'type'     => 'text',
                'required' => true,
            ],
            [
                'name'     => 'emergency_contact_name',
                'label'    => 'rekrutmen::config/application-form.fields.emergency_contact_name',
                'type'     => 'text',
                'required' => true,
            ],
            [
                'name'     => 'emergency_contact_relation',
                'label'    => 'rekrutmen::config/application-form.fields.emergency_contact_relation',
                'type'     => 'text',
                'required' => true,
            ],
            [
                'name'     => 'emergency_contact_phone',
                'label'    => 'rekrutmen::config/application-form.fields.emergency_contact_phone',
                'type'     => 'text',
                'required' => true,
            ],
            [
                'name'     => 'photo',
                'label'    => 'rekrutmen::config/application-form.fields.photo',
                'type'     => 'file',
                'required' => true,
            ],
            [
                'name'     => 'resume',
                'label'    => 'rekrutmen::config/application-form.fields.resume',
                'type'     => 'file',
                'required' => true,
            ],
        ],
        'by_slug' => [],
    ],

    'security' => [
        'recaptcha' => [
            'enabled'         => env('REKRUTMEN_RECAPTCHA_ENABLED', false),
            'site_key'        => env('REKRUTMEN_RECAPTCHA_SITE_KEY'),
            'secret_key'      => env('REKRUTMEN_RECAPTCHA_SECRET_KEY'),
            'action'          => env('REKRUTMEN_RECAPTCHA_ACTION', 'request_man_power'),
            'score_threshold' => (float) env('REKRUTMEN_RECAPTCHA_SCORE_THRESHOLD', 0.5),
            'timeout'         => (int) env('REKRUTMEN_RECAPTCHA_TIMEOUT', 5),
        ],
    ],
];
