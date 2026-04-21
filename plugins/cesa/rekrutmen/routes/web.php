<?php

use Cesa\Rekrutmen\Http\Controllers\JobApplicationAttachmentDownloadController;
use Cesa\Rekrutmen\Livewire\PublicRequestManPowerApprovalPage;
use Cesa\Rekrutmen\Livewire\PublicRequestManPowerForm;
use Cesa\Rekrutmen\Livewire\PublicRequestManPowerProgressPage;
use Illuminate\Http\Middleware\SetCacheHeaders;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    Route::get('man-power', PublicRequestManPowerForm::class)
        ->middleware([
            SetCacheHeaders::using([
                'no_store'        => true,
                'no_cache'        => true,
                'must_revalidate' => true,
                'max_age'         => 0,
                'private'         => true,
            ]),
            'throttle:60,1',
        ])
        ->name('rekrutmen.public.request-man-power.form');

    Route::get('man-power/progress/{response}', PublicRequestManPowerProgressPage::class)
        ->name('rekrutmen.public.request-man-power.progress');

    Route::get('man-power/approval/{token}', PublicRequestManPowerApprovalPage::class)
        ->middleware([
            SetCacheHeaders::using([
                'no_store'        => true,
                'no_cache'        => true,
                'must_revalidate' => true,
                'max_age'         => 0,
                'private'         => true,
            ]),
        ])
        ->name('rekrutmen.public.request-man-power.approval');
});

Route::middleware(['web', 'auth', 'signed'])->group(function () {
    Route::get('rekrutmen/job-applications/{jobApplication}/files/{attachment}', JobApplicationAttachmentDownloadController::class)
        ->name('rekrutmen.job-applications.attachments.download');
});
