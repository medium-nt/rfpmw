<?php

namespace App\Models;

use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['contractor_id', 'responsible_person_id', 'name', 'description', 'date', 'usd_value', 'status'])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
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
     * Контрагент-владелец проекта.
     *
     * @return BelongsTo<Contractor, self>
     */
    public function contractor(): BelongsTo
    {
        // withTrashed: после мягкого удаления контрагент остаётся доступен из проекта,
        // привязка физически не теряется (SoftDeletes не обнуляет contractor_id).
        return $this->belongsTo(Contractor::class)->withTrashed();
    }

    /**
     * Ответственный сотрудник (контактное лицо в компании).
     *
     * @return BelongsTo<EmployedPerson, self>
     */
    public function responsiblePerson(): BelongsTo
    {
        return $this->belongsTo(EmployedPerson::class, 'responsible_person_id');
    }

    /**
     * Позиции (артикулы) проекта.
     *
     * @return HasMany<ProjectItem>
     */
    public function projectItems(): HasMany
    {
        return $this->hasMany(ProjectItem::class);
    }

    /**
     * Пересчитывает usd_value проекта как Σ(quantity × price) по позициям.
     */
    public function recalcUsdValue(): void
    {
        $total = (float) $this->projectItems->sum(fn (ProjectItem $item) => (float) $item->price * (int) $item->quantity);

        $this->forceFill(['usd_value' => $total])->save();
    }

    /**
     * События, привязанные к проекту.
     *
     * @return HasMany<Event>
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * Справочник статусов проекта для выбора в формах.
     *
     * @return array<string, string>
     */
    public static function getStatuses(): array
    {
        return [
            'concept' => 'Концепт',
            'development' => 'Разработка',
            'testing' => 'Тестирование образцов',
            'serial' => 'В серии',
            'completed' => 'Завершён',
            'no_go' => 'Не пошёл',
        ];
    }
}
