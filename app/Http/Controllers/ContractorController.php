<?php

namespace App\Http\Controllers;

use App\Http\Requests\FindPartyRequest;
use App\Http\Requests\StoreContractorRequest;
use App\Http\Requests\UpdateContractorRequest;
use App\Models\ContactPerson;
use App\Models\Contractor;
use App\Models\Event;
use App\Models\Proposal;
use App\Models\Request;
use App\Models\User;
use App\Services\DaData\DadataService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ContractorController extends Controller
{
    /**
     * Список контрагентов с data scoping: админ видит всех, менеджер — только своих.
     * Поиск по названию (name) или ИНН (inn) через GET-параметр ?q=.
     */
    public function index(): View
    {
        $contractors = Contractor::query()
            ->when(auth()->user()->isManager(), fn ($q) => $q->where('user_id', auth()->id()))
            ->when(request('q'), function ($query, $q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', '%'.$q.'%')
                        ->orWhere('inn', 'like', '%'.$q.'%')
                        ->orWhere('legal_address', 'like', '%'.$q.'%')
                        ->orWhere('actual_address', 'like', '%'.$q.'%');
                });
            })
            ->with('user', 'parent')
            ->orderBy('id')
            ->paginate(10)
            ->appends(['q' => request('q')]);

        return view('contractors.index', compact('contractors'));
    }

    /**
     * Форма создания контрагента. Менеджеру доступны только свои данные в селекте менеджера.
     */
    public function create(): View
    {
        $managers = $this->managersForSelect();
        $parents = $this->parentsForSelect();
        $types = Contractor::getTypes();

        return view('contractors.create', compact('managers', 'parents', 'types'));
    }

    /**
     * Поиск компании в DaData по ИНН: возвращает JSON-список найденных вариантов
     * для автозаполнения полей формы контрагента.
     */
    public function findParty(FindPartyRequest $request, DadataService $dadata): JsonResponse
    {
        try {
            $suggestions = $dadata->findPartyByInn($request->validated('inn'));

            return response()->json(['suggestions' => $suggestions]);
        } catch (RuntimeException|ConnectionException $e) {
            $error = $e instanceof RuntimeException
                ? 'Сервис поиска компаний (DaData) не настроен. Обратитесь к администратору.'
                : 'Сервис DaData недоступен. Проверьте подключение к интернету и попробуйте позже.';

            Log::warning('DaData find-party failed', [
                'inn' => $request->validated('inn'),
                'reason' => $e->getMessage(),
            ]);

            return response()->json([
                'suggestions' => [],
                'error' => $error,
            ]);
        }
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

        $contractor->load(['employedPeople.contactPerson', 'projects.responsiblePerson.contactPerson', 'parent']);

        $availablePeople = ContactPerson::query()
            ->whereDoesntHave('employedPeople', fn ($q) => $q->where('contractor_id', $contractor->id)->whereNull('deleted_at'))
            ->orderBy('fio')
            ->get();

        $requests = Request::query()
            ->whereHas('employedPerson', fn ($q) => $q->where('contractor_id', $contractor->id))
            ->with(['employedPerson.contactPerson', 'user'])
            ->orderByDesc('id')
            ->get();

        $proposals = Proposal::query()
            ->whereHas('employedPerson', fn ($q) => $q->where('contractor_id', $contractor->id))
            ->with(['employedPerson.contactPerson', 'user'])
            ->orderByDesc('id')
            ->get();

        $events = Event::query()
            ->whereHas('employedPerson', fn ($q) => $q->where('contractor_id', $contractor->id))
            ->with(['employedPerson.contactPerson', 'user'])
            ->orderByDesc('id')
            ->get();

        return view('contractors.show', compact('contractor', 'availablePeople', 'requests', 'proposals', 'events'));
    }

    /**
     * Форма редактирования контрагента с проверкой доступа менеджера.
     */
    public function edit(Contractor $contractor): View
    {
        $this->authorizeAccess($contractor);

        $managers = $this->managersForSelect();
        $parents = $this->parentsForSelect($contractor->id);
        $types = Contractor::getTypes();

        return view('contractors.edit', compact('contractor', 'managers', 'parents', 'types'));
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
     * Список контрагентов для селекта «Головной контрагент»: менеджер видит только своих,
     * админ — всех. При редактировании текущий контрагент исключается (нельзя быть головным для себя).
     *
     * @return array<int, string>
     */
    protected function parentsForSelect(?int $exceptId = null): array
    {
        return Contractor::query()
            ->when(auth()->user()->isManager(), fn ($q) => $q->where('user_id', auth()->id()))
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
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
