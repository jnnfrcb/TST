<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tugas;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Throwable;
use App\Models\MataKuliah;
use App\Models\Kategori;

class TugasController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $data = Tugas::query()->select(['id', 'user_id', 'mata_kuliah_id', 'kategori_id', 'judul', 'deskripsi', 'deadline', 'status', 'created_at', 'updated_at'])
                ->with(['mataKuliah' => fn ($q) => $q->select(['id', 'user_id', 'nama_mata_kuliah']), 'kategori' => fn ($q) => $q->select(['id', 'user_id', 'nama_kategori']), 'user' => fn ($q) => $q->select(['id', 'name', 'email'])])
                ->orderBy('id')->get();
            $queryCount = count(DB::getQueryLog());
            return ApiResponse::success('Daftar tugas berhasil ditampilkan', $data, 200,
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
            $data = Tugas::query()->select(['id', 'user_id', 'mata_kuliah_id', 'kategori_id', 'judul', 'deskripsi', 'deadline', 'status', 'created_at', 'updated_at'])
                ->with(['mataKuliah' => fn ($q) => $q->select(['id', 'user_id', 'nama_mata_kuliah']), 'kategori' => fn ($q) => $q->select(['id', 'user_id', 'nama_kategori']), 'user' => fn ($q) => $q->select(['id', 'name', 'email'])])->find($id);
            if (!$data) return ApiResponse::error('Data tugas tidak ditemukan', 404);
            return ApiResponse::success('Detail tugas berhasil ditampilkan', $data);
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
            'mata_kuliah_id' => ['required', 'integer', Rule::exists('mata_kuliah', 'id')->where('user_id', $ownerId)],
            'kategori_id' => ['required', 'integer', Rule::exists('kategori', 'id')->where('user_id', $ownerId)],
            'judul' => 'required|string|max:255',
            'deskripsi' => 'required|string',
            'deadline' => 'required|date',
            'status' => 'required|in:belum,proses,selesai',
        ];
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) return ApiResponse::error('Validasi gagal', 422, ['errors' => $validator->errors()]);
            $validated = $validator->validated();

            if (!$this->ownerReferencesValid($validated, $ownerId)) {
                return ApiResponse::error('Mata kuliah atau kategori bukan milik user tersebut', 422);
            }

            $data = Tugas::create($validated);
            return ApiResponse::success('tugas berhasil ditambahkan', $data, 201);
        } catch (Throwable $e) {
            return $this->serverError($e);
        }
    }
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $data = Tugas::find($id);
            if (!$data) return ApiResponse::error('Data tugas tidak ditemukan', 404);
            if ($request->exists('user_id') || $request->exists('id')) {
                return ApiResponse::error('id dan user_id tidak dapat diubah', 422);
            }
            $ownerId = (int) $data->user_id;
            $required = $request->isMethod('put') ? 'required' : 'sometimes|required';
            $rules = [
            'mata_kuliah_id' => [...explode('|', $required), 'integer', Rule::exists('mata_kuliah', 'id')->where('user_id', $ownerId)],
            'kategori_id' => [...explode('|', $required), 'integer', Rule::exists('kategori', 'id')->where('user_id', $ownerId)],
            'judul' => $required . '|string|max:255' ,
            'deskripsi' => $required . '|string' ,
            'deadline' => $required . '|date' ,
            'status' => $required . '|in:belum,proses,selesai' ,
            ];
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) return ApiResponse::error('Validasi gagal', 422, ['errors' => $validator->errors()]);
            $validated = $validator->validated();
            if ($request->isMethod('patch') && $validated === []) {
                return ApiResponse::error('PATCH memerlukan minimal satu field', 422);
            }

            $merged = array_merge($data->only(['mata_kuliah_id', 'kategori_id', 'judul', 'deskripsi', 'deadline', 'status']), $validated);
            if (!$this->ownerReferencesValid($merged, $ownerId)) {
                return ApiResponse::error('Relasi tidak dimiliki user pemilik data', 422);
            }

            $data->update($validated);
            return ApiResponse::success('tugas berhasil diperbarui', $data->fresh());
        } catch (Throwable $e) {
            return $this->serverError($e);
        }
    }
    public function destroy(int $id): JsonResponse
    {
        try {
            $data = Tugas::find($id);
            if (!$data) return ApiResponse::error('Data tugas tidak ditemukan', 404);
            $data->delete();
            return ApiResponse::success('tugas berhasil dihapus');
        } catch (Throwable $e) {
            return $this->serverError($e);
        }
    }
    private function serverError(Throwable $e): JsonResponse
    {
        Log::error('TaskMate API error', ['controller' => self::class, 'exception' => $e]);
        return ApiResponse::error('Kesalahan server. Silakan coba lagi.', 500);
    }

    private function ownerReferencesValid(array $fields, int $ownerId): bool
    {
        return MataKuliah::where('id', $fields['mata_kuliah_id'])->where('user_id', $ownerId)->exists()
            && Kategori::where('id', $fields['kategori_id'])->where('user_id', $ownerId)->exists();
    }
}