<?php

namespace App\Models;

use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['user_id', 'employed_person_id', 'event_type', 'date', 'subject', 'description', 'project_id', 'request_id', 'proposal_id'])]
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
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
        ];
    }

    /**
     * Менеджер — автор события.
     *
     * @return BelongsTo<User, self>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Сотрудник, с которым произошло взаимодействие.
     *
     * @return BelongsTo<EmployedPerson, self>
     */
    public function employedPerson(): BelongsTo
    {
        return $this->belongsTo(EmployedPerson::class);
    }

    /**
     * Проект, к которому привязано событие (опционально).
     *
     * @return BelongsTo<Project, self>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Запрос, к которому привязано событие (опционально).
     *
     * @return BelongsTo<Request, self>
     */
    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class);
    }

    /**
     * КП, к которому привязано событие (опционально).
     *
     * @return BelongsTo<Proposal, self>
     */
    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    /**
     * Справочник типов события для выбора в формах.
     *
     * @return array<string, string>
     */
    public static function getEventTypes(): array
    {
        return [
            'call' => 'Звонок',
            'letter' => 'Письмо',
            'meeting' => 'Встреча',
        ];
    }
}
