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

// routes/channels.php (adicionar)

use App\Models\PrivateConversation;

Broadcast::channel('private-conversation.{conversationId}', function ($user, $conversationId) {
    $conversation = PrivateConversation::find($conversationId);

    return $conversation &&
        ($conversation->user_one_id === $user->id || $conversation->user_two_id === $user->id);
});

Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
