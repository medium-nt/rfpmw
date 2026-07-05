<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

/**
 * Валидация ИНН для поиска компании через DaData.
 *
 * Ответ всегда возвращается в JSON (роут используется только из AJAX),
 * поэтому ошибки валидации отдаются как 422 JSON вместо редиректа.
 */
class FindPartyRequest extends FormRequest
{
    /**
     * Доступ разрешён через middleware auth (админ и менеджер).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила валидации ИНН: 10 цифр (юрлицо) или 12 цифр (ИП).
     *
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'inn' => ['required', 'string', 'regex:/^(\d{10}|\d{12})$/'],
        ];
    }

    /**
     * При ошибке валидации всегда возвращать JSON 422 (запрос идёт только из AJAX).
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json(['errors' => $validator->errors()], JsonResponse::HTTP_UNPROCESSABLE_ENTITY)
        );
    }
}
