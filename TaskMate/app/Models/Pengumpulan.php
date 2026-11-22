<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\BelongsToOwner;

class Pengumpulan extends Model
{
        use BelongsToOwner;
    protected $table = 'pengumpulan';
    protected $fillable = ['user_id', 'tugas_id', 'tanggal_selesai', 'catatan'];
    protected $casts = ['tanggal_selesai' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tugas(): BelongsTo
    {
        return $this->belongsTo(Tugas::class);
    }
}