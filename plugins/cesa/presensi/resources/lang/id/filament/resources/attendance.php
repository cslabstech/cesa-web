<?php

return [
    'navigation' => [
        'label' => 'Presensi',
    ],
    'model' => [
        'singular' => 'Presensi',
        'plural'   => 'Presensi',
    ],
    'form' => [
        'sections' => [
            'attendance_info'                => 'Informasi Kehadiran',
            'attendance_info_description'    => 'Data utama presensi pegawai.',
            'start_time'                     => 'Waktu Mulai',
            'end_time'                       => 'Waktu Selesai',
            'schedule_reference'             => 'Referensi Jadwal',
            'schedule_reference_description' => 'Jadwal yang seharusnya diikuti.',
        ],
        'fields' => [
            'user_id'             => 'Pegawai',
            'is_leave'            => 'Izin/Cuti',
            'schedule_latitude'   => 'Latitude Jadwal',
            'schedule_longitude'  => 'Longitude Jadwal',
            'schedule_start_time' => 'Waktu Mulai Jadwal',
            'schedule_end_time'   => 'Waktu Selesai Jadwal',
            'start_time'          => 'Waktu Absen Masuk',
            'start_latitude'      => 'Latitude Absen Masuk',
            'start_longitude'     => 'Longitude Absen Masuk',
            'start_photo_path'    => 'Foto Absen Masuk',
            'end_time'            => 'Waktu Absen Pulang',
            'end_latitude'        => 'Latitude Absen Pulang',
            'end_longitude'       => 'Longitude Absen Pulang',
            'end_photo_path'      => 'Foto Absen Pulang',
        ],
    ],
    'table' => [
        'columns' => [
            'attendance_date'   => 'Tanggal Presensi',
            'user'              => 'Pegawai',
            'attendance_status' => 'Status Presensi',
            'flags'             => 'Flag',
            'start_time'        => 'Waktu Datang',
            'end_time'          => 'Waktu Pulang',
        ],
        'placeholders' => [
            'end_time' => '-',
            'pending'  => 'Menunggu',
        ],
        'statuses' => [
            'open'     => 'Terbuka',
            'closed'   => 'Selesai',
            'on_leave' => 'Izin/Cuti',
        ],
        'description' => [
            'work_duration' => 'Durasi: :value',
        ],
        'flags' => [
            'none'        => 'Tidak Ada',
            'late'        => 'Terlambat',
            'early_leave' => 'Pulang Lebih Awal',
        ],
    ],
];
