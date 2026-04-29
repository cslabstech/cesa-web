<?php

return [
    'title'   => 'Activity Notes',
    'form'    => [
        'fields' => [
            'activity_date' => 'Activity Date',
            'notes'         => 'Notes',
        ],
    ],
    'columns' => [
        'activity'      => 'Activity',
        'result'        => 'Result',
        'from_stage'    => 'From Stage',
        'to_stage'      => 'To Stage',
        'status'        => 'Status',
        'notes'         => 'Notes',
        'performed_by'  => 'Performed By',
        'activity_date' => 'Activity Date',
        'recorded_at'   => 'Recorded At',
    ],
    'actions' => [
        'edit' => [
            'label'   => 'Edit Date',
            'heading' => 'Edit History Date',
        ],
    ],
    'notifications' => [
        'updated' => 'Activity history date has been updated.',
    ],
    'placeholders' => [
        'activity'   => 'Manual activity',
        'result'     => '-',
        'from_stage' => 'Start',
        'to_stage'   => 'N/A',
    ],
];
