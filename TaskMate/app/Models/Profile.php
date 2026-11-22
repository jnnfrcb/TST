<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\Concerns\BelongsToOwner;

class Profile extends Model
{
        use BelongsToOwner;
    protected $table = 'profiles';
    protected $fillable = ['user_id', 'nim', 'no_telepon', 'program_studi', 'semester', 'foto_profil'];
    protected $casts = ['semester' => 'integer'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

}
