<?php

use Cesa\Lead\Livewire\PublicLeadForm;
use Cesa\Lead\Livewire\PublicLeadProgressPage;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])->group(function (): void {
    Route::redirect('leads', 'lead', 301);

    Route::get('lead', PublicLeadForm::class)
        ->name('lead.public.form');

    Route::get('lead/{lead}', PublicLeadProgressPage::class)
        ->middleware('signed')
        ->name('lead.public.show');
});
