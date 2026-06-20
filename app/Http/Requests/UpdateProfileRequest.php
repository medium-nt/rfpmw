<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Доступ уже закрыт middleware 'auth' на роуте.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила обновления своего профиля (имя, email). Роль недоступна — неизменяема.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$this->user()->id],
        ];
    }
}
