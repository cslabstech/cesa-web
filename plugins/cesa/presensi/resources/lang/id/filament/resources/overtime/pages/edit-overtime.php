<?php

return [
    'validation' => [
        'before_date'                 => 'Belum bisa approve sebelum tanggal lembur.',
        'user_on_leave'               => 'User sedang cuti pada tanggal tersebut.',
        'attendance_missing'          => 'Attendance belum ada untuk tanggal tersebut.',
        'schedule_missing'            => 'User belum mendapatkan jadwal kerja.',
        'invalid_overtime_time'       => 'Waktu lembur tidak valid.',
        'end_before_start'            => 'Waktu lembur harus berakhir setelah waktu mulai.',
        'invalid_shift_time'          => 'Waktu shift tidak valid.',
        'outside_working_hours'       => 'Waktu lembur harus di luar jam kerja.',
        'invalid_attendance_start'    => 'Waktu masuk presensi tidak valid.',
        'start_after_attendance'      => 'Waktu mulai lembur harus sebelum atau sama dengan waktu masuk presensi.',
        'invalid_attendance_end'      => 'Waktu pulang presensi tidak valid.',
        'end_after_attendance'        => 'Waktu lembur melebihi waktu pulang pada presensi.',
        'overlapping_request'         => 'Pengajuan lembur tumpang tindih dengan data lain.',
    ],
];
