<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\Concerns\BelongsToOwner;

class Kategori extends Model
{
        use BelongsToOwner;
    protected $table = 'kategori';
    protected $fillable = ['user_id', 'nama_kategori'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tugas(): HasMany
    {
        return $this->hasMany(Tugas::class, 'kategori_id');
    }

}