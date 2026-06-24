<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Валидация создания контрагента.
 */
class StoreContractorRequest extends FormRequest
{
    /**
     * Доступ разрешён через middleware auth (админ и менеджер).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила валидации полей контрагента.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'inn' => ['required', 'string', 'regex:/^(\d{10}|\d{12})$/', 'unique:contractors,inn'],
            'type' => ['required', 'string', 'in:customer,partner,supplier,vendor'],
            'address' => ['nullable', 'string', 'max:500'],
            'website' => ['nullable', 'string', 'url', 'max:255'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
