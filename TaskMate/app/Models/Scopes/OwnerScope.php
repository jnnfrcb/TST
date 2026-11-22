<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

// Role user hanya melihat/mengubah data miliknya. Admin melihat semua.
// Tanpa login (seeder, artisan) scope tidak aktif.
class OwnerScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::guard('api')->user();

        if ($user && $user->role !== 'admin') {
            $builder->where($model->getTable() . '.user_id', $user->id);
        }
    }
}