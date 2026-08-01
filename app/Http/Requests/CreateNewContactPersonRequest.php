<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Валидация создания нового контактного лица и его немедленной привязки к контрагенту.
 */
class CreateNewContactPersonRequest extends FormRequest
{
    /**
     * Доступ разрешён через middleware auth (админ и менеджер), принадлежность контрагента проверяется в контроллере.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила полей нового контактного лица и должности в привязке.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'fio' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'interests' => ['nullable', 'string', 'max:1000'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'position' => ['nullable', 'string', 'max:255'],
        ];
    }
}
