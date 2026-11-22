<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

// Untuk role user, user_id pada POST SELALU diganti dengan id pemilik token.
// Jadi user tidak bisa membuat data atas nama user lain. Admin boleh mengirim user_id sendiri.
class ForceOwnerId
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('api')->user();

        if ($user && $user->role !== 'admin' && $request->isMethod('post')) {
            $request->merge(['user_id' => $user->id]);
        }

        return $next($request);
    }
}