<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Throwable;

class KategoriController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $data = Kategori::query()->select(['id', 'user_id', 'nama_kategori', 'created_at', 'updated_at'])
                ->with([
                    'user'  => fn ($q) => $q->select(['id', 'name', 'email']),
                    'tugas' => fn ($q) => $q->select(['id', 'kategori_id', 'judul', 'deadline', 'status']),
                ])
                ->orderBy('id')->get();
            $queryCount = count(DB::getQueryLog());
            return ApiResponse::success('Daftar kategori berhasil ditampilkan', $data, 200,
                config('app.debug') ? ['query_count' => $queryCount] : []);
        } catch (Throwable $e) {
            return $this->serverError($e);
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $data = Kategori::query()->select(['id', 'user_id', 'nama_kategori', 'created_at', 'updated_at'])
                ->with([
                    'user'  => fn ($q) => $q->select(['id', 'name', 'email']),
                    'tugas' => fn ($q) => $q->select(['id', 'kategori_id', 'judul', 'deadline', 'status']),
                ])
                ->find($id);
            if (!$data) return ApiResponse::error('Data kategori tidak ditemukan', 404);
            return ApiResponse::success('Detail kategori berhasil ditampilkan', $data);
        } catch (Throwable $e) {
            return $this->serverError($e);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $rules = [
                'user_id' => ['required', 'integer', Rule::exists('users', 'id')],
                'nama_kategori' => 'required|string|max:100',
            ];
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) return ApiResponse::error('Validasi gagal', 422, ['errors' => $validator->errors()]);
            $validated = $validator->validated();

            $data = Kategori::create($validated);
            return ApiResponse::success('kategori berhasil ditambahkan', $data, 201);
        } catch (Throwable $e) {
            return $this->serverError($e);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $data = Kategori::find($id);
            if (!$data) return ApiResponse::error('Data kategori tidak ditemukan', 404);
            if ($request->exists('user_id') || $request->exists('id')) {
                return ApiResponse::error('id dan user_id tidak dapat diubah', 422);
            }
            $required = $request->isMethod('put') ? 'required' : 'sometimes|required';
            $rules = [
                'nama_kategori' => $required . '|string|max:100',
            ];
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) return ApiResponse::error('Validasi gagal', 422, ['errors' => $validator->errors()]);
            $validated = $validator->validated();
            if ($request->isMethod('patch') && $validated === []) {
                return ApiResponse::error('PATCH memerlukan minimal satu field', 422);
            }

            $data->update($validated);
            return ApiResponse::success('kategori berhasil diperbarui', $data->fresh());
        } catch (Throwable $e) {
            return $this->serverError($e);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $data = Kategori::find($id);
            if (!$data) return ApiResponse::error('Data kategori tidak ditemukan', 404);
            $data->delete();
            return ApiResponse::success('kategori berhasil dihapus');
        } catch (Throwable $e) {
            return $this->serverError($e);
        }
    }

    private function serverError(Throwable $e): JsonResponse
    {
        Log::error('TaskMate API error', ['controller' => self::class, 'exception' => $e]);
        return ApiResponse::error('Kesalahan server. Silakan coba lagi.', 500);
    }
}