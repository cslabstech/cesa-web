<?php

return [
    'navigation' => [
        'label' => 'Payroll Records',
    ],
    'model' => [
        'singular' => 'Payroll Record',
        'plural'   => 'Payroll Records',
    ],
    'form' => [
        'fields' => [
            'user_id'               => 'Employee',
            'payroll_period_id'     => 'Period',
            'total_attendance_days' => 'Total Attendance Days',
            'total_overtime_hours'  => 'Total Overtime Hours',
            'total_late_minutes'    => 'Total Late Minutes',
            'gross_salary'          => 'Gross Salary',
            'total_penalties'       => 'Total Penalties',
            'net_salary'            => 'Net Salary',
        ],
    ],
    'table' => [
        'columns' => [
            'employee'              => 'Employee',
            'period'                => 'Period',
            'total_attendance_days' => 'Attendance Days',
            'total_overtime_hours'  => 'Overtime Hours',
            'base_salary'           => 'Base Salary',
            'late_penalty'          => 'Late Penalty',
            'gross_salary'          => 'Gross Salary',
            'total_penalties'       => 'Total Penalties',
            'net_salary'            => 'Net Salary',
            'created_at'            => 'Created At',
        ],
        'filters' => [
            'payroll_period_id' => 'Period',
        ],
    ],
    'infolist' => [
        'sections' => [
            'record_details'      => 'Record Details',
            'financials'          => 'Financials',
            'calculation_details' => 'Calculation Details',
            'penalties_breakdown' => 'Penalties Breakdown',
        ],
        'entries' => [
            'employee'       => 'Employee',
            'period'         => 'Period',
            'attendance_days'=> 'Attendance Days',
            'overtime_hours' => 'Overtime Hours',
            'late_minutes'   => 'Late Minutes',
            'gross_salary'   => 'Gross Salary',
            'total_penalties'=> 'Total Penalties',
            'net_salary'     => 'Net Salary',
            'daily_wage'     => 'Daily Wage',
            'overtime_rate'  => 'Overtime Rate',
            'basic_salary'   => 'Basic Salary',
            'overtime_salary'=> 'Overtime Salary',
            'date'           => 'Date',
            'minutes_late'   => 'Minutes Late',
            'penalty_amount' => 'Penalty Amount',
            'late_penalties' => 'Late Penalties',
        ],
    ],
];
