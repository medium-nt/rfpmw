<?php

namespace App\Models;

use Database\Factories\ContractorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['user_id', 'name', 'inn', 'legal_address', 'actual_address', 'phone', 'region', 'industry', 'parent_id', 'website', 'type'])]
class Contractor extends Model
{
    /** @use HasFactory<ContractorFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Менеджер, за которым закреплён контрагент.
     *
     * @return BelongsTo<User, self>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Головной контрагент (самоссылающаяся связь parent_id).
     *
     * @return BelongsTo<Contractor, self>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Сотрудники контрагента (связки человек ↔ эта компания).
     *
     * @return HasMany<EmployedPerson>
     */
    public function employedPeople(): HasMany
    {
        return $this->hasMany(EmployedPerson::class);
    }

    /**
     * Проекты контрагента.
     *
     * @return HasMany<Project>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Справочник типов контрагента для выбора в формах.
     *
     * @return array<string, string>
     */
    public static function getTypes(): array
    {
        return [
            'customer' => 'Заказчик',
            'partner' => 'Партнёр',
            'supplier' => 'Поставщик',
            'vendor' => 'Вендор (производитель)',
        ];
    }
}
