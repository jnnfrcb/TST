<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MataKuliah;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Throwable;

class MataKuliahController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $data = MataKuliah::query()->select(['id', 'user_id', 'nama_mata_kuliah', 'dosen', 'semester', 'created_at', 'updated_at'])
                ->with(['user' => fn ($q) => $q->select(['id', 'name', 'email'])])
                ->orderBy('id')->get();
            $queryCount = count(DB::getQueryLog());
            return ApiResponse::success('Daftar mata-kuliah berhasil ditampilkan', $data, 200,
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
            $data = MataKuliah::query()->select(['id', 'user_id', 'nama_mata_kuliah', 'dosen', 'semester', 'created_at', 'updated_at'])
                ->with(['user' => fn ($q) => $q->select(['id', 'name', 'email'])])->find($id);
            if (!$data) return ApiResponse::error('Data mata-kuliah tidak ditemukan', 404);
            return ApiResponse::success('Detail mata-kuliah berhasil ditampilkan', $data);
        } catch (Throwable $e) {
            return $this->serverError($e);
        }
    }
    public function store(Request $request): JsonResponse
    {
        try {
        $ownerId = (int) ($request->input('user_id') ?? 0);
        $rules = [
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'nama_mata_kuliah' => 'required|string|max:255',
            'dosen' => 'required|string|max:255',
            'semester' => 'required|string|max:20',
        ];
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) return ApiResponse::error('Validasi gagal', 422, ['errors' => $validator->errors()]);
            $validated = $validator->validated();

            $data = MataKuliah::create($validated);
            return ApiResponse::success('mata-kuliah berhasil ditambahkan', $data, 201);
        } catch (Throwable $e) {
            return $this->serverError($e);
        }
    }
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $data = MataKuliah::find($id);
            if (!$data) return ApiResponse::error('Data mata-kuliah tidak ditemukan', 404);
            if ($request->exists('user_id') || $request->exists('id')) {
                return ApiResponse::error('id dan user_id tidak dapat diubah', 422);
            }
            $ownerId = (int) $data->user_id;
            $required = $request->isMethod('put') ? 'required' : 'sometimes|required';
            $rules = [
            'nama_mata_kuliah' => $required . '|string|max:255' ,
            'dosen' => $required . '|string|max:255' ,
            'semester' => $required . '|string|max:20' ,
            ];
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) return ApiResponse::error('Validasi gagal', 422, ['errors' => $validator->errors()]);
            $validated = $validator->validated();
            if ($request->isMethod('patch') && $validated === []) {
                return ApiResponse::error('PATCH memerlukan minimal satu field', 422);
            }

            $data->update($validated);
            return ApiResponse::success('mata-kuliah berhasil diperbarui', $data->fresh());
        } catch (Throwable $e) {
            return $this->serverError($e);
        }
    }
    public function destroy(int $id): JsonResponse
    {
        try {
            $data = MataKuliah::find($id);
            if (!$data) return ApiResponse::error('Data mata-kuliah tidak ditemukan', 404);
            $data->delete();
            return ApiResponse::success('mata-kuliah berhasil dihapus');
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