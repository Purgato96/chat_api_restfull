<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;  // ou use Channel se público
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use SerializesModels;

    public $id;
    public $content;
    public $user;
    public $room_id;
    public $created_at;
    public $edited_at;

    public function __construct(Message $message)
    {
        $this->id = $message->id;
        $this->content = $message->content;
        $this->user = $message->user;
        $this->room_id = $message->room_id;
        $this->created_at = $message->created_at;
        $this->edited_at = $message->edited_at;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('chat.' . $this->message->receiver_id);
    }

    public function broadcastAs()
    {
        return 'message.sent';
    }
}
