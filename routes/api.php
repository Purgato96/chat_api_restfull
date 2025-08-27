<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MessageApiController;
use App\Http\Controllers\Api\PrivateConversationController;
use App\Http\Controllers\Api\PrivateMessageController;
use App\Http\Controllers\Api\RoomApiController;
use App\Http\Controllers\Api\WebSocketAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// =====================
// Rotas públicas (sem auth)
// =====================
Route::prefix('v1')->name('api.')->group(function () {
    // Autenticação
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/register', [AuthController::class, 'register']);

    // Salas públicas
    Route::get('/rooms', [RoomApiController::class, 'index']);
    Route::get('/rooms/{room:slug}', [RoomApiController::class, 'show']);
    Route::get('/rooms/{room:slug}/members', [RoomApiController::class, 'members']);
    Route::get('/rooms/{room:slug}/messages', [MessageApiController::class, 'index']);
    Route::get('/rooms/{room:slug}/messages/search', [MessageApiController::class, 'search']);

    // Mensagem pública específica
    Route::get('/messages/{message}', [MessageApiController::class, 'show']);
});

// =====================
// Rotas protegidas (auth:sanctum)
// =====================
Route::prefix('v1')->name('api.')->middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    // Autenticação
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/logout-all', [AuthController::class, 'logoutAll']);
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Salas (ações autenticadas)
    Route::post('/rooms/{room:slug}/join', [RoomApiController::class, 'join']);
    Route::delete('/rooms/{room:slug}/leave', [RoomApiController::class, 'leave']);
    Route::get('/rooms/{room:slug}/members', [RoomApiController::class, 'members']);
    // Alias opcional para compatibilidade do front:
    Route::get('/rooms/{room:slug}/users', [RoomApiController::class, 'members']);

    // Mensagens (CRUD parcial via sala)
    Route::apiResource('rooms.messages', MessageApiController::class, [
        'except' => ['index', 'show'] // já definidos acima nas públicas
    ]);
    Route::get('/rooms/{room:slug}/messages/search', [MessageApiController::class, 'search']);

    // Rotas diretas para mensagens
    Route::put('/messages/{message}', [MessageApiController::class, 'update']);
    Route::delete('/messages/{message}', [MessageApiController::class, 'destroy']);

    // Minhas salas privadas
    Route::get('rooms/private/all', [RoomApiController::class, 'myPrivateRooms']);

    // Conversas privadas
    Route::get('/private-conversations', [PrivateConversationController::class, 'index']);
    Route::post('/private-conversations', [PrivateConversationController::class, 'start']);
    Route::get('/private-conversations/{conversation}', [PrivateConversationController::class, 'show']);

    // Mensagens privadas
    Route::post('/private-conversations/{conversation}/messages', [PrivateMessageController::class, 'store']);
    Route::put('/private-conversations/{conversation}/messages/{message}', [PrivateMessageController::class, 'update']);
    Route::post('/private-conversations/{conversation}/messages/{message}/read', [PrivateMessageController::class, 'markAsRead']);
});

// =====================
// Broadcasting auth (Sanctum)
// =====================
Route::middleware(['auth:sanctum'])->post('/broadcasting/auth', function (Request $request) {
    return response()->json([
        'auth' => optional($request->user())->id,
    ]);
});

// =====================
// WebSocket endpoints auxiliares
// =====================
Route::prefix('v1')->group(function () {
    Route::post('/websocket/auth', [WebSocketAuthController::class, 'authenticate']);
    Route::get('/websocket/channels', [WebSocketAuthController::class, 'channels']);
    Route::get('/websocket/test', [WebSocketAuthController::class, 'test']);
});

// =====================
// Status + fallback
// =====================
Route::get('/v1/status', function () {
    return response()->json([
        'status' => 'online',
        'version' => '1.0.0',
        'timestamp' => now()->toISOString(),
        'endpoints' => [
            'auth' => '/api/v1/auth/*',
            'rooms' => '/api/v1/rooms',
            'messages' => '/api/v1/rooms/{room}/messages',
            'websocket' => config('broadcasting.connections.pusher.options.host'),
        ]
    ]);
});

Route::get('/v1/test', function (Request $request) {
    return response()->json([
        'auth_guard' => config('auth.defaults.guard'),
        'user' => $request->user(),
    ]);
});

Route::fallback(function () {
    return response()->json([
        'error' => 'Endpoint não encontrado',
        'message' => 'A rota solicitada não existe. Consulte a documentação da API.',
        'documentation' => '/api/v1/status'
    ], 404);
});
