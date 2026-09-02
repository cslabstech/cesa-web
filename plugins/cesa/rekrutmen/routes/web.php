<?php

use Cesa\Rekrutmen\Http\Controllers\JobApplicationAttachmentDownloadController;
use Cesa\Rekrutmen\Http\Controllers\RekrutmenSpaController;
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

    Route::get('man-power/progress', PublicRequestManPowerProgressPage::class)
        ->name('rekrutmen.public.request-man-power.progress.lookup');

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

Route::middleware(['web', 'auth'])->group(function () {
    // API endpoints consumed by Vue SPA
    Route::prefix('rekrutmen/api')->group(function () {
        Route::get('dashboard', [RekrutmenSpaController::class, 'dashboard'])->name('rekrutmen.api.dashboard');
        Route::get('requests', [RekrutmenSpaController::class, 'getRequests'])->name('rekrutmen.api.requests');
        Route::post('requests/{id}/approve', [RekrutmenSpaController::class, 'approveRequest'])->name('rekrutmen.api.requests.approve');
        Route::post('requests/{id}/reject', [RekrutmenSpaController::class, 'rejectRequest'])->name('rekrutmen.api.requests.reject');
        Route::post('requests/{id}/hold', [RekrutmenSpaController::class, 'holdRequest'])->name('rekrutmen.api.requests.hold');

        Route::get('job-postings', [RekrutmenSpaController::class, 'getJobPostings'])->name('rekrutmen.api.job-postings');
        Route::patch('job-postings/{id}/publish', [RekrutmenSpaController::class, 'togglePublishJobPosting'])->name('rekrutmen.api.job-postings.publish');
        Route::put('job-postings/{id}', [RekrutmenSpaController::class, 'updateJobPosting'])->name('rekrutmen.api.job-postings.update');
        Route::post('job-postings/{id}', [RekrutmenSpaController::class, 'updateJobPosting'])->name('rekrutmen.api.job-postings.update-post');

        Route::get('applications', [RekrutmenSpaController::class, 'getApplications'])->name('rekrutmen.api.applications');
        Route::get('applications/{id}/cv', [RekrutmenSpaController::class, 'viewCv'])->name('rekrutmen.api.applications.cv');
        Route::post('applications/{id}/upload-cv', [RekrutmenSpaController::class, 'uploadCv'])->name('rekrutmen.api.applications.upload-cv');
        Route::get('applications/{id}/photo', [RekrutmenSpaController::class, 'viewPhoto'])->name('rekrutmen.api.applications.photo');
        Route::patch('applications/{id}/stage', [RekrutmenSpaController::class, 'updateApplicationStage'])->name('rekrutmen.api.applications.stage');
        Route::post('applications/{id}/analyze-ai', [RekrutmenSpaController::class, 'analyzeWithAi'])->name('rekrutmen.api.applications.analyze-ai');
        Route::post('applications/batch-analyze-ai', [RekrutmenSpaController::class, 'batchAnalyzeWithAi'])->name('rekrutmen.api.applications.batch-analyze-ai');
        Route::get('progress-report', [RekrutmenSpaController::class, 'getProgressReport'])->name('rekrutmen.api.progress-report');
        Route::get('progress-report/export', [RekrutmenSpaController::class, 'exportProgressReport'])->name('rekrutmen.api.progress-report.export');
        Route::get('configurations', [RekrutmenSpaController::class, 'getConfigurations'])->name('rekrutmen.api.configurations');
        Route::get('settings/ai', [RekrutmenSpaController::class, 'getAiSettings'])->name('rekrutmen.api.settings.ai');
        Route::post('settings/ai', [RekrutmenSpaController::class, 'saveAiSettings'])->name('rekrutmen.api.settings.ai.save');
        Route::post('settings/ai/test', [RekrutmenSpaController::class, 'testAiConnection'])->name('rekrutmen.api.settings.ai.test');
        Route::get('settings/mail-templates', [RekrutmenSpaController::class, 'getMailTemplates'])->name('rekrutmen.api.settings.mail-templates');
        Route::post('settings/mail-templates', [RekrutmenSpaController::class, 'saveMailTemplates'])->name('rekrutmen.api.settings.mail-templates.save');
        Route::post('applications/{id}/send-email', [RekrutmenSpaController::class, 'sendCandidateEmail'])->name('rekrutmen.api.applications.send-email');
        Route::post('applications/{id}/send-notification', [RekrutmenSpaController::class, 'sendCandidateEmail'])->name('rekrutmen.api.applications.send-notification');
        Route::post('applications/bulk-send-notification', [RekrutmenSpaController::class, 'bulkSendCandidateNotification'])->name('rekrutmen.api.applications.bulk-send-notification');
        Route::post('notifications/heartbeat', [RekrutmenSpaController::class, 'heartbeatScheduled'])->name('rekrutmen.api.notifications.heartbeat');
    });

    // Native Admin Panel URLs taken over by Vue SPA:
    Route::get('admin/request-man-powers{any?}', [RekrutmenSpaController::class, 'index'])->where('any', '.*');
    Route::get('admin/job-postings{any?}', [RekrutmenSpaController::class, 'index'])->where('any', '.*');
    Route::get('admin/job-applications{any?}', [RekrutmenSpaController::class, 'index'])->where('any', '.*');
    Route::get('admin/recruitment-progress{any?}', [RekrutmenSpaController::class, 'index'])->where('any', '.*');
    Route::get('admin/configurations{any?}', [RekrutmenSpaController::class, 'index'])->where('any', '.*');
    Route::get('admin/rekrutmen{any?}', [RekrutmenSpaController::class, 'index'])->where('any', '.*');

    // SPA wildcard fallback (excludes api/ paths to avoid conflicts with POST API routes)
    Route::get('rekrutmen/{any?}', [RekrutmenSpaController::class, 'index'])
        ->where('any', '^(?!api/).*')
        ->name('rekrutmen.spa');
});
