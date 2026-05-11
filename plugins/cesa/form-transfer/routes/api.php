<?php

use Cesa\FormTransfer\Http\Controllers\Api\V1\PublicTransferRequestController;
use Illuminate\Support\Facades\Route;

Route::middleware('api')
    ->prefix('api/form-transfer')
    ->name('form-transfer.api.')
    ->group(function (): void {
        Route::get('afiliasi', [PublicTransferRequestController::class, 'affiliates'])
            ->name('affiliates.index');

        Route::get('transfer-requests', [PublicTransferRequestController::class, 'index'])
            ->name('transfer-requests.index');

        Route::get('transfer-requests/progress', [PublicTransferRequestController::class, 'lookupProgress'])
            ->middleware('throttle:10,1')
            ->name('transfer-requests.progress.lookup');

        Route::get('transfer-requests/progress/{response}', [PublicTransferRequestController::class, 'showProgress'])
            ->middleware('throttle:30,1')
            ->name('transfer-requests.progress.show');

        Route::get('transfer-requests/{formTransfer}', [PublicTransferRequestController::class, 'show'])
            ->name('transfer-requests.show');

        Route::post('transfer-requests/{formTransfer}', [PublicTransferRequestController::class, 'store'])
            ->middleware('throttle:5,1')
            ->name('transfer-requests.store');
    });
