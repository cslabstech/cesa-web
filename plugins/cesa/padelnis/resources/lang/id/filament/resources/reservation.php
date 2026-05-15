<?php

return [
    'navigation' => [
        'title' => 'Reservasi',
        'group' => 'Padelnis',
    ],

    'singular' => 'Reservasi',
    'plural'   => 'Reservasi',

    'fields' => [
        'id_reff'          => 'ID Reff',
        'customer_name'    => 'Nama Customer',
        'reservation_date' => 'Tanggal Reservasi',
        'court'            => 'Lapangan',
        'reservation_time' => 'Jam',
        'blocked_slots'    => 'Detail Blok',
        'transfer_amount'  => 'Nominal Transfer',
        'transfer_date'    => 'Tanggal Transfer',
        'notes'            => 'Keterangan',
        'created_at'       => 'Dibuat Pada',
    ],

    'form' => [
        'sections' => [
            'reservation' => [
                'title' => 'Detail Reservasi',
            ],
        ],

        'placeholders' => [
            'customer_name'    => 'Masukkan nama customer',
            'reservation_date' => 'Pilih tanggal reservasi',
            'court'            => 'Pilih lapangan',
            'reservation_time' => 'Pilih jam mulai - jam berakhir',
            'transfer_amount'  => 'Masukkan nominal transfer',
            'transfer_date'    => 'Pilih tanggal transfer',
            'notes'            => 'Tambahkan keterangan jika diperlukan',
        ],
    ],

    'table' => [
        'columns' => [
            'id_reff'          => 'ID Reff',
            'customer_name'    => 'Nama Customer',
            'reservation_date' => 'Tanggal',
            'reservation_time' => 'Jam',
            'blocked_slots'    => 'Detail Blok',
            'court'            => 'Lapangan',
            'transfer_amount'  => 'Nominal Transfer',
            'transfer_date'    => 'Tanggal Transfer',
            'notes'            => 'Keterangan',
            'created_at'       => 'Dibuat Pada',
        ],
    ],

    'filters' => [
        'reservation_from'        => 'Tanggal Dari',
        'reservation_until'       => 'Tanggal Sampai',
        'reservation_time'        => 'Jam',
        'reservation_range'       => 'Reservasi: :from - :until',
        'reservation_from_value'  => 'Reservasi dari: :date',
        'reservation_until_value' => 'Reservasi sampai: :date',
        'court'                   => 'Lapangan',
    ],

    'actions' => [
        'copy_id_reff' => 'ID Reff berhasil disalin.',
    ],

    'validation' => [
        'active_slot_unique' => 'Slot ini sudah dipesan untuk lapangan dan tanggal tersebut.',
    ],

    'exports' => [
        'notifications' => [
            'completed_body' => 'Ekspor reservasi selesai dengan :success baris berhasil diekspor dan :failed baris gagal diekspor.',
        ],
    ],

    'pages' => [
        'list' => [
            'header_actions' => [
                'create' => [
                    'label' => 'Buat Reservasi',
                ],
                'export' => [
                    'label' => 'Ekspor Reservasi',
                ],
            ],
        ],
    ],
];
