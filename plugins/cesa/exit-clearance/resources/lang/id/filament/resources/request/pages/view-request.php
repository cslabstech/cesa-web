<?php

return [
    'actions' => [
        'resend_pending_approvers' => [
            'label'             => 'Kirim ulang ke approver pending',
            'modal_heading'     => 'Kirim ulang notifikasi',
            'modal_description' => 'Notifikasi akan dikirim ulang ke approver yang statusnya masih pending.',
        ],
    ],
    'notifications' => [
        'form_completed' => [
            'title' => 'Form sudah selesai',
            'body'  => 'Status form sudah bukan pending.',
        ],
        'no_pending_approvers' => [
            'title' => 'Tidak ada approver pending',
            'body'  => 'Semua approver sudah diproses.',
        ],
        'notifications_resent' => [
            'title' => 'Notifikasi dikirim ulang',
            'body'  => 'Dikirim ke :count approver pending.',
        ],
    ],
];
