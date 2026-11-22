<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\Concerns\BelongsToOwner;

class Tugas extends Model
{
        use BelongsToOwner;
    protected $table = 'tugas';
    protected $fillable = ['user_id', 'mata_kuliah_id', 'kategori_id', 'judul', 'deskripsi', 'deadline', 'status'];
    protected $casts = ['deadline' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mataKuliah(): BelongsTo
    {
        return $this->belongsTo(MataKuliah::class, 'mata_kuliah_id');
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class);
    }

    public function pengumpulan(): HasOne
    {
        return $this->hasOne(Pengumpulan::class);
    }

}