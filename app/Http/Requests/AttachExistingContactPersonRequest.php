<?php

namespace App\Http\Requests;

use App\Models\EmployedPerson;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Валидация привязки существующего контактного лица к контрагенту.
 */
class AttachExistingContactPersonRequest extends FormRequest
{
    /**
     * Доступ разрешён через middleware auth (админ и менеджер), принадлежность контрагента проверяется в контроллере.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила: человек должен существовать (и не быть удалённым), пара человек↔контрагент уникальна среди активных связей.
     *
     * @return array<string, ValidationRule|array<mixed>|Closure|string>
     */
    public function rules(): array
    {
        return [
            'contact_person_id' => [
                'required',
                'integer',
                Rule::exists('contact_people', 'id')->whereNull('deleted_at'),
                function (string $attribute, mixed $value, Closure $fail): void {
                    $alreadyAttached = EmployedPerson::query()
                        ->where('contact_person_id', $value)
                        ->where('contractor_id', $this->route('contractor')->id)
                        ->whereNull('deleted_at')
                        ->exists();

                    if ($alreadyAttached) {
                        $fail('Это контактное лицо уже привязано к данному контрагенту.');
                    }
                },
            ],
            'position' => ['nullable', 'string', 'max:255'],
        ];
    }
}
