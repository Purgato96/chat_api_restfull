<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\User;

class AutoLoginFromIframe
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            return $next($request);
        }

        if ($request->has('email') && $request->has('account_id')) {
            $email = $request->get('email');
            $accountId = $request->get('account_id');

            // Procura o usuário
            $user = User::where('email', $email)->first();

            if (!$user) {
                $user = User::create([
                    'name' => $email,
                    'email' => $email,
                    'account_id' => $accountId,
                    'password' => bcrypt(Str::random(16)),
                ]);
            } else {
                // Atualiza account_id, se necessário
                if ($user->account_id !== $accountId) {
                    $user->account_id = $accountId;
                    $user->save();
                }
            }

            Auth::login($user);
        }

        return $next($request);
    }
}
