<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Отображает список пользователей.
     */
    public function index(): View
    {
        $users = User::query()
            ->when(request('q'), function ($query, $q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', '%'.$q.'%')
                        ->orWhere('username', 'like', '%'.$q.'%');
                });
            })
            ->with('role')
            ->orderBy('id')
            ->get();

        return view('users.index', compact('users'));
    }

    /**
     * Отображает форму создания пользователя.
     */
    public function create(): View
    {
        $roles = Role::orderBy('title')->pluck('title', 'id');

        return view('users.create', compact('roles'));
    }

    /**
     * Сохраняет нового пользователя (пароль хешируется автоматически через cast 'hashed').
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        User::create($request->validated());

        return redirect()
            ->route('users.index')
            ->with('success', 'Пользователь успешно создан.');
    }

    /**
     * Отображает форму редактирования пользователя.
     */
    public function edit(User $user): View
    {
        $roles = Role::orderBy('title')->pluck('title', 'id');

        return view('users.edit', compact('user', 'roles'));
    }

    /**
     * Обновляет пользователя. Роль не меняется (role_id отсутствует в запросе).
     * Пустой пароль игнорируется — существующий хеш не перезаписывается.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()
            ->route('users.index')
            ->with('success', 'Пользователь успешно обновлён.');
    }

    /**
     * Заглушка удаления: реальный функционал с условиями — позже.
     */
    public function destroy(User $user): RedirectResponse
    {
        return back()->with('error', 'Удаление пока недоступно.');
    }
}
