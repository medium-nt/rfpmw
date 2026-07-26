<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Валидация обновления личных данных контактного лица.
 */
class UpdateContactPersonRequest extends FormRequest
{
    /**
     * Доступ разрешён через middleware auth (админ и менеджер), принадлежность проверяется в контроллере.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила полей контактного лица (без должности — она атрибут связи).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'fio' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'interests' => ['nullable', 'string', 'max:1000'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
        ];
    }
}
