<?php

namespace App\Http\Requests;

use App\Models\Item;
use App\Models\ProjectItem;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Валидация добавления позиции к проекту.
 */
class StoreProjectItemRequest extends FormRequest
{
    /**
     * Доступ разрешён через middleware auth (админ и менеджер), принадлежность проекта проверяется в контроллере.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила полей позиции проекта.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'item_id' => ['required', Rule::exists(Item::class, 'id')->whereNull('deleted_at')],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', Rule::in(array_keys(ProjectItem::getStatuses()))],
            'production_start_date' => ['nullable', 'date'],
        ];
    }
}
