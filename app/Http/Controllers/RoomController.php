<?php

/**
 * Controlador responsável pelas telas
 * do chat utilizando Inertia. Gere salas
 * e renderiza mensagens para usuários logados.
 */

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class RoomController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if (!$user) {
            abort(403, 'Não autenticado');
        }

        // Usa o account_id ou fallback pro ID do usuário
        $accountId = $user->account_id ?: $user->id;

        $slug = 'account-' . $accountId;

        // Cria apenas se não existir, de forma segura e atômica
        $room = \App\Models\Room::firstOrCreate(
            ['slug' => $slug],
            [
                'name' => 'Espaço #' . $accountId,
                'description' => 'Sala criada automaticamente para account_id ' . $accountId,
                'is_private' => true,
                'created_by' => $user->id,
            ]
        );

        // Garante que o usuário está na sala
        if (!$room->users()->where('user_id', $user->id)->exists()) {
            $room->users()->attach($user->id, ['joined_at' => now()]);
        }

        // Redireciona pra sala
        return redirect()->route('rooms.show', $room->slug);
    }
    /*public function index(): Response
    {
        $rooms = auth()->user()->rooms()
            ->with(['creator', 'latestMessages'])
            ->withCount('users')
            ->get();

        return Inertia::render('Chat/Index', [
            'rooms' => $rooms,
        ]);
    }*/

    public function show(Room $room): Response
    {
        // Verifica se o usuário tem acesso à sala
        if (!$room->users()->where('user_id', auth()->id())->exists()) {
            abort(403, 'Você não tem acesso a esta sala.');
        }

        $messages = $room->messages()
            ->with('user')
            ->latest()
            ->limit(50)
            ->get()
            ->reverse()
            ->values();

        return Inertia::render('Chat/Room', [
            'room' => $room->load(['users', 'creator']), // Adiciona o criador aqui
            'messages' => $messages,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_private' => 'boolean',
        ]);

        $room = Room::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']) . '-' . Str::random(6),
            'description' => $validated['description'],
            'is_private' => $validated['is_private'] ?? false,
            'created_by' => auth()->id(),
        ]);

        // Adiciona o criador à sala
        $room->users()->attach(auth()->id());

        return redirect()->route('rooms.show', $room);
    }

    public function join(Room $room)
    {
        if ($room->is_private) {
            abort(403, 'Esta sala é privada.');
        }

        if (!$room->users()->where('user_id', auth()->id())->exists()) {
            $room->users()->attach(auth()->id());
        }

        return redirect()->route('rooms.show', $room);
    }

    public function leave(Room $room)
    {
        $room->users()->detach(auth()->id());

        return redirect()->route('rooms.index');
    }

    public function addUser(Request $request, Room $room)
    {
        // Verifica se o usuário atual é o criador da sala
        if ($room->created_by !== auth()->id()) {
            abort(403, 'Apenas o criador da sala pode adicionar usuários.');
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $userId = $validated['user_id'];

        // Verifica se o usuário já está na sala
        if ($room->users()->where('user_id', $userId)->exists()) {
            return back()->withErrors(['user_id' => 'Este usuário já está na sala.']);
        }

        // Adiciona o usuário à sala
        $room->users()->attach($userId, [
            'joined_at' => now(),
        ]);

        return back()->with('success', 'Usuário adicionado à sala com sucesso!');
    }

    /**
     * Adiciona um usuário a uma sala por email
     * Apenas o criador da sala pode adicionar usuários
     */
    public function addUserByEmail(Request $request, Room $room)
    {
        // Verifica se o usuário atual é o criador da sala
        if ($room->created_by !== auth()->id()) {
            abort(403, 'Apenas o criador da sala pode adicionar usuários.');
        }

        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        // Busca o usuário pelo email
        $user = \App\Models\User::where('email', $validated['email'])->first();

        if (!$user) {
            return back()->withErrors(['email' => 'Usuário não encontrado.']);
        }

        // Verifica se o usuário já está na sala
        if ($room->users()->where('user_id', $user->id)->exists()) {
            return back()->withErrors(['email' => 'Este usuário já está na sala.']);
        }

        // Adiciona o usuário à sala
        $room->users()->attach($user->id, [
            'joined_at' => now(),
        ]);

        return back()->with('success', "Usuário {$user->name} adicionado à sala com sucesso!");
    }

    /**
     * Remove um usuário de uma sala
     * Apenas o criador da sala pode remover usuários
     */
    public function removeUser(Request $request, Room $room, $userId)
    {
        // Verifica se o usuário atual é o criador da sala
        if ($room->created_by !== auth()->id()) {
            abort(403, 'Apenas o criador da sala pode remover usuários.');
        }

        // Não permite remover o próprio criador
        if ($userId == $room->created_by) {
            return back()->withErrors(['error' => 'O criador da sala não pode ser removido.']);
        }

        // Remove o usuário da sala
        $room->users()->detach($userId);

        return back()->with('success', 'Usuário removido da sala com sucesso!');
    }

    /**
     * Lista todos os usuários disponíveis para adicionar à sala
     * (usuários que ainda não estão na sala)
     */
    public function getAvailableUsers(Room $room)
    {
        // Verifica se o usuário atual é o criador da sala
        if ($room->created_by !== auth()->id()) {
            abort(403, 'Apenas o criador da sala pode ver usuários disponíveis.');
        }

        // Busca usuários que não estão na sala
        $usersInRoom = $room->users()->pluck('users.id');
        $availableUsers = \App\Models\User::whereNotIn('id', $usersInRoom)
            ->select('id', 'name', 'email')
            ->get();

        return response()->json([
            'available_users' => $availableUsers,
        ]);
    }

}
