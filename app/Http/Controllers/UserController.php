<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
            ->paginate(10);

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
     * Удаляет пользователя (soft delete), если нет блокирующих связей и защит.
     */
    public function destroy(User $user): RedirectResponse
    {
        $reason = $this->deletionBlocker($user);

        if ($reason !== null) {
            return back()->with('error', $reason);
        }

        // Мгновенный logout: чистим сессии удаляемого пользователя.
        DB::table('sessions')->where('user_id', $user->id)->delete();

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', 'Пользователь удалён.');
    }

    /**
     * Возвращает причину запрета удаления пользователя или null, если удаление разрешено.
     */
    private function deletionBlocker(User $user): ?string
    {
        // 1. Нельзя удалить самого себя.
        if (Auth::id() === $user->id) {
            return 'Нельзя удалить свою учётную запись.';
        }

        // 2. Нельзя удалить последнего администратора.
        if ($user->isAdmin()
            && User::whereHas('role', fn ($q) => $q->where('slug', 'admin'))->count() <= 1) {
            return 'Нельзя удалить последнего администратора.';
        }

        // 3. Нельзя удалить, если есть активные бизнес-связи.
        $dependencies = $this->dependencySummary($user);
        if ($dependencies !== []) {
            return 'Удаление невозможно: пользователь закреплён за '
                .implode(', ', $dependencies).'.';
        }

        return null;
    }

    /**
     * Формирует список непустых связей пользователя с количеством (для сообщения об ошибке).
     *
     * @return list<string>
     */
    private function dependencySummary(User $user): array
    {
        $parts = [];
        foreach ([
            'контрагентами' => $user->contractors()->count(),
            'запросами' => $user->requests()->count(),
            'КП' => $user->proposals()->count(),
            'событиями' => $user->events()->count(),
        ] as $label => $count) {
            if ($count > 0) {
                $parts[] = "$label ($count)";
            }
        }

        return $parts;
    }
}
