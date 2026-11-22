<?php
use Illuminate\Support\Facades\Route;
use App\Support\ApiResponse;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MataKuliahController;
use App\Http\Controllers\Api\KategoriController;
use App\Http\Controllers\Api\TugasController;
use App\Http\Controllers\Api\PengumpulanController;
use App\Http\Controllers\Api\ProfileController;

// ===== PUBLIK (tanpa token) =====
Route::get('/ping', fn () => ApiResponse::success('TaskMate API berjalan'));
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// ===== PRIVAT (wajib token JWT) =====
Route::middleware('auth:api')->group(function () {

    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // user dan admin: Create, Read, Update
        Route::middleware(['role:user,admin', 'owner'])->group(function () {

        Route::get('/mata-kuliah', [MataKuliahController::class, 'index']);
        Route::get('/mata-kuliah/{id}', [MataKuliahController::class, 'show'])->whereNumber('id');
        Route::post('/mata-kuliah', [MataKuliahController::class, 'store']);
        Route::match(['put', 'patch'], '/mata-kuliah/{id}', [MataKuliahController::class, 'update'])->whereNumber('id');

        Route::get('/kategori', [KategoriController::class, 'index']);
        Route::get('/kategori/{id}', [KategoriController::class, 'show'])->whereNumber('id');
        Route::post('/kategori', [KategoriController::class, 'store']);
        Route::match(['put', 'patch'], '/kategori/{id}', [KategoriController::class, 'update'])->whereNumber('id');

        Route::get('/tugas', [TugasController::class, 'index']);
        Route::get('/tugas/{id}', [TugasController::class, 'show'])->whereNumber('id');
        Route::post('/tugas', [TugasController::class, 'store']);
        Route::match(['put', 'patch'], '/tugas/{id}', [TugasController::class, 'update'])->whereNumber('id');

        Route::get('/pengumpulan', [PengumpulanController::class, 'index']);
        Route::get('/pengumpulan/{id}', [PengumpulanController::class, 'show'])->whereNumber('id');
        Route::post('/pengumpulan', [PengumpulanController::class, 'store']);
        Route::match(['put', 'patch'], '/pengumpulan/{id}', [PengumpulanController::class, 'update'])->whereNumber('id');

        Route::get('/profile', [ProfileController::class, 'index']);
        Route::get('/profile/{id}', [ProfileController::class, 'show'])->whereNumber('id');
        Route::post('/profile', [ProfileController::class, 'store']);
        Route::match(['put', 'patch'], '/profile/{id}', [ProfileController::class, 'update'])->whereNumber('id');
    });

    // khusus admin: Delete
    Route::middleware('role:admin')->group(function () {
        Route::delete('/mata-kuliah/{id}', [MataKuliahController::class, 'destroy'])->whereNumber('id');
        Route::delete('/kategori/{id}', [KategoriController::class, 'destroy'])->whereNumber('id');
        Route::delete('/tugas/{id}', [TugasController::class, 'destroy'])->whereNumber('id');
        Route::delete('/pengumpulan/{id}', [PengumpulanController::class, 'destroy'])->whereNumber('id');
        Route::delete('/profile/{id}', [ProfileController::class, 'destroy'])->whereNumber('id');
    });
});

// Khusus simulasi 500 untuk laporan lokal, bukan kegagalan server sungguhan.
if (app()->environment('local') && config('app.debug')) {
    Route::get('/_demo/server-error', function () {
        try {
            throw new \RuntimeException('Simulasi kesalahan internal lokal');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Simulasi 500 lokal', ['exception' => $e]);
            return ApiResponse::error('Kesalahan server. Silakan coba lagi.', 500);
        }
    });
}