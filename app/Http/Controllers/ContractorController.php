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
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use RuntimeException;

class ContractorController extends Controller
{
    /** Размер страницы для независимой пагинации связанных сущностей в карточке контрагента. */
    private const int PER_PAGE = 5;

    /**
     * Список контрагентов с data scoping: админ видит всех, менеджер — только своих.
     * Поиск по названию (name) или ИНН (inn) через GET-параметр ?q=.
     * Сортировка по клику на заголовок: ?sort=name|inn&direction=asc|desc.
     */
    public function index(): View
    {
        // Белый список полей для сортировки (безопасность)
        $allowedSortFields = ['name', 'inn'];

        // Получаем параметры сортировки с defaults
        $sortField = request('sort', 'name');
        $sortDirection = request('direction', 'asc');

        // Валидация поля
        if (! in_array($sortField, $allowedSortFields)) {
            $sortField = 'name';
        }

        // Валидация направления
        if (! in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'asc';
        }

        // Базовый запрос с scoping и поиском
        $query = Contractor::query()
            ->when(auth()->user()->isManager(), fn ($q) => $q->where('user_id', auth()->id()))
            ->when(request('q'), function ($query, $q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', '%'.$q.'%')
                        ->orWhere('inn', 'like', '%'.$q.'%')
                        ->orWhere('legal_address', 'like', '%'.$q.'%')
                        ->orWhere('actual_address', 'like', '%'.$q.'%');
                });
            })
            ->orderBy($sortField, $sortDirection);

        $contractors = $query->with('user', 'parent')
            ->paginate(10)
            ->appends([
                'q' => request('q'),
                'sort' => $sortField,
                'direction' => $sortDirection,
            ]);

        return view('contractors.index', compact('contractors', 'sortField', 'sortDirection'));
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
     *
     * Каждый блок связанных сущностей (события, контактные лица, проекты, запросы, КП)
     * пагинируется независимо — по PER_PAGE записей, через собственный query-параметр
     * (?page_events, ?page_contacts, ?page_projects, ?page_requests, ?page_proposals),
     * чтобы листание одного блока не сбрасывало страницы остальных. appends() сохраняет
     * чужие page_*-параметры при переходе по страницам.
     *
     * Сортировка по умолчанию: события/запросы/КП/проекты — по дате (desc, свежие сверху),
     * контактные лица — по ФИО (алфавит, asc).
     */
    public function show(Contractor $contractor): View
    {
        $this->authorizeAccess($contractor);

        $contractor->load([
            'employedPeople.contactPerson',
            'parent',
        ]);

        // Сортировка контактных лиц по ФИО (алфавит) — коллекцией, т.к. fio в связанной таблице
        // и сортировка через join потребовала бы leftJoin (чтобы не терять сотрудников с удалённым профилем).
        $contractor->setRelation(
            'employedPeople',
            $contractor->employedPeople->sortBy(fn ($ep) => mb_strtolower($ep->contactPerson?->fio ?? ''))->values()
        );

        // Независимая пагинация контактных лиц: коллекция уже отсортирована, режем вручную
        // (контактных лиц обычно мало, SQL-пагинация по contactPerson.fio была бы оверинжинирингом).
        $peoplePage = LengthAwarePaginator::resolveCurrentPage('page_contacts');
        $employedPeople = new LengthAwarePaginator(
            $contractor->employedPeople->forPage($peoplePage, self::PER_PAGE),
            $contractor->employedPeople->count(),
            self::PER_PAGE,
            $peoplePage,
            ['pageName' => 'page_contacts', 'path' => request()->url()],
        );
        $employedPeople->appends(request()->except('page_contacts'));

        $availablePeople = ContactPerson::query()
            ->whereDoesntHave('employedPeople', fn ($q) => $q->where('contractor_id', $contractor->id)->whereNull('deleted_at'))
            ->orderBy('fio')
            ->get();

        $projects = $contractor->projects()
            ->with('responsiblePerson.contactPerson')
            ->orderByDesc('date')
            ->paginate(self::PER_PAGE, ['*'], 'page_projects')
            ->appends(request()->except('page_projects'));

        $requests = Request::query()
            ->whereHas('employedPerson', fn ($q) => $q->where('contractor_id', $contractor->id))
            ->with(['employedPerson.contactPerson', 'user'])
            ->orderByDesc('date')
            ->paginate(self::PER_PAGE, ['*'], 'page_requests')
            ->appends(request()->except('page_requests'));

        $proposals = Proposal::query()
            ->whereHas('employedPerson', fn ($q) => $q->where('contractor_id', $contractor->id))
            ->with(['employedPerson.contactPerson', 'user'])
            ->orderByDesc('date')
            ->paginate(self::PER_PAGE, ['*'], 'page_proposals')
            ->appends(request()->except('page_proposals'));

        $events = Event::query()
            ->whereHas('employedPerson', fn ($q) => $q->where('contractor_id', $contractor->id))
            ->with(['employedPerson.contactPerson', 'user'])
            ->orderByDesc('date')
            ->paginate(self::PER_PAGE, ['*'], 'page_events')
            ->appends(request()->except('page_events'));

        return view('contractors.show', compact(
            'contractor',
            'availablePeople',
            'employedPeople',
            'projects',
            'requests',
            'proposals',
            'events',
        ));
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
