<?php

return [
    'title' => 'Komentar',
    'form'  => [
        'fields' => [
            'visibility'  => 'Visibilitas',
            'attachments' => 'Lampiran',
        ],
        'options' => [
            'public_comment' => 'Komentar Publik',
            'internal_note'  => 'Catatan Internal',
        ],
    ],
    'table' => [
        'columns' => [
            'user'        => 'Pengguna',
            'created_at'  => 'Dibuat',
            'attachments' => 'Lampiran',
        ],
        'visibility' => [
            'internal' => 'Internal',
            'public'   => 'Publik',
        ],
        'placeholders' => [
            'zero' => '0',
        ],
    ],
    'errors' => [
        'invalid_user' => 'Pengguna terautentikasi tidak valid.',
    ],
];
