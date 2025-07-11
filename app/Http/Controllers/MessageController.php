<?php

/**
 * Controlador web de mensagens utilizado
 * nas rotas Inertia. Emite eventos de
 * broadcast ao criar ou atualizar mensagens.
 */

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Events\MessageSent;

class MessageController extends Controller
{
    public function store(Request $request, Room $room)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $message = $room->messages()->create([
            'content' => $validated['content'],
            'user_id' => auth()->id(),
        ]);

        $message->load('user');

        broadcast(new MessageSent($message))->toOthers();

        // 🚨 ESSA PARTE É A CHAVE:
        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'message' => $message,
            ]);
        }

        // fallback pra requisição Inertia (form tradicional)
        return redirect()->back();
    }

    public function update(Request $request, Message $message)
    {
        $this->authorize('update', $message);

        $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $message->update([
            'content' => $request->content,
            'edited_at' => now(),
        ]);

        broadcast(new MessageSent($message))->toOthers();

        return response()->json(['message' => $message]);
    }

    public function destroy(Message $message)
    {
        $this->authorize('delete', $message);

        $message->delete();

        broadcast(new \App\Events\MessageDeleted($message->id))->toOthers();

        return response()->json(['deleted' => true]);
    }
}
