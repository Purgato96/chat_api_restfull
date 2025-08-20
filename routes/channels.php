<?php
Broadcast::channel('room.{slug}', function ($user, $slug) {
    return true;
});

Broadcast::channel('room.{slug}.presence', function ($user, $slug) {
    return ['id' => $user->id];
});

Broadcast::channel('room.{roomId}', function ($user, $roomId) {
    return true;
});

Broadcast::channel('room.{roomId}.presence', function ($user, $roomId) {
    return ['id' => $user->id];
});
