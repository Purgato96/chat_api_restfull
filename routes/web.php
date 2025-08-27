<?php

use App\Http\Controllers\Api\PrivateConversationController;
use App\Http\Controllers\Api\PrivateMessageController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\Api\RoomApiController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Aqui definimos as rotas de frontend (via Inertia) e as rotas de chat
| público e privado. As rotas de API (stateless) continuam em routes/api.php.
|
*/

// Página inicial
Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

// Dashboard protegido (via Jetstream)
Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Rota de login para o chat, com middleware customizado
Route::get('/chat/login', function () {
    // Middleware cuida do login via iframe
})->middleware('chatrace.login');

// Rotas protegidas de chat via web (Inertia)
Route::middleware(['web', 'auth:sanctum', 'verified'])
    ->prefix('chat')
    ->group(function () {
        // Salas públicas
        Route::get('/', [RoomController::class, 'index'])->name('rooms.index');
        Route::get('/list', [RoomController::class, 'list'])->name('rooms.list');
        Route::post('/rooms', [RoomController::class, 'store'])->name('rooms.store');
        Route::get('/rooms/{room}', [RoomController::class, 'show'])->name('rooms.show');
        Route::post('/rooms/{room}/join', [RoomController::class, 'join'])->name('rooms.join');
        Route::delete('/rooms/{room}/leave', [RoomController::class, 'leave'])->name('rooms.leave');
        Route::post('/rooms/{room}/users', [RoomController::class, 'addUser'])->name('rooms.addUser');
        Route::post('/rooms/{room}/users/email', [RoomController::class, 'addUserByEmail'])->name('rooms.addUserByEmail');
        Route::delete('/rooms/{room}/users/{userId}', [RoomController::class, 'removeUser'])->name('rooms.removeUser');
        Route::get('/rooms/{room}/available-users', [RoomController::class, 'getAvailableUsers'])->name('rooms.availableUsers');

        // Mensagens públicas
        Route::post('/rooms/{room}/messages', [MessageController::class, 'store'])->name('messages.store');
        Route::put('/messages/{message}', [MessageController::class, 'update'])->name('messages.update');
        Route::delete('/messages/{message}', [MessageController::class, 'destroy'])->name('messages.destroy');

        // Conversas privadas
        Route::get('/private-conversations', [PrivateConversationController::class, 'index'])->name('privateConversations.index');
        Route::post('/private-conversations', [PrivateConversationController::class, 'start'])->name('privateConversations.start');
        Route::get('/private-conversations/{conversation}', [PrivateConversationController::class, 'show'])->name('privateConversations.show');
        Route::post('/private-conversations/{conversation}/messages', [PrivateMessageController::class, 'store'])->name('privateMessages.store');
        Route::put('/private-conversations/{conversation}/messages/{message}', [PrivateMessageController::class, 'update'])->name('privateMessages.update');
        Route::post('/private-conversations/{conversation}/messages/{message}/read', [PrivateMessageController::class, 'markAsRead'])->name('privateMessages.read');
    });

// Endpoint adicional via web para listar salas privadas (se precisar)
Route::prefix('api/v1')
    ->middleware(['web', 'auth:sanctum'])
    ->group(function () {
        Route::get('/rooms/private/all', [RoomApiController::class, 'myPrivateRooms'])
            ->name('api.myPrivateRooms');
    });

// Outras rotas do sistema
require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';

// Broadcasting via sessão web
use Illuminate\Support\Facades\Broadcast;
Broadcast::routes(['middleware' => ['auth']]);
