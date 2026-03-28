<?php

return [
    'approval' => [
        'subject'           => ':prefix Persetujuan Diperlukan - :title',
        'greeting'          => 'Halo :name,',
        'action_text'       => 'Buka Halaman Approval',
        'heading'           => 'Persetujuan Pengajuan Transfer',
        'intro'             => ':approver, persetujuan Anda dibutuhkan untuk pengajuan transfer berikut.',
        'summary_heading'   => 'Ringkasan Pengajuan',
        'approvals_heading' => 'Detail Approval',
        'progress_hint'     => 'Cek progres:',
        'progress_link'     => 'lihat status pengajuan',
        'plain'             => [
            'intro'       => 'Mohon tinjau pengajuan transfer berikut:',
            'uid'         => 'UID: :uid',
            'requester'   => 'Nama Pemohon: :requester',
            'division'    => 'Divisi: :division',
            'amount'      => 'Jumlah Transfer: Rp :amount',
            'purpose'     => 'Keperluan: :purpose',
            'status'      => 'Status saat ini: :status',
            'approvers'   => 'Daftar approver:',
            'thanks'      => 'Terima kasih.',
        ],
    ],
    'status' => [
        'subject'           => ':prefix :title - :status',
        'greeting'          => 'Halo :name,',
        'action_text'       => 'Lihat Progres Approval',
        'heading'           => 'Status Pengajuan Transfer',
        'intro'             => 'Status pengajuan transfer Anda saat ini: :status',
        'summary_heading'   => 'Ringkasan Pengajuan',
        'approvals_heading' => 'Detail Approval',
        'progress_hint'     => 'Lihat progres lengkap di sini:',
        'progress_link'     => 'cek status pengajuan',
        'plain'             => [
            'intro'     => 'Status pengajuan transfer Anda diperbarui menjadi: :status',
            'uid'       => 'UID: :uid',
            'division'  => 'Divisi: :division',
            'amount'    => 'Jumlah Transfer: Rp :amount',
        ],
    ],
];
