<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    /**
     * Доступ уже ограничен middleware can:is-admin в роутах.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила валидации обновления пользователя.
     * role_id отсутствует — роль неизменяема после создания.
     * email игнорирует текущего пользователя (берётся из route-параметра {user}).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$this->route('user')->id],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ];
    }
}
