<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['slug', 'title'])]
class Role extends Model
{
    /**
     * Пользователи, которым назначена эта роль.
     *
     * @return HasMany<User>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
