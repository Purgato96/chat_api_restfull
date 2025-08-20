<?php

/**
 * Endpoints de autenticação da API.
 * Responsável por login, registro e
 * gerenciamento de tokens pessoais.
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login e criação de token
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['As credenciais fornecidas estão incorretas.'],
            ]);
        }

        // Revoga tokens existentes do mesmo dispositivo (opcional)
        $user->tokens()->where('name', $request->device_name)->delete();

        $token = $user->createToken($request->device_name, ['chat:read', 'chat:write']);

        return response()->json([
            'user' => $user,
            'token' => $token->plainTextToken,
            'abilities' => $token->accessToken->abilities,
        ]);
    }

    /**
     * Registro de novo usuário
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'device_name' => 'required|string',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken($request->device_name, ['chat:read', 'chat:write']);

        return response()->json([
            'user' => $user,
            'token' => $token->plainTextToken,
            'abilities' => $token->accessToken->abilities,
        ], 201);
    }

    /**
     * Logout (revoga token atual)
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Token revogado com sucesso.',
        ]);
    }

    /**
     * Logout de todos os dispositivos
     */
    public function logoutAll(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Todos os tokens foram revogados.',
        ]);
    }

    /**
     * Informações do usuário autenticado
     */
    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user(),
            'abilities' => $request->user()->currentAccessToken()->abilities,
        ]);
    }

    /**
     * Renovar token
     */
    public function refresh(Request $request)
    {
        $request->validate([
            'device_name' => 'required|string',
        ]);

        $user = $request->user();
        $currentToken = $user->currentAccessToken();

        // Revoga o token atual
        $currentToken->delete();

        // Cria um novo token
        $newToken = $user->createToken($request->device_name, ['chat:read', 'chat:write']);

        return response()->json([
            'user' => $user,
            'token' => $newToken->plainTextToken,
            'abilities' => $newToken->accessToken->abilities,
        ]);
    }
}
