<?php

use Cesa\Lead\Livewire\PublicLeadForm;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])->group(function (): void {
    Route::get('leads', PublicLeadForm::class)
        ->name('lead.public.form');
});
