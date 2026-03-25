<?php

return [
    'messages' => [
        'job_listed'            => 'Daftar lowongan berhasil diambil.',
        'job_not_found'         => 'Lowongan kerja tidak ditemukan.',
        'job_detail_retrieved'  => 'Detail lowongan berhasil diambil.',
        'job_not_open'          => 'Lowongan kerja tidak ditemukan atau sudah tidak dibuka.',
        'application_submitted' => 'Lamaran berhasil dikirim.',
    ],
    'validation' => [
        'messages' => [
            'full_name.required'       => 'Nama lengkap wajib diisi.',
            'email.required'           => 'Email wajib diisi.',
            'email.email'              => 'Format email tidak valid.',
            'phone.required'           => 'Nomor telepon wajib diisi.',
            'portfolio_url.url'        => 'Format URL portofolio tidak valid.',
            'resume.mimes'             => 'File CV harus berformat pdf, doc, atau docx.',
            'resume.max'               => 'Ukuran file CV maksimal 5 MB.',
            'additional_answers.array' => 'Jawaban tambahan harus berupa array.',
            'required'                 => 'Kolom :attribute wajib diisi.',
        ],
        'attributes' => [
            'full_name'     => 'nama lengkap',
            'email'         => 'email',
            'phone'         => 'nomor telepon',
            'portfolio_url' => 'URL portofolio',
            'resume'        => 'CV',
            'cover_letter'  => 'surat lamaran',
        ],
    ],
    'application' => [
        'additional_answers_prefix' => 'Jawaban Tambahan:',
        'submitted_via_public_api'  => 'Lamaran dikirim melalui API publik.',
    ],
];
