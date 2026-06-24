<?php

use App\Http\Controllers\CesaHomeController;
use Illuminate\Support\Facades\Route;
use Webkul\PluginManager\Package;

if (! Package::isPluginInstalled('website')) {
    Route::get('/', [CesaHomeController::class, 'index'])->name('home');
    Route::redirect('/login', '/admin/login')
        ->name('login');
}
