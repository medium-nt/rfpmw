<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Отображает страницу профиля текущего пользователя (просмотр + формы).
     */
    public function edit(): View
    {
        return view('profile.edit', ['user' => auth()->user()]);
    }

    /**
     * Обновляет имя и email текущего пользователя. Роль не меняется.
     */
    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        return redirect()
            ->route('profile.edit')
            ->with('success', 'Профиль успешно обновлён.');
    }

    /**
     * Меняет пароль текущего пользователя (хеширование — через cast 'hashed').
     */
    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $request->user()->update([
            'password' => $request->validated('password'),
        ]);

        return redirect()
            ->route('profile.edit')
            ->with('success', 'Пароль успешно изменён.');
    }
}
