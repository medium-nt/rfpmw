<?php

namespace App\Models;

use Database\Factories\EmployedPersonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['contact_person_id', 'contractor_id', 'position'])]
class EmployedPerson extends Model
{
    /** @use HasFactory<EmployedPersonFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Контактное лицо (человек).
     *
     * @return BelongsTo<ContactPerson, self>
     */
    public function contactPerson(): BelongsTo
    {
        return $this->belongsTo(ContactPerson::class);
    }

    /**
     * Компания, где работает этот сотрудник.
     *
     * @return BelongsTo<Contractor, self>
     */
    public function contractor(): BelongsTo
    {
        // withTrashed: после мягкого удаления контрагент остаётся доступен из сотрудника,
        // привязка физически не теряется (SoftDeletes не обнуляет contractor_id).
        return $this->belongsTo(Contractor::class)->withTrashed();
    }

    /**
     * Проекты, где сотрудник является ответственным.
     *
     * @return HasMany<Project>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'responsible_person_id');
    }

    /**
     * Запросы от этого сотрудника.
     *
     * @return HasMany<Request>
     */
    public function requests(): HasMany
    {
        return $this->hasMany(Request::class);
    }

    /**
     * Коммерческие предложения, отправленные этому сотруднику.
     *
     * @return HasMany<Proposal>
     */
    public function proposals(): HasMany
    {
        return $this->hasMany(Proposal::class);
    }

    /**
     * События взаимодействия с этим сотрудником.
     *
     * @return HasMany<Event>
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
