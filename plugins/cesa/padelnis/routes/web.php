<?php

use Cesa\Padelnis\Livewire\PublicReservationForm;
use Cesa\Padelnis\Livewire\PublicReservationSuccessPage;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])->group(function (): void {
    Route::get('padelnis', PublicReservationForm::class)
        ->name('padelnis.public.form');

    Route::get('padelnis/reservasi/{idReff}', PublicReservationSuccessPage::class)
        ->middleware('signed')
        ->name('padelnis.public.success');
});
