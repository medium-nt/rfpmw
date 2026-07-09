<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProposalRequest;
use App\Http\Requests\UpdateProposalRequest;
use App\Models\Contractor;
use App\Models\Item;
use App\Models\Proposal;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProposalController extends Controller
{
    /**
     * Список КП с data scoping: админ видит все, менеджер — только КП своих контрагентов.
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

        $proposals = Proposal::query()
            ->with(['employedPerson.contactPerson', 'employedPerson.contractor', 'user'])
            ->whereHas('employedPerson.contractor', fn ($q) => $q->whereNull('contractors.deleted_at'))
            ->when(auth()->user()->isManager(), function ($q): void {
                $q->whereHas('employedPerson.contractor', fn ($qq) => $qq->where('user_id', auth()->id()));
            })
            ->when(request('from'), fn ($q) => $q->where('date', '>=', request('from')))
            ->when(request('to'), fn ($q) => $q->where('date', '<=', request('to')))
            ->when($sortField === 'contractor', function ($query) use ($sortDirection): void {
                // Сортировка по имени контрагента (косвенная связь через employed_people, 1-к-1, дублей нет)
                $query->leftJoin('employed_people', 'proposals.employed_person_id', '=', 'employed_people.id')
                    ->leftJoin('contractors', 'employed_people.contractor_id', '=', 'contractors.id')
                    ->select('proposals.*')
                    ->orderBy('contractors.name', $sortDirection);
            }, function ($query) use ($sortDirection): void {
                $query->orderBy('date', $sortDirection);
            })
            ->paginate(10)
            ->appends(['from' => request('from'), 'to' => request('to'), 'sort' => $sortField, 'direction' => $sortDirection]);

        return view('proposals.index', compact('proposals', 'sortField', 'sortDirection'));
    }

    /**
     * Форма создания нового КП в контексте контрагента.
     */
    public function create(Contractor $contractor): View
    {
        $this->authorizeContractorAccess($contractor);

        $employedPeople = $this->employedPeopleForSelect($contractor);

        return view('proposals.create', compact('contractor', 'employedPeople'));
    }

    /**
     * Сохранение нового КП. user_id — текущий пользователь, usd_value не вводится вручную (кэш позиций, см. observer TODO).
     */
    public function store(StoreProposalRequest $request, Contractor $contractor): RedirectResponse
    {
        $this->authorizeContractorAccess($contractor);

        $data = $request->validated();

        Proposal::create([
            'employed_person_id' => $data['employed_person_id'],
            'user_id' => auth()->id(),
            'date' => $data['date'],
            'status' => $data['status'] ?? null,
        ]);

        return redirect()
            ->route('contractors.show', $contractor)
            ->with('success', 'КП успешно создано.');
    }

    /**
     * Карточка КП с сотрудником, контрагентом и менеджером.
     */
    public function show(Proposal $proposal): View
    {
        $this->authorizeProposalAccess($proposal);

        abort_if($proposal->employedPerson->contractor->trashed(), 404, 'Контрагент удалён.');

        $proposal->load(['employedPerson.contactPerson', 'employedPerson.contractor', 'user', 'proposalItems.item.vendor']);

        $items = Item::forSelect();

        return view('proposals.show', compact('proposal', 'items'));
    }

    /**
     * Форма редактирования КП с проверкой доступа менеджера.
     */
    public function edit(Proposal $proposal): View
    {
        $this->authorizeProposalAccess($proposal);

        $employedPeople = $this->employedPeopleForSelect($proposal->employedPerson->contractor);

        return view('proposals.edit', compact('proposal', 'employedPeople'));
    }

    /**
     * Обновление данных КП. user_id и usd_value не меняются.
     */
    public function update(UpdateProposalRequest $request, Proposal $proposal): RedirectResponse
    {
        $this->authorizeProposalAccess($proposal);

        $proposal->update($request->validated());

        return redirect()
            ->route('proposals.show', $proposal)
            ->with('success', 'КП успешно обновлено.');
    }

    /**
     * Мягкое удаление КП (без UI корзины на данный момент).
     */
    public function destroy(Proposal $proposal): RedirectResponse
    {
        $this->authorizeProposalAccess($proposal);

        $proposal->delete();

        return redirect()
            ->route('proposals.index')
            ->with('success', 'КП удалено.');
    }

    /**
     * Список сотрудников контрагента для селекта «Кому направлено КП».
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
