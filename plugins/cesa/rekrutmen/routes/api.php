<?php

use Cesa\Rekrutmen\Http\Controllers\Api\CareerController;
use Cesa\Rekrutmen\Http\Controllers\Api\RecruitmentProgressController;
use Cesa\Rekrutmen\Models\JobApplicationHistory;
use Illuminate\Support\Facades\Route;

Route::middleware('api')->prefix('api')->group(function () {
    Route::get('/jobs', [CareerController::class, 'index'])->name('rekrutmen.jobs.index');
    Route::get('/jobs/{slug}', [CareerController::class, 'show'])->name('rekrutmen.jobs.detail');
    Route::post('/jobs/{slug}/apply', [CareerController::class, 'apply'])
        ->middleware('throttle:30,1')
        ->name('rekrutmen.jobs.apply');

    Route::prefix('recruitment/progress-report')->middleware([
        'auth',
        'can:viewAny,'.JobApplicationHistory::class,
    ])->group(function () {
        Route::get('/', [RecruitmentProgressController::class, 'report'])->name('rekrutmen.progress-report');
        Route::get('/timeline', [RecruitmentProgressController::class, 'timeline'])->name('rekrutmen.progress-report.timeline');
        Route::get('/overview', [RecruitmentProgressController::class, 'overview'])->name('rekrutmen.progress-report.overview');
    });
});
