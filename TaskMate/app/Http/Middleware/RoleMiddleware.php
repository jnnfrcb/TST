<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

// Dipakai setelah auth:api. Contoh: ->middleware('role:admin') atau 'role:user,admin'
class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            return ApiResponse::error('Belum terautentikasi', 401);
        }

        if (!in_array($user->role, $roles, true)) {
            return ApiResponse::error('Tidak memiliki izin untuk mengakses resource ini', 403);
        }

        return $next($request);
    }
}