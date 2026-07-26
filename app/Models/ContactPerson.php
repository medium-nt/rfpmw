<?php

namespace App\Models;

use Database\Factories\ContactPersonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['fio', 'phone', 'email', 'interests', 'birth_date'])]
class ContactPerson extends Model
{
    /** @use HasFactory<ContactPersonFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['birth_date' => 'date'];
    }

    /**
     * Места работы человека (связки человек ↔ компания).
     *
     * @return HasMany<EmployedPerson>
     */
    public function employedPeople(): HasMany
    {
        return $this->hasMany(EmployedPerson::class);
    }
}
