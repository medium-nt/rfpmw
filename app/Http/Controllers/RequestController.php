<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRequestRequest;
use App\Http\Requests\UpdateRequestRequest;
use App\Models\Contractor;
use App\Models\Item;
use App\Models\Project;
use App\Models\Proposal;
use App\Models\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
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
            ->with(['employedPerson.contactPerson', 'employedPerson.contractor', 'user', 'requestItems.item.vendor'])
            ->whereHas('employedPerson.contractor', fn ($q) => $q->whereNull('contractors.deleted_at'))
            ->when(auth()->user()->isManager(), function ($q): void {
                $q->whereHas('employedPerson.contractor', fn ($qq) => $qq->where('user_id', auth()->id()));
            })
            ->when(request('q'), function ($query, $q): void {
                // Поиск по имени контрагента (косвенная связь через сотрудника)
                $query->whereHas('employedPerson.contractor', fn ($c) => $c->where('contractors.name', 'like', '%'.$q.'%'));
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
            ->appends(['from' => request('from'), 'to' => request('to'), 'q' => request('q'), 'sort' => $sortField, 'direction' => $sortDirection]);

        return view('requests.index', compact('requests', 'sortField', 'sortDirection'));
    }

    /**
     * Форма создания нового запроса в контексте контрагента.
     */
    public function create(Contractor $contractor): View
    {
        $this->authorizeContractorAccess($contractor);

        $employedPeople = $this->employedPeopleForSelect($contractor);
        $projects = $this->projectsForSelect($contractor);

        return view('requests.create', compact('contractor', 'employedPeople', 'projects'));
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
            'project_id' => $data['project_id'] ?? null,
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

        $request->load(['employedPerson.contactPerson', 'employedPerson.contractor', 'user', 'project', 'requestItems.item.vendor', 'proposals']);

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
        $projects = $this->projectsForSelect($request->employedPerson->contractor);

        return view('requests.edit', compact('request', 'employedPeople', 'projects'));
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

        // Отвязываем все КП этого запроса, чтобы не осталось ссылок на удалённый запрос.
        Proposal::where('request_id', $request->id)->update(['request_id' => null]);

        $request->delete();

        return redirect()
            ->route('requests.index')
            ->with('success', 'Запрос удалён.');
    }

    /**
     * Создание КП из запроса: копирует сотрудника и позиции, дата — сегодня, статус — draft.
     * Один запрос → не более одного КП (unique на proposals.request_id).
     */
    public function createProposal(Request $request): RedirectResponse
    {
        $this->authorizeRequestAccess($request);

        abort_if($request->employedPerson->contractor->trashed(), 404, 'Контрагент удалён.');
        abort_if($request->proposals()->exists(), 403, 'Из этого запроса уже создано КП.');

        $proposal = DB::transaction(function () use ($request) {
            $proposal = Proposal::create([
                'request_id' => $request->id,
                'employed_person_id' => $request->employed_person_id,
                'user_id' => auth()->id(),
                'project_id' => $request->project_id,
                'date' => now()->toDateString(),
                'status' => 'draft',
                'comment' => $request->comment,
            ]);

            // Копируем позиции запроса; price у позиции запроса nullable — падаем в 0 (в КП цена обязательна).
            foreach ($request->requestItems as $requestItem) {
                $proposal->proposalItems()->create([
                    'item_id' => $requestItem->item_id,
                    'quantity' => $requestItem->quantity,
                    'price' => (float) $requestItem->price ?: 0,
                ]);
            }

            $proposal->recalcUsdValue();

            return $proposal;
        });

        return redirect()
            ->route('proposals.show', $proposal)
            ->with('success', 'КП создано из запроса.');
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

    /**
     * Список проектов контрагента для селекта «Проект» (мягко-удалённые исключаются).
     *
     * @return array<int, string>
     */
    protected function projectsForSelect(Contractor $contractor): array
    {
        return $contractor->projects()
            ->orderBy('id')
            ->get()
            ->mapWithKeys(fn (Project $project) => [$project->id => $project->name])
            ->all();
    }
}
