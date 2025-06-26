<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Room;

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

// Canal privado para cada sala de chat
Broadcast::channel('room.{roomId}', function ($user, $roomId) {
    // Verifica se o usuário tem acesso à sala
    $room = Room::find($roomId);

    if (!$room) {
        return false;
    }

    // Verifica se o usuário está na sala
    return $room->users()->where('user_id', $user->id)->exists();
});

// Canal de presença para mostrar usuários online na sala (opcional)
Broadcast::channel('room.{roomId}.presence', function ($user, $roomId) {
    $room = Room::find($roomId);

    if (!$room || !$room->users()->where('user_id', $user->id)->exists()) {
        return false;
    }

    return [
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
    ];
});


// Canais públicos não precisam de autorização (definidos no evento)
Broadcast::routes(['middleware' => ['auth']]
);
