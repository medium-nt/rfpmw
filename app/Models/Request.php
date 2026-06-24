<?php

namespace App\Models;

use Database\Factories\RequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['employed_person_id', 'user_id', 'date', 'usd_value', 'status'])]
class Request extends Model
{
    /** @use HasFactory<RequestFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Атрибуты, приводимые к нативным типам.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'usd_value' => 'decimal:2',
        ];
    }

    /**
     * Сотрудник, от которого поступил запрос.
     *
     * @return BelongsTo<EmployedPerson, self>
     */
    public function employedPerson(): BelongsTo
    {
        return $this->belongsTo(EmployedPerson::class);
    }

    /**
     * Менеджер, принявший запрос.
     *
     * @return BelongsTo<User, self>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Позиции (артикулы) запроса.
     *
     * @return HasMany<RequestItem>
     */
    public function requestItems(): HasMany
    {
        return $this->hasMany(RequestItem::class);
    }

    /**
     * События, привязанные к запросу.
     *
     * @return HasMany<Event>
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
