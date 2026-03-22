<?php

return [
    'header' => [
        'sub-heading' => [
            'accept-invitation' => 'Terima Undangan',
        ],
    ],
    'title'   => 'Daftar',
    'heading' => 'Mendaftar',
    'actions' => [
        'login' => [
            'before' => 'atau',
            'label'  => 'masuk ke akun Anda',
        ],
    ],
    'form' => [
        'email' => [
            'label' => 'Alamat email',
        ],
        'name' => [
            'label' => 'Nama',
        ],
        'password' => [
            'label'                => 'Kata sandi',
            'validation_attribute' => 'kata sandi',
        ],
        'password_confirmation' => [
            'label' => 'Konfirmasikan kata sandi',
        ],
        'actions' => [
            'register' => [
                'label' => 'Mendaftar',
            ],
        ],
    ],
    'notifications' => [
        'throttled' => [
            'title' => 'Terlalu banyak upaya pendaftaran',
            'body'  => 'Silakan coba lagi dalam :seconds detik.',
        ],
    ],
];
