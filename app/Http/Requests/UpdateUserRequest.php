<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'username' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_-]+$/u', Rule::unique('users', 'username')->ignore($this->route('user')->id)],
            'email' => ['nullable', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('user')->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ];
    }
}
