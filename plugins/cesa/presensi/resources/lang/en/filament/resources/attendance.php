<?php

return [
    'navigation' => [
        'label' => 'Attendances',
    ],
    'model' => [
        'singular' => 'Attendance',
        'plural'   => 'Attendances',
    ],
    'form' => [
        'sections' => [
            'attendance_info'                => 'Attendance Information',
            'attendance_info_description'    => 'Primary attendance data for the employee.',
            'start_time'                     => 'Start Time',
            'end_time'                       => 'End Time',
            'schedule_reference'             => 'Schedule Reference',
            'schedule_reference_description' => 'The schedule the employee should follow.',
        ],
        'fields' => [
            'user_id'             => 'Employee',
            'is_leave'            => 'Is Leave',
            'schedule_latitude'   => 'Schedule Latitude',
            'schedule_longitude'  => 'Schedule Longitude',
            'schedule_start_time' => 'Schedule Start Time',
            'schedule_end_time'   => 'Schedule End Time',
            'start_time'          => 'Check-in Time',
            'start_latitude'      => 'Check-in Latitude',
            'start_longitude'     => 'Check-in Longitude',
            'start_photo_path'    => 'Check-in Photo',
            'end_time'            => 'Check-out Time',
            'end_latitude'        => 'Check-out Latitude',
            'end_longitude'       => 'Check-out Longitude',
            'end_photo_path'      => 'Check-out Photo',
        ],
    ],
    'table' => [
        'columns' => [
            'attendance_date'   => 'Attendance Date',
            'user'              => 'Employee',
            'attendance_status' => 'Attendance Status',
            'flags'             => 'Flags',
            'start_time'        => 'Check-in Time',
            'end_time'          => 'Check-out Time',
        ],
        'placeholders' => [
            'end_time' => '-',
            'pending'  => 'Pending',
        ],
        'statuses' => [
            'open'     => 'Open',
            'closed'   => 'Closed',
            'on_leave' => 'On Leave',
        ],
        'description' => [
            'work_duration' => 'Duration: :value',
        ],
        'flags' => [
            'none'        => 'None',
            'late'        => 'Late',
            'early_leave' => 'Early Leave',
        ],
    ],
];
