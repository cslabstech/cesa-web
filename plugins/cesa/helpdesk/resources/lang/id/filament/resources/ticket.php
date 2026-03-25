<?php

return [
    'label' => [
        'single' => 'Tiket',
        'plural' => 'Tiket',
    ],
    'form' => [
        'sections' => [
            'ticket_detail' => 'Detail Tiket',
            'assignment'    => 'Penugasan',
        ],
        'fields' => [
            'unit_id'                 => 'Unit',
            'problem_category_id'     => 'Kategori Masalah',
            'title'                   => 'Judul',
            'description'             => 'Deskripsi',
            'supporting_attachments'  => 'Lampiran Pendukung',
            'priority_id'             => 'Prioritas',
            'company_id'              => 'Perusahaan',
            'ticket_status_name'      => 'Status',
            'responsible_id'          => 'Penanggung Jawab',
            'owner_name'              => 'Pemilik',
            'approved_at'             => 'Disetujui Pada',
            'solved_at'               => 'Diselesaikan Pada',
            'close_reason'            => 'Alasan Penutupan',
            'cancel_reason'           => 'Alasan Pembatalan',
            'reopen_reason'           => 'Alasan Buka Ulang',
        ],
        'placeholders' => [
            'open' => 'Open',
            'dash' => '-',
        ],
    ],
    'table' => [
        'columns' => [
            'unit'         => 'Unit',
            'category'     => 'Kategori',
            'priority'     => 'Prioritas',
            'status'       => 'Status',
            'responsible'  => 'Penanggung Jawab',
            'created_at'   => 'Dibuat Pada',
        ],
        'filters' => [
            'unit_id'          => 'Unit',
            'ticket_status_id' => 'Status',
            'priority_id'      => 'Prioritas',
            'responsible_id'   => 'Penanggung Jawab',
        ],
        'placeholders' => [
            'dash' => '-',
        ],
    ],
    'infolist' => [
        'sections' => [
            'ticket_detail' => 'Detail Tiket',
            'attachments'   => 'Lampiran',
        ],
        'entries' => [
            'unit'                  => 'Unit',
            'category'              => 'Kategori',
            'priority'              => 'Prioritas',
            'status'                => 'Status',
            'company'               => 'Perusahaan',
            'owner'                 => 'Pemilik',
            'responsible'           => 'Penanggung Jawab',
            'approved_at'           => 'Disetujui Pada',
            'solved_at'             => 'Diselesaikan Pada',
            'close_reason'          => 'Alasan Penutupan',
            'cancel_reason'         => 'Alasan Pembatalan',
            'reopen_reason'         => 'Alasan Buka Ulang',
            'supporting_attachments'=> 'Lampiran Pendukung',
        ],
        'placeholders' => [
            'dash' => '-',
        ],
    ],
];
