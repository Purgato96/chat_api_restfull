<?php

use App\Http\Controllers\MessageController;
use App\Http\Controllers\RoomController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(array_filter([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
]))->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    // Chat Routes
    Route::prefix('chat')->group(function () {
        Route::get('/', [RoomController::class, 'index'])->name('rooms.index');
        // demais rotas comentadas...
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
