<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role'];
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed'];
    }

    // Isi claim "sub" pada token = id user
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    // Claim tambahan (kosong: jangan taruh password/rahasia di token)
    public function getJWTCustomClaims(): array
    {
        return [];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function profile(): HasOne { return $this->hasOne(Profile::class); }
    public function mataKuliah(): HasMany { return $this->hasMany(MataKuliah::class); }
    public function kategori(): HasMany { return $this->hasMany(Kategori::class); }
    public function tugas(): HasMany { return $this->hasMany(Tugas::class); }
    public function pengumpulan(): HasMany { return $this->hasMany(Pengumpulan::class); }
}