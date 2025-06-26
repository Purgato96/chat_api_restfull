<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MessageApiController;
use App\Http\Controllers\Api\RoomApiController;
use App\Http\Controllers\Api\WebSocketAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Rotas públicas (sem autenticação)
Route::prefix('v1')->group(function () {

    // Autenticação
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/register', [AuthController::class, 'register']);

    // Salas públicas (apenas listagem e visualização)
    Route::get('/rooms', [RoomApiController::class, 'index']);
    Route::get('/rooms/{room}', [RoomApiController::class, 'show']);
    Route::get('/rooms/{room}/members', [RoomApiController::class, 'members']);
    Route::get('/rooms/{room}/messages', [MessageApiController::class, 'index']);
    Route::get('/rooms/{room}/messages/search', [MessageApiController::class, 'search']);
    Route::get('/messages/{message}', [MessageApiController::class, 'show']);
});

// Rotas protegidas (requer autenticação)
Route::prefix('v1')->middleware(['auth:sanctum', 'throttle:api'])->group(function () {

    // Autenticação
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/logout-all', [AuthController::class, 'logoutAll']);
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Salas - CRUD completo
    Route::apiResource('rooms', RoomApiController::class);
    Route::post('/rooms/{room}/join', [RoomApiController::class, 'join']);
    Route::delete('/rooms/{room}/leave', [RoomApiController::class, 'leave']);
    Route::get('/rooms/{room}/members', [RoomApiController::class, 'members']);

    // Mensagens - CRUD completo
    Route::apiResource('rooms.messages', MessageApiController::class, [
        'except' => ['index', 'show'] // Já definidos nas rotas públicas
    ]);
    Route::get('/rooms/{room}/messages/search', [MessageApiController::class, 'search']);

    // Rotas diretas para mensagens (sem precisar da sala)
    Route::put('/messages/{message}', [MessageApiController::class, 'update']);
    Route::delete('/messages/{message}', [MessageApiController::class, 'destroy']);
});

// Rota para autenticação do broadcasting (WebSocket)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/broadcasting/auth', function (Request $request) {
        return response()->json([
            'auth' => auth()->user() ? auth()->user()->id : null,
        ]);
    });
});

// Rotas de autenticação WebSocket para clientes externos
Route::prefix('v1')->group(function () {
    Route::post('/websocket/auth', [WebSocketAuthController::class, 'authenticate']);
    Route::get('/websocket/channels', [WebSocketAuthController::class, 'channels']);
    Route::get('/websocket/test', [WebSocketAuthController::class, 'test']);
});

// Rota de status da API
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

// Fallback para rotas não encontradas
Route::fallback(function () {
    return response()->json([
        'error' => 'Endpoint não encontrado',
        'message' => 'A rota solicitada não existe. Consulte a documentação da API.',
        'documentation' => '/api/v1/status'
    ], 404);
});

