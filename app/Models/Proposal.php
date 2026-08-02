<?php

namespace App\Models;

use Database\Factories\ProposalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['employed_person_id', 'user_id', 'request_id', 'date', 'usd_value', 'status', 'comment'])]
class Proposal extends Model
{
    /** @use HasFactory<ProposalFactory> */
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
     * Сотрудник, которому отправлено КП.
     *
     * @return BelongsTo<EmployedPerson, self>
     */
    public function employedPerson(): BelongsTo
    {
        return $this->belongsTo(EmployedPerson::class);
    }

    /**
     * Менеджер, отправивший КП.
     *
     * @return BelongsTo<User, self>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Запрос, из которого создано КП (опционально — КП может быть самостоятельным).
     *
     * @return BelongsTo<Request, self>
     */
    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class);
    }

    /**
     * Позиции (артикулы) КП.
     *
     * @return HasMany<ProposalItem>
     */
    public function proposalItems(): HasMany
    {
        return $this->hasMany(ProposalItem::class);
    }

    /**
     * Пересчитывает usd_value КП как Σ(quantity × price) по позициям.
     */
    public function recalcUsdValue(): void
    {
        $total = (float) $this->proposalItems->sum(fn (ProposalItem $item) => (float) $item->price * (int) $item->quantity);

        $this->forceFill(['usd_value' => $total])->save();
    }

    /**
     * События, привязанные к КП.
     *
     * @return HasMany<Event>
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * Справочник статусов КП для выбора в формах.
     *
     * @return array<string, string>
     */
    public static function getStatuses(): array
    {
        return [
            'draft' => 'Черновик',
            'sent' => 'КП отправлено',
            'feedback_needed' => 'Нужна обратная связь клиента',
            'invoiced' => 'Выставлен счёт',
            'rejected' => 'Отклонено клиентом',
            'outdated' => 'Неактуальное',
        ];
    }
}
