<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePasswordRequest extends FormRequest
{
    /**
     * Доступ уже закрыт middleware 'auth' на роуте.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила смены своего пароля: current_password проверяется встроенным правилом.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
