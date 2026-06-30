<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateNewContactPersonRequest;
use App\Http\Requests\UpdateContactPersonRequest;
use App\Models\ContactPerson;
use App\Models\Contractor;
use App\Models\EmployedPerson;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ContactPersonController extends Controller
{
    /**
     * Read-only список контактных лиц: админ видит всех, менеджер — только людей своих контрагентов.
     *
     * Колонка «Контрагенты» для менеджера также скоупирована: у лица, работающего в нескольких
     * компаниях, показываются только контрагенты этого менеджера (изоляция от чужих клиентов).
     */
    public function index(): View
    {
        $people = ContactPerson::query()
            ->when(auth()->user()->isManager(), function ($q): void {
                $q->whereHas('employedPeople', fn ($qq) => $qq->whereRelation('contractor', 'user_id', auth()->id()));
            })
            ->with(['employedPeople' => function ($q): void {
                $q->with('contractor')
                    ->when(auth()->user()->isManager(), fn ($qq) => $qq->whereHas('contractor', fn ($c) => $c->where('user_id', auth()->id())));
            }])
            ->orderBy('id')
            ->paginate(10);

        return view('contact-people.index', compact('people'));
    }

    /**
     * Карточка контактного лица с перечнем его контрагентов и должностей.
     *
     * Менеджер видит только привязки к своим контрагентам (изоляция данных).
     */
    public function show(ContactPerson $person): View
    {
        $this->authorizeContactPersonAccess($person);

        $employments = $person->employedPeople()
            ->when(auth()->user()->isManager(), fn ($q) => $q->whereHas('contractor', fn ($qq) => $qq->where('user_id', auth()->id())))
            ->with('contractor.user')
            ->get();

        return view('contact-people.show', compact('person', 'employments'));
    }

    /**
     * Форма редактирования личных данных контактного лица.
     */
    public function edit(ContactPerson $person): View
    {
        $this->authorizeContactPersonAccess($person);

        return view('contact-people.edit', compact('person'));
    }

    /**
     * Обновление личных данных контактного лица (должность правится отдельно как атрибут связи).
     */
    public function update(UpdateContactPersonRequest $request, ContactPerson $person): RedirectResponse
    {
        $this->authorizeContactPersonAccess($person);

        $person->update($request->validated());

        return redirect()
            ->route('contact-people.show', $person)
            ->with('success', 'Данные контактного лица обновлены.');
    }

    /**
     * Форма создания нового контактного лица в контексте контрагента (с немедленной привязкой).
     */
    public function create(Contractor $contractor): View
    {
        $this->authorizeContractorAccess($contractor);

        return view('contact-people.create', compact('contractor'));
    }

    /**
     * Сохранение нового контактного лица и его привязка к контрагенту (одной транзакцией).
     */
    public function store(CreateNewContactPersonRequest $request, Contractor $contractor): RedirectResponse
    {
        $this->authorizeContractorAccess($contractor);

        $data = $request->validated();
        $position = $data['position'] ?? null;

        DB::transaction(function () use ($data, $position, $contractor): void {
            $person = ContactPerson::create([
                'fio' => $data['fio'],
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'interests' => $data['interests'] ?? null,
            ]);

            EmployedPerson::create([
                'contact_person_id' => $person->id,
                'contractor_id' => $contractor->id,
                'position' => $position,
            ]);
        });

        return redirect()
            ->route('contractors.show', $contractor)
            ->with('success', 'Контактное лицо создано и привязано.');
    }
}
