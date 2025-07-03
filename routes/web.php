<?php

use App\Http\Controllers\MessageController;
use App\Http\Controllers\RoomController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Página inicial
Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

// Dashboard protegido
Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Rotas protegidas por autenticação
Route::middleware(array_filter([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
]))->group(function () {
    // Dashboard dentro do grupo
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    // Rotas do chat
    Route::prefix('chat')->group(function () {
        Route::get('/', [RoomController::class, 'index'])->name('rooms.index');
        Route::post('/rooms', [RoomController::class, 'store'])->name('rooms.store');
        Route::get('/rooms/{room}', [RoomController::class, 'show'])->name('rooms.show');
        Route::post('/rooms/{room}/join', [RoomController::class, 'join'])->name('rooms.join');
        Route::delete('/rooms/{room}/leave', [RoomController::class, 'leave'])->name('rooms.leave');
        Route::post('/rooms/{room}/users', [RoomController::class, 'addUser'])->name('rooms.addUser');
        Route::post('/rooms/{room}/users/email', [RoomController::class, 'addUserByEmail'])->name('rooms.addUserByEmail');
        Route::delete('/rooms/{room}/users/{userId}', [RoomController::class, 'removeUser'])->name('rooms.removeUser');
        Route::get('/rooms/{room}/available-users', [RoomController::class, 'getAvailableUsers'])->name('rooms.availableUsers');
        Route::post('/rooms/{room}/messages', [MessageController::class, 'store'])->name('messages.store');
        Route::put('/messages/{message}', [MessageController::class, 'update'])->name('messages.update');
        Route::delete('/messages/{message}', [MessageController::class, 'destroy'])->name('messages.destroy');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

use Illuminate\Support\Facades\Broadcast;

// ✅ Esta é a rota de broadcasting padrão com sessão web
Broadcast::routes(['middleware' => ['auth:sanctum']]);
