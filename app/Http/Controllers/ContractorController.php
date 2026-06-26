<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContractorRequest;
use App\Http\Requests\UpdateContractorRequest;
use App\Models\ContactPerson;
use App\Models\Contractor;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ContractorController extends Controller
{
    /**
     * Список контрагентов с data scoping: админ видит всех, менеджер — только своих.
     */
    public function index(): View
    {
        $contractors = Contractor::query()
            ->when(auth()->user()->isManager(), fn ($q) => $q->where('user_id', auth()->id()))
            ->with('user')
            ->orderBy('id')
            ->paginate(10);

        return view('contractors.index', compact('contractors'));
    }

    /**
     * Форма создания контрагента. Менеджеру доступны только свои данные в селекте менеджера.
     */
    public function create(): View
    {
        $managers = $this->managersForSelect();
        $types = Contractor::getTypes();

        return view('contractors.create', compact('managers', 'types'));
    }

    /**
     * Сохранение нового контрагента. Менеджеру принудительно назначается его user_id.
     */
    public function store(StoreContractorRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if (auth()->user()->isManager()) {
            $data['user_id'] = auth()->id();
        }

        $contractor = Contractor::create($data);

        return redirect()
            ->route('contractors.show', $contractor)
            ->with('success', 'Контрагент успешно создан.');
    }

    /**
     * Карточка контрагента с данными, контактными лицами и действиями.
     */
    public function show(Contractor $contractor): View
    {
        $this->authorizeAccess($contractor);

        $contractor->load('employedPeople.contactPerson');

        $availablePeople = ContactPerson::query()
            ->whereDoesntHave('employedPeople', fn ($q) => $q->where('contractor_id', $contractor->id)->whereNull('deleted_at'))
            ->orderBy('fio')
            ->get();

        return view('contractors.show', compact('contractor', 'availablePeople'));
    }

    /**
     * Форма редактирования контрагента с проверкой доступа менеджера.
     */
    public function edit(Contractor $contractor): View
    {
        $this->authorizeAccess($contractor);

        $managers = $this->managersForSelect();
        $types = Contractor::getTypes();

        return view('contractors.edit', compact('contractor', 'managers', 'types'));
    }

    /**
     * Обновление контрагента. Менеджер не может сменить владельца (user_id зафиксирован).
     */
    public function update(UpdateContractorRequest $request, Contractor $contractor): RedirectResponse
    {
        $this->authorizeAccess($contractor);

        $data = $request->validated();

        if (auth()->user()->isManager()) {
            $data['user_id'] = $contractor->user_id;
        }

        $contractor->update($data);

        return redirect()
            ->route('contractors.show', $contractor)
            ->with('success', 'Контрагент успешно обновлён.');
    }

    /**
     * Мягкое удаление контрагента (перемещение в корзину). Привязки сохраняются.
     */
    public function destroy(Contractor $contractor): RedirectResponse
    {
        $this->authorizeAccess($contractor);

        $contractor->delete();

        return redirect()
            ->route('contractors.index')
            ->with('success', 'Контрагент перемещён в корзину.');
    }

    /**
     * Список удалённых контрагентов (корзина). Доступен только администратору.
     */
    public function trashed(): View
    {
        $this->authorizeAdmin();

        $contractors = Contractor::onlyTrashed()
            ->with('user')
            ->orderBy('deleted_at', 'desc')
            ->get();

        return view('contractors.trashed', compact('contractors'));
    }

    /**
     * Восстановление контрагента из корзины. Только администратор.
     */
    public function restore(int $id): RedirectResponse
    {
        $this->authorizeAdmin();

        Contractor::onlyTrashed()->whereKey($id)->firstOrFail()->restore();

        return redirect()
            ->route('contractors.trashed')
            ->with('success', 'Контрагент восстановлен.');
    }

    /**
     * Список менеджеров для селекта формы: менеджер видит только себя, админ — всех.
     *
     * @return array<int, string>
     */
    protected function managersForSelect(): array
    {
        return User::query()
            ->whereHas('role', fn ($q) => $q->where('slug', 'manager'))
            ->when(auth()->user()->isManager(), fn ($q) => $q->where('id', auth()->id()))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * Проверка доступа менеджера: можно работать только со своими контрагентами.
     */
    protected function authorizeAccess(Contractor $contractor): void
    {
        $this->authorizeContractorAccess($contractor);
    }

    /**
     * Проверка доступа: действие доступно только администратору.
     */
    protected function authorizeAdmin(): void
    {
        if (! auth()->user()->isAdmin()) {
            abort(403, 'Действие доступно только администратору.');
        }
    }
}
