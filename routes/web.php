<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('gym.dashboard')
        : redirect()->route('login');
})->name('home');

Route::middleware('auth')->group(function () {
    Route::redirect('dashboard', '/gym-dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/winner-gym-core.php';
require __DIR__.'/winner-gym-operations.php';
require __DIR__.'/winner-gym-final.php';
