<?php

return [
    'navigation' => [
        'label' => 'Manpower Requests',
    ],
    'model' => [
        'singular' => 'Manpower Request',
        'plural'   => 'Manpower Requests',
    ],
    'form' => [
        'sections' => [
            'applicant_information' => 'Requester Information',
            'requirement_details'   => 'Requirement Details',
            'qualifications'        => 'Qualifications & Job Description',
            'approval_status'       => 'Approval Status',
        ],
        'fields' => [
            'nama_pengaju'               => 'Requester Name',
            'posisi_pengaju'             => 'Requester Position',
            'email_address'              => 'Requester Email',
            'tanggal_pengajuan'          => 'Submission Date',
            'divisi'                     => 'Division',
            'badan_usaha'                => 'Business Entity',
            'posisi_dibutuhkan'          => 'Requested Position',
            'lokasi_penempatan'          => 'Placement Location',
            'status_kebutuhan'           => 'Requirement Status',
            'level_pekerjaan'            => 'Job Level',
            'jumlah_karyawan_dibutuhkan' => 'Number of Employees Needed',
            'estimasi_tanggal_join'      => 'Estimated Join Date',
            'nama_karyawan_replacement'  => 'Employee to Be Replaced',
            'requirements_kualifikasi'   => 'Required Qualifications',
            'job_description'            => 'Job Description',
            'keterangan'                 => 'Additional Notes',
            'status'                     => 'Approval Status',
            'approved_by'                => 'Approved By',
        ],
        'helper_texts' => [
            'nama_karyawan_replacement' => 'For replacement requests, enter the name of the employee being replaced.',
        ],
    ],
    'table' => [
        'columns' => [
            'nama_pengaju'               => 'Requester Name',
            'posisi_dibutuhkan'          => 'Requested Position',
            'divisi'                     => 'Division',
            'status_kebutuhan'           => 'Requirement Status',
            'nama_karyawan_replacement'  => 'Replacement Employee',
            'jumlah_karyawan_dibutuhkan' => 'Quantity',
            'tanggal_pengajuan'          => 'Submission Date',
            'status'                     => 'Approval Status',
        ],
        'placeholders' => [
            'nama_karyawan_replacement' => '-',
        ],
        'filters' => [
            'status'           => 'Approval Status',
            'status_kebutuhan' => 'Requirement Status',
            'divisi'           => 'Division',
        ],
        'actions' => [
            'approve'     => 'Approve',
            'reject'      => 'Reject',
            'set_pending' => 'Set Pending',
        ],
    ],
    'errors' => [
        'default_pipeline_not_configured' => 'A default recruitment pipeline is not configured.',
        'approval_failed'                 => 'Unable to complete the approval action.',
    ],
];
