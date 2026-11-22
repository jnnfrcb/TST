<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pengumpulan;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Throwable;
use App\Models\Tugas;

class PengumpulanController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $data = Pengumpulan::query()->select(['id', 'user_id', 'tugas_id', 'tanggal_selesai', 'catatan', 'created_at', 'updated_at'])
                ->with(['tugas' => fn ($q) => $q->select(['id', 'user_id', 'judul', 'status']), 'user' => fn ($q) => $q->select(['id', 'name', 'email'])])
                ->orderBy('id')->get();
            $queryCount = count(DB::getQueryLog());
            return ApiResponse::success('Daftar pengumpulan berhasil ditampilkan', $data, 200,
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
            $data = Pengumpulan::query()->select(['id', 'user_id', 'tugas_id', 'tanggal_selesai', 'catatan', 'created_at', 'updated_at'])
                ->with(['tugas' => fn ($q) => $q->select(['id', 'user_id', 'judul', 'status']), 'user' => fn ($q) => $q->select(['id', 'name', 'email'])])->find($id);
            if (!$data) return ApiResponse::error('Data pengumpulan tidak ditemukan', 404);
            return ApiResponse::success('Detail pengumpulan berhasil ditampilkan', $data);
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
            'tugas_id' => ['required', 'integer', Rule::exists('tugas', 'id')->where('user_id', $ownerId)],
            'tanggal_selesai' => 'required|date',
            'catatan' => 'nullable|string',
        ];
        $rules['tugas_id'][] = Rule::unique('pengumpulan', 'tugas_id');
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) return ApiResponse::error('Validasi gagal', 422, ['errors' => $validator->errors()]);
            $validated = $validator->validated();

            if (!$this->ownerReferencesValid($validated, $ownerId)) {
                return ApiResponse::error('Tugas bukan milik user tersebut', 422);
            }

            $data = DB::transaction(function () use ($validated) {
                $result = Pengumpulan::create($validated);
                Tugas::whereKey($validated['tugas_id'])->update(['status' => 'selesai']);
                return $result;
            });
            return ApiResponse::success('pengumpulan berhasil ditambahkan', $data, 201);
        } catch (Throwable $e) {
            return $this->serverError($e);
        }
    }
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $data = Pengumpulan::find($id);
            if (!$data) return ApiResponse::error('Data pengumpulan tidak ditemukan', 404);
            if ($request->exists('user_id') || $request->exists('id')) {
                return ApiResponse::error('id dan user_id tidak dapat diubah', 422);
            }
            $ownerId = (int) $data->user_id;
            $required = $request->isMethod('put') ? 'required' : 'sometimes|required';
            $rules = [
            'tugas_id' => [...explode('|', $required), 'integer', Rule::exists('tugas', 'id')->where('user_id', $ownerId)],
            'tanggal_selesai' => $required . '|date' ,
            'catatan' => 'nullable|string',
            ];
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) return ApiResponse::error('Validasi gagal', 422, ['errors' => $validator->errors()]);
            $validated = $validator->validated();
            if ($request->isMethod('patch') && $validated === []) {
                return ApiResponse::error('PATCH memerlukan minimal satu field', 422);
            }

            $merged = array_merge($data->only(['tugas_id', 'tanggal_selesai', 'catatan']), $validated);
            if (!$this->ownerReferencesValid($merged, $ownerId)) {
                return ApiResponse::error('Relasi tidak dimiliki user pemilik data', 422);
            }

            if (isset($validated['tugas_id']) && Pengumpulan::where('tugas_id', $validated['tugas_id'])->where('id', '!=', $id)->exists()) {
                return ApiResponse::error('Tugas sudah memiliki pengumpulan', 422);
            }

            if (isset($validated['tugas_id']) && (int) $validated['tugas_id'] !== (int) $data->tugas_id) {
                return ApiResponse::error('tugas_id tidak boleh dipindahkan pada pengumpulan yang sudah ada', 422);
            }
            $data->update($validated);
            return ApiResponse::success('pengumpulan berhasil diperbarui', $data->fresh());
        } catch (Throwable $e) {
            return $this->serverError($e);
        }
    }
    public function destroy(int $id): JsonResponse
    {
        try {
            $data = Pengumpulan::find($id);
            if (!$data) return ApiResponse::error('Data pengumpulan tidak ditemukan', 404);
            $data->delete();
            return ApiResponse::success('pengumpulan berhasil dihapus');
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
        return Tugas::where('id', $fields['tugas_id'])->where('user_id', $ownerId)->exists();
    }
}