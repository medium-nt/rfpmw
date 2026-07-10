<?php

namespace App\Http\Requests;

use App\Models\EmployedPerson;
use App\Models\Request;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Валидация создания нового запроса в контексте контрагента.
 */
class StoreRequestRequest extends FormRequest
{
    /**
     * Доступ разрешён через middleware auth (админ и менеджер), принадлежность контрагента проверяется в контроллере.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила полей запроса. Сотрудник обязан принадлежать этому контрагенту.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'employed_person_id' => [
                'required',
                Rule::exists(EmployedPerson::class, 'id')->where('contractor_id', $this->route('contractor')->id),
            ],
            'date' => ['required', 'date'],
            'status' => ['nullable', 'string', Rule::in(array_keys(Request::getStatuses()))],
            'comment' => ['nullable', 'string'],
        ];
    }
}
