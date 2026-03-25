<?php

return [
    'navigation' => [
        'label' => 'Data Penggajian',
    ],
    'model' => [
        'singular' => 'Data Penggajian',
        'plural'   => 'Data Penggajian',
    ],
    'form' => [
        'fields' => [
            'user_id'               => 'Karyawan',
            'payroll_period_id'     => 'Periode',
            'total_attendance_days' => 'Total Hari Hadir',
            'total_overtime_hours'  => 'Total Jam Lembur',
            'total_late_minutes'    => 'Total Menit Terlambat',
            'gross_salary'          => 'Gaji Kotor',
            'total_penalties'       => 'Total Denda',
            'net_salary'            => 'Gaji Bersih',
        ],
    ],
    'table' => [
        'columns' => [
            'employee'              => 'Karyawan',
            'period'                => 'Periode',
            'total_attendance_days' => 'Hari Hadir',
            'total_overtime_hours'  => 'Jam Lembur',
            'base_salary'           => 'Gaji Pokok',
            'late_penalty'          => 'Denda Keterlambatan',
            'gross_salary'          => 'Gaji Kotor',
            'total_penalties'       => 'Total Denda',
            'net_salary'            => 'Gaji Bersih',
            'created_at'            => 'Dibuat Pada',
        ],
        'filters' => [
            'payroll_period_id' => 'Periode',
        ],
    ],
    'infolist' => [
        'sections' => [
            'record_details'      => 'Detail Data',
            'financials'          => 'Keuangan',
            'calculation_details' => 'Detail Perhitungan',
            'penalties_breakdown' => 'Rincian Denda',
        ],
        'entries' => [
            'employee'       => 'Karyawan',
            'period'         => 'Periode',
            'attendance_days'=> 'Hari Hadir',
            'overtime_hours' => 'Jam Lembur',
            'late_minutes'   => 'Menit Terlambat',
            'gross_salary'   => 'Gaji Kotor',
            'total_penalties'=> 'Total Denda',
            'net_salary'     => 'Gaji Bersih',
            'daily_wage'     => 'Gaji Harian',
            'overtime_rate'  => 'Tarif Lembur',
            'basic_salary'   => 'Gaji Dasar',
            'overtime_salary'=> 'Gaji Lembur',
            'date'           => 'Tanggal',
            'minutes_late'   => 'Menit Terlambat',
            'penalty_amount' => 'Jumlah Denda',
            'late_penalties' => 'Denda Keterlambatan',
        ],
    ],
];
