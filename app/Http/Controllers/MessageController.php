<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Message;
use App\Models\Room;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function store(Request $request, Room $room)
    {
        // Verifica se o usuário tem acesso à sala
        if (!$room->users()->where('user_id', auth()->id())->exists()) {
            abort(403, 'Você não tem acesso a esta sala.');
        }

        $validated = $request->validate([
            'content' => 'required|string|max:2000',
        ]);

        $message = Message::create([
            'content' => $validated['content'],
            'user_id' => auth()->id(),
            'room_id' => $room->id,
        ]);

        $message->load('user');

        // Dispara o evento de broadcasting
        broadcast(new MessageSent($message))->toOthers();

        return response()->json([
            'message' => $message,
        ]);
    }

    public function update(Request $request, Message $message)
    {
        // Verifica se o usuário é o autor da mensagem
        if ($message->user_id !== auth()->id()) {
            abort(403, 'Você só pode editar suas próprias mensagens.');
        }

        $validated = $request->validate([
            'content' => 'required|string|max:2000',
        ]);

        $message->update([
            'content' => $validated['content'],
            'edited_at' => now(),
        ]);

        return response()->json([
            'message' => $message->fresh('user'),
        ]);
    }

    public function destroy(Message $message)
    {
        // Verifica se o usuário é o autor da mensagem
        if ($message->user_id !== auth()->id()) {
            abort(403, 'Você só pode deletar suas próprias mensagens.');
        }

        $message->delete();

        return response()->json([
            'success' => true,
        ]);
    }
}
