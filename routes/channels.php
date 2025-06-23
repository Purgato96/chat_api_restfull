<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// Canal de usuário individual
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Canal privado de sala (para usuários autenticados via web)
Broadcast::channel('room.{roomId}', function ($user, $roomId) {
    // Verifica se o usuário tem acesso à sala
    return $user->rooms()->where('room_id', $roomId)->exists();
});

// Canal privado de sala (para clientes externos via API)
Broadcast::channel('private-room.{roomId}', function ($user, $roomId) {
    $room = \App\Models\Room::find($roomId);

    if (!$room) {
        return false;
    }

    // Se a sala é privada, verifica se o usuário é membro
    if ($room->is_private) {
        return $room->users()->where('user_id', $user->id)->exists();
    }

    // Salas públicas permitem acesso a qualquer usuário autenticado
    return true;
});

// Canal de presença para mostrar usuários online na sala
Broadcast::channel('presence-room.{roomId}', function ($user, $roomId) {
    $room = \App\Models\Room::find($roomId);

    if (!$room) {
        return false;
    }

    // Verifica acesso à sala
    if ($room->is_private && !$room->users()->where('user_id', $user->id)->exists()) {
        return false;
    }

    return [
        'id' => $user->id,
        'name' => $user->name,
    ];
});

// Canais públicos não precisam de autorização (definidos no evento)
