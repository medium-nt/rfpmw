<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Валидация обновления артикула.
 */
class UpdateItemRequest extends FormRequest
{
    /**
     * Доступ разрешён через middleware can:is-admin (только админ).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила валидации полей артикула.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sku' => ['required', 'string', 'max:255', Rule::unique('items', 'sku')->where(fn ($query) => $query->where('vendor_id', $this->vendor_id))->ignore($this->item)],
            'vendor_id' => ['required', 'integer', Rule::exists('contractors', 'id')->where('type', 'vendor')],
            'description' => ['nullable', 'string'],
        ];
    }
}
