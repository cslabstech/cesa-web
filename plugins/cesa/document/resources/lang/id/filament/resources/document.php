<?php

return [
    'title' => 'Dokumen',

    'navigation' => [
        'title' => 'Dokumen',
        'group' => null,
    ],

    'singular' => 'Dokumen',
    'plural'   => 'Dokumen',

    'form' => [
        'sections' => [
            'basic_information' => [
                'title' => 'Informasi Dasar',

                'fields' => [
                    'title'       => 'Judul Dokumen',
                    'source_type' => 'Jenis Sumber',
                ],
            ],
            'content' => [
                'title' => 'Konten',

                'fields' => [
                    'docx_file'    => 'File DOCX',
                    'html_content' => 'Konten HTML',
                ],
            ],
        ],

        'placeholders' => [
            'title' => 'Masukkan judul dokumen',
        ],
    ],

    'actions' => [
        'download_excel_template' => 'Unduh Template Excel',
        'download_word'           => 'Unduh Dokumen Word',
    ],

    'helpers' => [
        'filename' => 'Kosongkan untuk menggunakan judul dokumen, atau gunakan placeholder {{$KEY}} untuk membentuk nama file yang dinamis.',
        'excel'    => 'Gunakan template yang dihasilkan dengan key placeholder sebagai header. Setiap baris akan menghasilkan satu dokumen, dan banyak baris akan dibundel ke dalam file ZIP.',
    ],

    'placeholders' => [
        'filename' => 'Contoh: perjanjian-{{$NAME}}',
    ],

    'fields' => [
        'title'        => 'Judul Dokumen',
        'source_type'  => 'Jenis Sumber',
        'docx_file'    => 'File DOCX',
        'html_content' => 'Konten HTML',
        'filename'     => 'Nama File',
        'mode'         => 'Mode',
        'key_value'    => 'Nilai Placeholder',
        'key'          => 'Key',
        'value'        => 'Nilai',
        'upload_excel' => 'Unggah File Excel',
        'created_at'   => 'Dibuat Pada',
        'updated_at'   => 'Diperbarui Pada',
    ],

    'options' => [
        'source_type' => [
            'html' => 'HTML',
            'docx' => 'DOCX',
        ],
        'mode' => [
            'single' => 'Satu Dokumen',
            'bulk'   => 'Massal dari Excel',
        ],
    ],

    'table' => [
        'columns' => [
            'title'       => 'Judul Dokumen',
            'source_type' => 'Jenis Sumber',
            'created_at'  => 'Dibuat Pada',
            'updated_at'  => 'Diperbarui Pada',
        ],
    ],

    'notifications' => [
        'template_error' => [
            'title' => 'Gagal membuat template Excel',
        ],
        'placeholder_error' => [
            'title' => 'Gagal mendeteksi placeholder dokumen',
        ],
        'download_error' => [
            'title' => 'Gagal mengunduh dokumen',
        ],
        'docx_missing' => [
            'title' => 'File DOCX tidak ditemukan',
        ],
    ],

    'messages' => [
        'docx_missing'         => 'File DOCX tidak ditemukan.',
        'bulk_excel_required'  => 'Mode massal memerlukan file Excel.',
        'excel_missing'        => 'File Excel tidak ditemukan di penyimpanan lokal.',
        'excel_empty'          => 'Tidak ada baris data. Silakan tambahkan nilai mulai dari baris ke-2.',
        'zip_failed'           => 'Gagal membuat arsip ZIP.',
    ],
];
