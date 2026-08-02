<?php

namespace App\Models;

use Database\Factories\RequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['employed_person_id', 'user_id', 'project_id', 'date', 'usd_value', 'status', 'comment'])]
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
     * Проект контрагента, к которому относится запрос (опционально).
     *
     * @return BelongsTo<Project, self>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
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
     * КП, созданные из этого запроса (не более одного — защищено unique на proposals.request_id).
     *
     * @return HasMany<Proposal>
     */
    public function proposals(): HasMany
    {
        return $this->hasMany(Proposal::class);
    }

    /**
     * Пересчитывает usd_value запроса как Σ(quantity × price) по позициям.
     */
    public function recalcUsdValue(): void
    {
        $total = (float) $this->requestItems->sum(fn (RequestItem $item) => (float) $item->price * (int) $item->quantity);

        $this->forceFill(['usd_value' => $total])->save();
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

    /**
     * Справочник статусов запроса для выбора в формах.
     *
     * @return array<string, string>
     */
    public static function getStatuses(): array
    {
        return [
            'new' => 'Новый',
            'waiting_reply' => 'Ожидает ответа',
            'quoted' => 'Отправлено КП',
            'closed' => 'Закрыт',
        ];
    }
}
