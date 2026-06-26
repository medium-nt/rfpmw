<?php

namespace App\Http\Requests;

use App\Models\EmployedPerson;
use App\Models\Project;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Валидация обновления данных проекта.
 */
class UpdateProjectRequest extends FormRequest
{
    /**
     * Доступ разрешён через middleware auth (админ и менеджер), принадлежность проекта проверяется в контроллере.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила полей проекта. Ответственный должен быть сотрудником контрагента этого проекта.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'status' => ['nullable', 'string', Rule::in(array_keys(Project::getStatuses()))],
            'description' => ['nullable', 'string'],
            'responsible_person_id' => [
                'nullable',
                Rule::exists(EmployedPerson::class, 'id')->where('contractor_id', $this->route('project')->contractor_id),
            ],
        ];
    }
}
