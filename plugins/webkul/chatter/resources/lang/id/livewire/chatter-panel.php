<?php

return [
    'heading'      => 'Obrolan',
    'placeholders' => [
        'no-record-found' => 'Tidak ada catatan yang ditemukan.',
        'loading'         => 'Memuat Obrolan...',
    ],
    'activity-infolist' => [
        'title' => 'Kegiatan',
    ],
    'cancel-activity-plan-action' => [
        'title' => 'Batalkan Aktivitas',
    ],
    'delete-message-action' => [
        'title' => 'Hapus Pesan',
    ],
    'edit-activity' => [
        'title' => 'Sunting Aktivitas',
        'form'  => [
            'fields' => [
                'activity-plan' => 'Rencana Aktivitas',
                'plan-date'     => 'Tanggal Rencana',
                'plan-summary'  => 'Ringkasan Rencana',
                'activity-type' => 'Jenis Aktivitas',
                'due-date'      => 'Batas Waktu',
                'summary'       => 'Ringkasan',
                'assigned-to'   => 'Ditugaskan Kepada',
            ],
        ],
        'action' => [
            'notification' => [
                'success' => [
                    'title' => 'Aktivitas diperbarui',
                    'body'  => 'Aktivitas telah berhasil diperbarui.',
                ],
            ],
        ],
    ],
    'process-message' => [
        'original-note' => '<br><div><span class="font-bold">Catatan Asli</span>: :body</div>',
        'feedback'      => '<div><span class="font-bold">Umpan Balik</span>: <p>:feedback</p></div>',
    ],
    'mark-as-done' => [
        'title' => 'Tandai sebagai selesai',
        'form'  => [
            'fields' => [
                'feedback' => 'Masukan',
            ],
        ],
        'footer-actions' => [
            'label'   => 'Selesai & Jadwalkan Selanjutnya',
            'actions' => [
                'notification' => [
                    'mark-as-done' => [
                        'title' => 'Tandai aktivitas sebagai selesai',
                        'body'  => 'Aktivitas ditandai sebagai selesai dengan sukses.',
                    ],
                ],
            ],
        ],
    ],
];
