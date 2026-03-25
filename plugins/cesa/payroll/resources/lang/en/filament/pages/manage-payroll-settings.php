<?php

return [
    'navigation' => [
        'label' => 'Penalty & Wage',
    ],
    'sections' => [
        'wage_settings'            => 'Wage Settings',
        'late_penalty_settings'    => 'Late Penalty Settings',
        'late_penalty_description' => 'Configure penalties for late attendance.',
    ],
    'fields' => [
        'daily_wage'                  => 'Daily Wage',
        'overtime_hourly_rate'        => 'Overtime Hourly Rate',
        'late_penalty_tier_1_min'     => 'Tier 1 Minimum Minutes',
        'late_penalty_tier_1_amount'  => 'Tier 1 Penalty Amount',
        'late_penalty_tier_2_min'     => 'Tier 2 Minimum Minutes',
        'late_penalty_tier_2_amount'  => 'Tier 2 Penalty Amount',
        'late_penalty_tier_3_percent' => 'Tier 3 Penalty Percentage (> 30 mins)',
    ],
];
