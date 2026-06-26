<?php

namespace App\Http\Requests;

use App\Models\EmployedPerson;
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
        return [
            'employed_person_id' => [
                'required',
                Rule::exists(EmployedPerson::class, 'id')->where('contractor_id', $this->route('request')->employedPerson->contractor_id),
            ],
            'date' => ['required', 'date'],
            'status' => ['nullable', 'string', Rule::in(array_keys(Request::getStatuses()))],
        ];
    }
}
