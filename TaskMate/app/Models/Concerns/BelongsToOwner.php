<?php

namespace App\Models\Concerns;

use App\Models\Scopes\OwnerScope;

trait BelongsToOwner
{
    protected static function bootBelongsToOwner(): void
    {
        static::addGlobalScope(new OwnerScope());
    }
}