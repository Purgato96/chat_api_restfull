<?php

/**
 * Controller REST que gerencia as salas via API.
 * Possui endpoints para listar, criar e gerenciar
 * salas e membros utilizando autenticação Sanctum.
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoomApiController extends Controller
{
    /**
     * Lista todas as salas públicas ou salas do usuário
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Room::with(['creator:id,name', 'users:id,name'])
            ->withCount('users', 'messages');

        // Se o usuário está autenticado, mostra suas salas + públicas
        if ($user) {
            $query->where(function ($q) use ($user) {
                $q->where('is_private', false)
                    ->orWhereHas('users', function ($userQuery) use ($user) {
                        $userQuery->where('user_id', $user->id);
                    });
            });
        } else {
            // Apenas salas públicas para usuários não autenticados
            $query->where('is_private', false);
        }

        $rooms = $query->latest()->paginate(20);

        return response()->json([
            'data' => $rooms->items(),
            'meta' => [
                'current_page' => $rooms->currentPage(),
                'last_page' => $rooms->lastPage(),
                'per_page' => $rooms->perPage(),
                'total' => $rooms->total(),
            ]
        ]);
    }

    /**
     * Exibe uma sala específica
     */
    public function show(Request $request, Room $room)
    {
        $user = $request->user();

        // Verifica acesso à sala
        if ($room->is_private && (!$user || !$room->users()->where('user_id', $user->id)->exists())) {
            return response()->json([
                'error' => 'Acesso negado',
                'message' => 'Você não tem permissão para acessar esta sala.'
            ], 403);
        }

        $room->load(['creator:id,name', 'users:id,name']);
        $room->loadCount('users', 'messages');

        return response()->json([
            'data' => $room
        ]);
    }

    /**
     * Cria uma nova sala
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_private' => 'boolean',
        ]);

        $room = Room::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name) . '-' . Str::random(6),
            'description' => $request->description,
            'is_private' => $request->boolean('is_private'),
            'created_by' => $request->user()->id,
        ]);

        // Adiciona o criador à sala
        $room->users()->attach($request->user()->id);

        $room->load(['creator:id,name', 'users:id,name']);

        return response()->json([
            'data' => $room,
            'message' => 'Sala criada com sucesso.'
        ], 201);
    }

    /**
     * Atualiza uma sala (apenas o criador)
     */
    public function update(Request $request, Room $room)
    {
        if ($room->created_by !== $request->user()->id) {
            return response()->json([
                'error' => 'Acesso negado',
                'message' => 'Apenas o criador pode editar esta sala.'
            ], 403);
        }

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_private' => 'sometimes|boolean',
        ]);

        $room->update($request->only(['name', 'description', 'is_private']));

        $room->load(['creator:id,name', 'users:id,name']);

        return response()->json([
            'data' => $room,
            'message' => 'Sala atualizada com sucesso.'
        ]);
    }

    /**
     * Remove uma sala (apenas o criador)
     */
    public function destroy(Request $request, Room $room)
    {
        if ($room->created_by !== $request->user()->id) {
            return response()->json([
                'error' => 'Acesso negado',
                'message' => 'Apenas o criador pode deletar esta sala.'
            ], 403);
        }

        $room->delete();

        return response()->json([
            'message' => 'Sala deletada com sucesso.'
        ]);
    }

    /**
     * Entrar em uma sala
     */
    public function join(Request $request, Room $room)
    {
        $user = $request->user();

        if ($room->is_private) {
            return response()->json([
                'error' => 'Acesso negado',
                'message' => 'Esta sala é privada.'
            ], 403);
        }

        if (!$room->users()->where('user_id', $user->id)->exists()) {
            $room->users()->attach($user->id);
        }

        return response()->json([
            'message' => 'Você entrou na sala com sucesso.',
            'data' => [
                'room_id' => $room->id,
                'user_id' => $user->id,
                'joined_at' => now()->toISOString()
            ]
        ]);
    }

    /**
     * Sair de uma sala
     */
    public function leave(Request $request, Room $room)
    {
        $user = $request->user();

        $room->users()->detach($user->id);

        return response()->json([
            'message' => 'Você saiu da sala com sucesso.'
        ]);
    }

    /**
     * Lista membros de uma sala
     */
    public function members(Request $request, Room $room)
    {
        $user = $request->user();

        // Verifica acesso à sala
        if ($room->is_private && (!$user || !$room->users()->where('user_id', $user->id)->exists())) {
            return response()->json([
                'error' => 'Acesso negado',
                'message' => 'Você não tem permissão para ver os membros desta sala.'
            ], 403);
        }

        $members = $room->users()
            ->select('users.id', 'users.name', 'room_user.joined_at')
            ->paginate(50);

        return response()->json([
            'data' => $members->items(),
            'meta' => [
                'current_page' => $members->currentPage(),
                'last_page' => $members->lastPage(),
                'per_page' => $members->perPage(),
                'total' => $members->total(),
            ]
        ]);
    }
    /**
     * Lista todas as salas privadas do usuário autenticado
     */
    public function myPrivateRooms(Request $request)
    {
        $user = $request->user();

        $rooms = Room::where('is_private', true)
            ->whereHas('users', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->with(['users' => function($q) use ($user) {
                $q->where('user_id', '!=', $user->id)
                    ->select('users.id', 'users.name', 'users.email');
            }])
            ->get();

        return response()->json([
            'data' => $rooms
        ]);
    }
}
