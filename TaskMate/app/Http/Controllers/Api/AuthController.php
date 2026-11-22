<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Throwable;

class AuthController extends Controller
{
    // POST /api/register  (publik)
    public function register(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:users,email',
                'password' => 'required|string|min:8|max:255',
            ]);
            if ($validator->fails()) {
                return ApiResponse::error('Validasi gagal', 422, ['errors' => $validator->errors()]);
            }

            // Role SELALU 'user'. Jangan pernah ambil role dari input client.
            $user = User::create([
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'password' => $request->input('password'),
                'role' => 'user',
            ]);

            $token = Auth::guard('api')->login($user);

            return ApiResponse::success('Registrasi berhasil', $this->tokenData($token, $user), 201);
        } catch (Throwable $e) {
            return $this->serverError($e);
        }
    }

    // POST /api/login  (publik)
    public function login(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string',
            ]);
            if ($validator->fails()) {
                return ApiResponse::error('Validasi gagal', 422, ['errors' => $validator->errors()]);
            }

            $token = Auth::guard('api')->attempt($validator->validated());
            if (!$token) {
                return ApiResponse::error('Email atau password salah', 401);
            }

            return ApiResponse::success('Login berhasil', $this->tokenData($token, Auth::guard('api')->user()));
        } catch (Throwable $e) {
            return $this->serverError($e);
        }
    }

    // GET /api/me  (perlu token)
    public function me(): JsonResponse
    {
        try {
            $user = Auth::guard('api')->user();
            return ApiResponse::success('Data user berhasil ditampilkan', $this->userData($user));
        } catch (Throwable $e) {
            return $this->serverError($e);
        }
    }

    // POST /api/logout  (perlu token) -> token dimasukkan blacklist
    public function logout(): JsonResponse
    {
        try {
            Auth::guard('api')->logout();
            return ApiResponse::success('Logout berhasil');
        } catch (Throwable $e) {
            return $this->serverError($e);
        }
    }

    private function tokenData(string $token, User $user): array
    {
        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
            'user' => $this->userData($user),
        ];
    }

    // Hanya kolom aman. Password tidak pernah dikirim.
    private function userData(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ];
    }

    private function serverError(Throwable $e): JsonResponse
    {
        Log::error('TaskMate API error', ['controller' => self::class, 'exception' => $e]);
        return ApiResponse::error('Kesalahan server. Silakan coba lagi.', 500);
    }
}