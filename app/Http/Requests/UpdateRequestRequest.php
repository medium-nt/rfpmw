<?php

namespace App\Http\Requests;

use App\Models\EmployedPerson;
use App\Models\Project;
use App\Models\Request;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Валидация обновления данных запроса.
 */
class UpdateRequestRequest extends FormRequest
{
    /**
     * Доступ разрешён через middleware auth (админ и менеджер), принадлежность запроса проверяется в контроллере.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила полей запроса. Сотрудник обязан принадлежать контрагенту этого запроса.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $contractorId = $this->route('request')->employedPerson->contractor_id;

        return [
            'employed_person_id' => [
                'required',
                Rule::exists(EmployedPerson::class, 'id')->where('contractor_id', $contractorId),
            ],
            'project_id' => ['nullable', Rule::exists(Project::class, 'id')->where('contractor_id', $contractorId)],
            'date' => ['required', 'date'],
            'status' => ['nullable', 'string', Rule::in(array_keys(Request::getStatuses()))],
            'comment' => ['nullable', 'string'],
        ];
    }
}
