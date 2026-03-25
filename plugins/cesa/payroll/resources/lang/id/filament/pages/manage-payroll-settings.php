<?php

return [
    'navigation' => [
        'label' => 'Denda & Gaji',
    ],
    'sections' => [
        'wage_settings'            => 'Pengaturan Gaji',
        'late_penalty_settings'    => 'Pengaturan Denda Keterlambatan',
        'late_penalty_description' => 'Konfigurasikan denda untuk keterlambatan.',
    ],
    'fields' => [
        'daily_wage'                  => 'Gaji Harian',
        'overtime_hourly_rate'        => 'Tarif Per Jam Lembur',
        'late_penalty_tier_1_min'     => 'Tier 1 Menit Minimum',
        'late_penalty_tier_1_amount'  => 'Tier 1 Nominal Denda',
        'late_penalty_tier_2_min'     => 'Tier 2 Menit Minimum',
        'late_penalty_tier_2_amount'  => 'Tier 2 Nominal Denda',
        'late_penalty_tier_3_percent' => 'Tier 3 Persentase Denda (> 30 menit)',
    ],
];
