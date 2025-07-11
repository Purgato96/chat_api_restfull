<?php

/**
 * Evento broadcast quando uma mensagem é
 * enviada em uma sala. Utilizado para
 * atualizar clientes conectados em tempo real.
 */

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;  // ou use Channel se público
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use SerializesModels;

    public $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('room.' . $this->message->room->slug);
    }

    public function broadcastAs()
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'content' => $this->message->content,
            'room_id' => $this->message->room_id,
            'created_at' => $this->message->created_at,
            'edited_at' => $this->message->edited_at,
            'user' => [
                'id' => $this->message->user->id,
                'name' => $this->message->user->name,
            ],
        ];
    }
}
