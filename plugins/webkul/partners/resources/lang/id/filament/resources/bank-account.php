<?php

return [
    'navigation' => [
        'group' => 'Bank',
        'title' => 'Rekening Bank',
    ],
    'form' => [
        'account-number' => 'Nomor Rekening',
        'bank'           => 'Bank',
        'account-holder' => 'Pemegang Rekening',
        'can-send-money' => 'Dapat Mengirim Uang',
    ],
    'table' => [
        'columns' => [
            'account-number' => 'Nomor Rekening',
            'bank'           => 'Bank',
            'account-holder' => 'Pemegang Rekening',
            'send-money'     => 'Dapat Mengirim Uang',
            'created-at'     => 'Dibuat Pada',
            'updated-at'     => 'Diperbarui Pada',
            'deleted-at'     => 'Dihapus Pada',
        ],
        'filters' => [
            'bank'           => 'Bank',
            'account-holder' => 'Pemegang Rekening',
            'creator'        => 'Dibuat Oleh',
            'can-send-money' => 'Dapat Mengirim Uang',
        ],
        'groups' => [
            'bank'           => 'Bank',
            'can-send-money' => 'Dapat Mengirim Uang',
            'created-at'     => 'Dibuat Pada',
        ],
        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Rekening bank diperbarui',
                    'body'  => 'Rekening bank telah berhasil diperbarui.',
                ],
            ],
            'restore' => [
                'notification' => [
                    'title' => 'Rekening bank dipulihkan',
                    'body'  => 'Rekening bank telah berhasil dipulihkan.',
                ],
            ],
            'delete' => [
                'notification' => [
                    'title' => 'Rekening bank dihapus',
                    'body'  => 'Rekening bank telah berhasil dihapus.',
                ],
            ],
            'force-delete' => [
                'notification' => [
                    'title' => 'Rekening bank dihapus secara paksa',
                    'body'  => 'Rekening bank telah berhasil dihapus secara paksa.',
                ],
            ],
        ],
        'bulk-actions' => [
            'restore' => [
                'notification' => [
                    'title' => 'Rekening bank dipulihkan',
                    'body'  => 'Rekening bank telah berhasil dipulihkan.',
                ],
            ],
            'delete' => [
                'notification' => [
                    'title' => 'Rekening bank dihapus',
                    'body'  => 'Rekening bank telah berhasil dihapus.',
                ],
            ],
            'force-delete' => [
                'notification' => [
                    'title' => 'Rekening bank dihapus paksa',
                    'body'  => 'Rekening bank telah berhasil dihapus secara paksa.',
                ],
            ],
        ],
    ],
];
