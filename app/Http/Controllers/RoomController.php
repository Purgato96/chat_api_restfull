<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class RoomController extends Controller
{
    public function index(): Response
    {
        $rooms = auth()->user()->rooms()
            ->with(['creator', 'latestMessages'])
            ->get();

        return Inertia::render('Chat/Index', [
            'rooms' => $rooms,
        ]);
    }

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
            'room' => $room->load('users'),
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
}
