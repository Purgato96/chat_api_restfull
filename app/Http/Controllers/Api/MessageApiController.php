<?php

/**
 * Controlador da API responsável pelas
 * operações de mensagens nas salas de chat.
 * Inclui listagem, envio e busca de mensagens.
 */

namespace App\Http\Controllers\Api;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Room;
use Illuminate\Http\Request;

class MessageApiController extends Controller
{
    /**
     * Lista mensagens de uma sala
     */
    public function index(Request $request, Room $room)
    {
        $user = $request->user();

        // Verifica acesso à sala
        if ($room->is_private && (!$user || !$room->users()->where('user_id', $user->id)->exists())) {
            return response()->json([
                'error' => 'Acesso negado',
                'message' => 'Você não tem permissão para ver as mensagens desta sala.'
            ], 403);
        }

        $request->validate([
            'per_page' => 'sometimes|integer|min:1|max:100',
            'before' => 'sometimes|integer|exists:messages,id',
            'after' => 'sometimes|integer|exists:messages,id',
        ]);

        $query = $room->messages()
            ->with('user:id,name')
            ->latest();

        // Paginação baseada em cursor para melhor performance
        if ($request->has('before')) {
            $query->where('id', '<', $request->before);
        }

        if ($request->has('after')) {
            $query->where('id', '>', $request->after);
        }

        $perPage = $request->get('per_page', 50);
        $messages = $query->limit($perPage)->get();

        // Se não há filtro 'after', inverte a ordem para mostrar mais recentes primeiro
        if (!$request->has('after')) {
            $messages = $messages->reverse()->values();
        }

        return response()->json([
            'data' => $messages,
            'meta' => [
                'room_id' => $room->id,
                'count' => $messages->count(),
                'per_page' => $perPage,
                'has_more' => $messages->count() === $perPage,
            ]
        ]);
    }

    /**
     * Exibe uma mensagem específica
     */
    public function show(Request $request, Message $message)
    {
        $user = $request->user();
        $room = $message->room;

        // Verifica acesso à sala
        if ($room->is_private && (!$user || !$room->users()->where('user_id', $user->id)->exists())) {
            return response()->json([
                'error' => 'Acesso negado',
                'message' => 'Você não tem permissão para ver esta mensagem.'
            ], 403);
        }

        $message->load('user:id,name', 'room:id,name');

        return response()->json([
            'data' => $message
        ]);
    }

    /**
     * Envia uma nova mensagem
     */
    public function store(Request $request, Room $room)
    {
        $user = $request->user();

        // Verifica acesso à sala
        if (!$room->users()->where('user_id', $user->id)->exists()) {
            return response()->json([
                'error' => 'Acesso negado',
                'message' => 'Você precisa estar na sala para enviar mensagens.'
            ], 403);
        }

        $request->validate([
            'content' => 'required|string|max:2000',
        ]);

        $message = Message::create([
            'content' => $request->input('content'),
            'user_id' => $user->id,
            'room_id' => $room->id,
        ]);

        $message->load('user:id,name');

        broadcast(new MessageSent($message))->toOthers();

        return response()->json([
            'data' => $message,
            'message' => 'Mensagem enviada com sucesso.'
        ], 201);
    }

    /**
     * Atualiza uma mensagem (apenas o autor)
     */
    public function update(Request $request, Message $message)
    {
        if ($message->user_id !== $request->user()->id) {
            return response()->json([
                'error' => 'Acesso negado',
                'message' => 'Você só pode editar suas próprias mensagens.'
            ], 403);
        }

        $request->validate([
            'content' => 'required|string|max:2000',
        ]);

        $message->update([
            'content' => $request->content,
            'edited_at' => now(),
        ]);

        $message->load('user:id,name');

        return response()->json([
            'data' => $message,
            'message' => 'Mensagem atualizada com sucesso.'
        ]);
    }

    /**
     * Remove uma mensagem (apenas o autor)
     */
    public function destroy(Request $request, Message $message)
    {
        if ($message->user_id !== $request->user()->id) {
            return response()->json([
                'error' => 'Acesso negado',
                'message' => 'Você só pode deletar suas próprias mensagens.'
            ], 403);
        }

        $message->delete();

        return response()->json([
            'message' => 'Mensagem deletada com sucesso.'
        ]);
    }

    /**
     * Busca mensagens por conteúdo
     */
    public function search(Request $request, Room $room)
    {
        $user = $request->user();

        // Verifica acesso à sala
        if ($room->is_private && (!$user || !$room->users()->where('user_id', $user->id)->exists())) {
            return response()->json([
                'error' => 'Acesso negado',
                'message' => 'Você não tem permissão para buscar nesta sala.'
            ], 403);
        }

        $request->validate([
            'q' => 'required|string|min:3|max:100',
            'per_page' => 'sometimes|integer|min:1|max:50',
        ]);

        $query = $room->messages()
            ->with('user:id,name')
            ->where('content', 'LIKE', '%' . $request->q . '%')
            ->latest();

        $perPage = $request->get('per_page', 20);
        $messages = $query->paginate($perPage);

        return response()->json([
            'data' => $messages->items(),
            'meta' => [
                'query' => $request->q,
                'room_id' => $room->id,
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
            ]
        ]);
    }
}
