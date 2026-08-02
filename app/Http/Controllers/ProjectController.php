<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Contractor;
use App\Models\Item;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProjectController extends Controller
{
    /**
     * Список проектов с data scoping: админ видит все, менеджер — только проекты своих контрагентов.
     * Сортировка по клику на заголовок: ?sort=name|contractor&direction=asc|desc.
     * По умолчанию — по названию контрагента (asc).
     */
    public function index(): View
    {
        $allowedSortFields = ['name', 'contractor'];
        $sortField = request('sort', 'contractor');
        $sortDirection = request('direction', 'asc');

        if (! in_array($sortField, $allowedSortFields)) {
            $sortField = 'contractor';
        }
        if (! in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'asc';
        }

        $projects = Project::query()
            ->with(['contractor.user', 'responsiblePerson.contactPerson'])
            ->whereHas('contractor', fn ($q) => $q->whereNull('contractors.deleted_at'))
            ->when(auth()->user()->isManager(), function ($q): void {
                $q->whereHas('contractor', fn ($qq) => $qq->where('user_id', auth()->id()));
            })
            ->when(request('q'), function ($query, $q): void {
                $query->where(function ($qq) use ($q): void {
                    $qq->where('projects.name', 'like', '%'.$q.'%')
                        ->orWhereHas('contractor', fn ($c) => $c->where('contractors.name', 'like', '%'.$q.'%'));
                });
            })
            ->when(request('from'), fn ($q) => $q->where('date', '>=', request('from')))
            ->when(request('to'), fn ($q) => $q->where('date', '<=', request('to')))
            ->when($sortField === 'contractor', function ($query) use ($sortDirection): void {
                // Сортировка по имени контрагента (прямая связь contractor_id, дублей нет)
                $query->leftJoin('contractors', 'projects.contractor_id', '=', 'contractors.id')
                    ->select('projects.*')
                    ->orderBy('contractors.name', $sortDirection);
            }, function ($query) use ($sortDirection): void {
                $query->orderBy('name', $sortDirection);
            })
            ->paginate(10)
            ->appends(['from' => request('from'), 'to' => request('to'), 'q' => request('q'), 'sort' => $sortField, 'direction' => $sortDirection]);

        return view('projects.index', compact('projects', 'sortField', 'sortDirection'));
    }

    /**
     * Форма создания нового проекта в контексте контрагента.
     */
    public function create(Contractor $contractor): View
    {
        $this->authorizeContractorAccess($contractor);

        $responsiblePeople = $this->responsiblePeopleForSelect($contractor);

        return view('projects.create', compact('contractor', 'responsiblePeople'));
    }

    /**
     * Сохранение нового проекта с привязкой к контрагенту. usd_value не вводится вручную (кэш позиций, см. observer TODO).
     */
    public function store(StoreProjectRequest $request, Contractor $contractor): RedirectResponse
    {
        $this->authorizeContractorAccess($contractor);

        $data = $request->validated();

        $project = Project::create([
            'contractor_id' => $contractor->id,
            'name' => $data['name'],
            'date' => $data['date'],
            'status' => $data['status'] ?? null,
            'description' => $data['description'] ?? null,
            'responsible_person_id' => $data['responsible_person_id'] ?? null,
        ]);

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'Проект успешно создан.');
    }

    /**
     * Карточка проекта с данными, ответственным, позициями и контрагентом.
     */
    public function show(Project $project): View
    {
        $this->authorizeProjectAccess($project);

        abort_if($project->contractor->trashed(), 404, 'Контрагент удалён.');

        $project->load(['contractor.user', 'responsiblePerson.contactPerson', 'events.employedPerson.contactPerson', 'events.user', 'projectItems.item.vendor']);

        $items = Item::forSelect();

        return view('projects.show', compact('project', 'items'));
    }

    /**
     * Форма редактирования проекта с проверкой доступа менеджера.
     */
    public function edit(Project $project): View
    {
        $this->authorizeProjectAccess($project);

        $responsiblePeople = $this->responsiblePeopleForSelect($project->contractor);

        return view('projects.edit', compact('project', 'responsiblePeople'));
    }

    /**
     * Обновление данных проекта. contractor_id и usd_value не меняются (последний — кэш позиций).
     */
    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        $this->authorizeProjectAccess($project);

        $project->update($request->validated());

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'Проект успешно обновлён.');
    }

    /**
     * Мягкое удаление проекта (без UI корзины на данный момент).
     */
    public function destroy(Project $project): RedirectResponse
    {
        $this->authorizeProjectAccess($project);

        $project->delete();

        return redirect()
            ->route('projects.index')
            ->with('success', 'Проект удалён.');
    }

    /**
     * Список сотрудников контрагента для селекта «Ответственный».
     *
     * @return array<int, string>
     */
    protected function responsiblePeopleForSelect(Contractor $contractor): array
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
