<?php

return [
    'title' => 'Comments',
    'form'  => [
        'fields' => [
            'visibility'  => 'Visibility',
            'attachments' => 'Attachments',
        ],
        'options' => [
            'public_comment' => 'Public Comment',
            'internal_note'  => 'Internal Note',
        ],
    ],
    'table' => [
        'columns' => [
            'user'        => 'User',
            'created_at'  => 'Created',
            'attachments' => 'Attachments',
        ],
        'visibility' => [
            'internal' => 'Internal',
            'public'   => 'Public',
        ],
        'placeholders' => [
            'zero' => '0',
        ],
    ],
    'errors' => [
        'invalid_user' => 'Authenticated user is invalid.',
    ],
];
