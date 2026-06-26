<?php

namespace App\Http\Requests;

use App\Models\EmployedPerson;
use App\Models\Event;
use App\Models\Project;
use App\Models\Proposal;
use App\Models\Request;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Валидация обновления данных события.
 */
class UpdateEventRequest extends FormRequest
{
    /**
     * Доступ разрешён через middleware auth (админ и менеджер), принадлежность события проверяется в контроллере.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила полей события. Привязка — единственная (формат type:id), должна принадлежать контрагенту этого события.
     *
     * @return array<string, ValidationRule|array<mixed>|Closure|string>
     */
    public function rules(): array
    {
        $contractorId = $this->route('event')->employedPerson->contractor_id;

        return [
            'employed_person_id' => [
                'required',
                Rule::exists(EmployedPerson::class, 'id')->where('contractor_id', $contractorId),
            ],
            'event_type' => ['required', Rule::in(array_keys(Event::getEventTypes()))],
            'date' => ['required', 'date'],
            'subject' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'link' => ['nullable', $this->linkRule($contractorId)],
        ];
    }

    /**
     * Замыкание-правило: привязка имеет формат «project|request|proposal:{id}» и принадлежит этому контрагенту.
     */
    protected function linkRule(int $contractorId): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($contractorId): void {
            if ($value === null || $value === '') {
                return;
            }

            if (! preg_match('/^(project|request|proposal):(\d+)$/', (string) $value, $m)) {
                $fail('Некорректная привязка.');

                return;
            }

            [$type, $id] = [$m[1], (int) $m[2]];

            if ($type === 'project') {
                $exists = Project::query()->where('id', $id)->where('contractor_id', $contractorId)->exists();
            } else {
                $model = $type === 'request' ? Request::class : Proposal::class;
                $exists = $model::query()
                    ->where('id', $id)
                    ->whereHas('employedPerson', fn ($q) => $q->where('contractor_id', $contractorId))
                    ->exists();
            }

            if (! $exists) {
                $fail('Выбранная запись не принадлежит этому контрагенту.');
            }
        };
    }
}
