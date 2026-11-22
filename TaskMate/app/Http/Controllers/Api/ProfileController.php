<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Throwable;

class ProfileController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $data = Profile::query()->select(['id', 'user_id', 'nim', 'no_telepon', 'program_studi', 'semester', 'foto_profil', 'created_at', 'updated_at'])
                ->with(['user' => fn ($q) => $q->select(['id', 'name', 'email'])])
                ->orderBy('id')->get();
            $queryCount = count(DB::getQueryLog());
            return ApiResponse::success('Daftar profile berhasil ditampilkan', $data, 200,
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
            $data = Profile::query()->select(['id', 'user_id', 'nim', 'no_telepon', 'program_studi', 'semester', 'foto_profil', 'created_at', 'updated_at'])
                ->with(['user' => fn ($q) => $q->select(['id', 'name', 'email'])])->find($id);
            if (!$data) return ApiResponse::error('Data profile tidak ditemukan', 404);
            return ApiResponse::success('Detail profile berhasil ditampilkan', $data);
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
            'nim' => 'required|string|max:30',
            'no_telepon' => 'required|string|max:20',
            'program_studi' => 'required|string|max:100',
            'semester' => 'required|integer|min:1|max:14',
            'foto_profil' => 'nullable|string|max:255',
        ];
        $rules['user_id'][] = Rule::unique('profiles', 'user_id');
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) return ApiResponse::error('Validasi gagal', 422, ['errors' => $validator->errors()]);
            $validated = $validator->validated();

            $data = Profile::create($validated);
            return ApiResponse::success('profile berhasil ditambahkan', $data, 201);
        } catch (Throwable $e) {
            return $this->serverError($e);
        }
    }
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $data = Profile::find($id);
            if (!$data) return ApiResponse::error('Data profile tidak ditemukan', 404);
            if ($request->exists('user_id') || $request->exists('id')) {
                return ApiResponse::error('id dan user_id tidak dapat diubah', 422);
            }
            $ownerId = (int) $data->user_id;
            $required = $request->isMethod('put') ? 'required' : 'sometimes|required';
            $rules = [
            'nim' => $required . '|string|max:30' ,
            'no_telepon' => $required . '|string|max:20' ,
            'program_studi' => $required . '|string|max:100' ,
            'semester' => $required . '|integer|min:1|max:14' ,
            'foto_profil' => 'sometimes|nullable|string|max:255',
            ];
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) return ApiResponse::error('Validasi gagal', 422, ['errors' => $validator->errors()]);
            $validated = $validator->validated();
            if ($request->isMethod('patch') && $validated === []) {
                return ApiResponse::error('PATCH memerlukan minimal satu field', 422);
            }

            $data->update($validated);
            return ApiResponse::success('profile berhasil diperbarui', $data->fresh());
        } catch (Throwable $e) {
            return $this->serverError($e);
        }
    }
    public function destroy(int $id): JsonResponse
    {
        try {
            $data = Profile::find($id);
            if (!$data) return ApiResponse::error('Data profile tidak ditemukan', 404);
            $data->delete();
            return ApiResponse::success('profile berhasil dihapus');
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