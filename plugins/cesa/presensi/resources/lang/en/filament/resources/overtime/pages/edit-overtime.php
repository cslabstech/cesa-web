<?php

return [
    'validation' => [
        'before_date'                 => 'Approval is not allowed before the overtime date.',
        'user_on_leave'               => 'The user is on leave on the selected date.',
        'attendance_missing'          => 'Attendance is not available for the selected date.',
        'schedule_missing'            => 'The user does not have a work schedule yet.',
        'invalid_overtime_time'       => 'Overtime time is invalid.',
        'end_before_start'            => 'Overtime must end after it starts.',
        'invalid_shift_time'          => 'Shift time is invalid.',
        'outside_working_hours'       => 'Overtime must be outside working hours.',
        'invalid_attendance_start'    => 'Attendance check-in time is invalid.',
        'start_after_attendance'      => 'Overtime start time must be before or equal to the attendance check-in time.',
        'invalid_attendance_end'      => 'Attendance check-out time is invalid.',
        'end_after_attendance'        => 'Overtime exceeds the attendance check-out time.',
        'overlapping_request'         => 'The overtime request overlaps with another record.',
    ],
];
