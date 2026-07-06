<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Валидация обновления контрагента.
 */
class UpdateContractorRequest extends FormRequest
{
    /**
     * Доступ разрешён через middleware auth (админ и менеджер).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Нормализация сайта перед валидацией: если ввод без схемы (http/https),
     * дописываем https:// — иначе правило url отклонит значение вроде «сайт.ру».
     */
    protected function prepareForValidation(): void
    {
        $website = $this->string('website')->trim()->toString();

        if ($website !== '' && ! preg_match('#^https?://#i', $website)) {
            $this->merge(['website' => 'https://'.$website]);
        }
    }

    /**
     * Правила валидации полей контрагента.
     *
     * ИНН уникален с игнорированием текущей записи.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'inn' => ['required', 'string', 'regex:/^(\d{10}|\d{12})$/', 'unique:contractors,inn,'.$this->route('contractor')->id],
            'type' => ['required', 'string', 'in:customer,partner,supplier,vendor'],
            'legal_address' => ['nullable', 'string', 'max:500'],
            'actual_address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'max:255'],
            'industry' => ['nullable', 'string', 'max:255'],
            // Контрагент не может быть головным для самого себя.
            'parent_id' => ['nullable', 'integer', 'exists:contractors,id', 'not_in:'.$this->route('contractor')->id],
            'website' => ['nullable', 'string', 'url', 'max:255'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
