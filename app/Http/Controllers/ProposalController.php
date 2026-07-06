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
     */
    public function index(): View
    {
        $proposals = Proposal::query()
            ->with(['employedPerson.contactPerson', 'employedPerson.contractor', 'user'])
            ->when(auth()->user()->isManager(), function ($q): void {
                $q->whereHas('employedPerson.contractor', fn ($qq) => $qq->where('user_id', auth()->id()));
            })
            ->when(request('from'), fn ($q) => $q->where('date', '>=', request('from')))
            ->when(request('to'), fn ($q) => $q->where('date', '<=', request('to')))
            ->orderBy('id')
            ->paginate(10)
            ->appends(['from' => request('from'), 'to' => request('to')]);

        return view('proposals.index', compact('proposals'));
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
