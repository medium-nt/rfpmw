<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRequestRequest;
use App\Http\Requests\UpdateRequestRequest;
use App\Models\Contractor;
use App\Models\Item;
use App\Models\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RequestController extends Controller
{
    /**
     * Список запросов с data scoping: админ видит все, менеджер — только запросы своих контрагентов.
     * Сортировка по клику на заголовок: ?sort=date|contractor&direction=asc|desc.
     * По умолчанию — по дате (desc), вверху более свежие.
     */
    public function index(): View
    {
        $allowedSortFields = ['date', 'contractor'];
        $sortField = request('sort', 'date');
        $sortDirection = request('direction', 'desc');

        if (! in_array($sortField, $allowedSortFields)) {
            $sortField = 'date';
        }
        if (! in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }

        $requests = Request::query()
            ->with(['employedPerson.contactPerson', 'employedPerson.contractor', 'user'])
            ->whereHas('employedPerson.contractor', fn ($q) => $q->whereNull('contractors.deleted_at'))
            ->when(auth()->user()->isManager(), function ($q): void {
                $q->whereHas('employedPerson.contractor', fn ($qq) => $qq->where('user_id', auth()->id()));
            })
            ->when(request('from'), fn ($q) => $q->where('date', '>=', request('from')))
            ->when(request('to'), fn ($q) => $q->where('date', '<=', request('to')))
            ->when($sortField === 'contractor', function ($query) use ($sortDirection): void {
                // Сортировка по имени контрагента (косвенная связь через employed_people, 1-к-1, дублей нет)
                $query->leftJoin('employed_people', 'requests.employed_person_id', '=', 'employed_people.id')
                    ->leftJoin('contractors', 'employed_people.contractor_id', '=', 'contractors.id')
                    ->select('requests.*')
                    ->orderBy('contractors.name', $sortDirection);
            }, function ($query) use ($sortDirection): void {
                $query->orderBy('date', $sortDirection);
            })
            ->paginate(10)
            ->appends(['from' => request('from'), 'to' => request('to'), 'sort' => $sortField, 'direction' => $sortDirection]);

        return view('requests.index', compact('requests', 'sortField', 'sortDirection'));
    }

    /**
     * Форма создания нового запроса в контексте контрагента.
     */
    public function create(Contractor $contractor): View
    {
        $this->authorizeContractorAccess($contractor);

        $employedPeople = $this->employedPeopleForSelect($contractor);

        return view('requests.create', compact('contractor', 'employedPeople'));
    }

    /**
     * Сохранение нового запроса. user_id — текущий пользователь, usd_value не вводится вручную (кэш позиций, см. observer TODO).
     */
    public function store(StoreRequestRequest $input, Contractor $contractor): RedirectResponse
    {
        $this->authorizeContractorAccess($contractor);

        $data = $input->validated();

        Request::create([
            'employed_person_id' => $data['employed_person_id'],
            'user_id' => auth()->id(),
            'date' => $data['date'],
            'status' => $data['status'] ?? null,
        ]);

        return redirect()
            ->route('contractors.show', $contractor)
            ->with('success', 'Запрос успешно создан.');
    }

    /**
     * Карточка запроса с сотрудником, контрагентом и менеджером.
     */
    public function show(Request $request): View
    {
        $this->authorizeRequestAccess($request);

        abort_if($request->employedPerson->contractor->trashed(), 404, 'Контрагент удалён.');

        $request->load(['employedPerson.contactPerson', 'employedPerson.contractor', 'user', 'requestItems.item.vendor']);

        $items = Item::forSelect();

        return view('requests.show', compact('request', 'items'));
    }

    /**
     * Форма редактирования запроса с проверкой доступа менеджера.
     */
    public function edit(Request $request): View
    {
        $this->authorizeRequestAccess($request);

        $employedPeople = $this->employedPeopleForSelect($request->employedPerson->contractor);

        return view('requests.edit', compact('request', 'employedPeople'));
    }

    /**
     * Обновление данных запроса. user_id и usd_value не меняются.
     */
    public function update(UpdateRequestRequest $input, Request $request): RedirectResponse
    {
        $this->authorizeRequestAccess($request);

        $request->update($input->validated());

        return redirect()
            ->route('requests.show', $request)
            ->with('success', 'Запрос успешно обновлён.');
    }

    /**
     * Мягкое удаление запроса (без UI корзины на данный момент).
     */
    public function destroy(Request $request): RedirectResponse
    {
        $this->authorizeRequestAccess($request);

        $request->delete();

        return redirect()
            ->route('requests.index')
            ->with('success', 'Запрос удалён.');
    }

    /**
     * Список сотрудников контрагента для селекта «От кого поступил запрос».
     *
     * @return array<int, string>
     */
    protected function employedPeopleForSelect(Contractor $contractor): array
    {
        return $contractor->employedPeople()
            ->with('contactPerson')
            ->get()
            ->mapWithKeys(fn ($employed) => [
                $employed->id => $employed->position
                    ? "{$employed->contactPerson->fio} ({$employed->position})"
                    : $employed->contactPerson->fio,
            ])
            ->all();
    }
}
