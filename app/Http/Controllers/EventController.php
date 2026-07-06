<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Models\Contractor;
use App\Models\Event;
use App\Models\Project;
use App\Models\Proposal;
use App\Models\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EventController extends Controller
{
    /**
     * Список событий с data scoping: админ видит все, менеджер — только события своих контрагентов.
     */
    public function index(): View
    {
        $events = Event::query()
            ->with(['employedPerson.contactPerson', 'employedPerson.contractor', 'user', 'project', 'request', 'proposal'])
            ->when(auth()->user()->isManager(), function ($q): void {
                $q->whereHas('employedPerson.contractor', fn ($qq) => $qq->where('user_id', auth()->id()));
            })
            ->when(request('from'), fn ($q) => $q->where('date', '>=', request('from')))
            ->when(request('to'), fn ($q) => $q->where('date', '<=', request('to')))
            ->orderBy('id')
            ->paginate(10)
            ->appends(['from' => request('from'), 'to' => request('to')]);

        return view('events.index', compact('events'));
    }

    /**
     * Форма создания нового события в контексте контрагента.
     */
    public function create(Contractor $contractor): View
    {
        $this->authorizeContractorAccess($contractor);

        $employedPeople = $this->employedPeopleForSelect($contractor);
        $entities = $this->entitiesForSelect($contractor);

        return view('events.create', compact('contractor', 'employedPeople', 'entities'));
    }

    /**
     * Сохранение нового события. user_id — текущий пользователь.
     */
    public function store(StoreEventRequest $request, Contractor $contractor): RedirectResponse
    {
        $this->authorizeContractorAccess($contractor);

        $data = $request->validated();
        [$projectId, $requestId, $proposalId] = $this->parseLink($data['link'] ?? null);

        Event::create([
            'employed_person_id' => $data['employed_person_id'],
            'user_id' => auth()->id(),
            'event_type' => $data['event_type'],
            'date' => $data['date'],
            'subject' => $data['subject'] ?? null,
            'description' => $data['description'] ?? null,
            'project_id' => $projectId,
            'request_id' => $requestId,
            'proposal_id' => $proposalId,
        ]);

        return redirect()
            ->route('contractors.show', $contractor)
            ->with('success', 'Событие успешно создано.');
    }

    /**
     * Карточка события с сотрудником, контрагентом, менеджером и привязками.
     */
    public function show(Event $event): View
    {
        $this->authorizeEventAccess($event);

        $event->load(['user', 'employedPerson.contactPerson', 'employedPerson.contractor', 'project', 'request', 'proposal']);

        return view('events.show', compact('event'));
    }

    /**
     * Форма редактирования события с проверкой доступа менеджера.
     */
    public function edit(Event $event): View
    {
        $this->authorizeEventAccess($event);

        $contractor = $event->employedPerson->contractor;
        $employedPeople = $this->employedPeopleForSelect($contractor);
        $entities = $this->entitiesForSelect($contractor);
        $currentLink = $this->currentLink($event);

        return view('events.edit', compact('event', 'employedPeople', 'entities', 'currentLink'));
    }

    /**
     * Обновление данных события.
     */
    public function update(UpdateEventRequest $request, Event $event): RedirectResponse
    {
        $this->authorizeEventAccess($event);

        $data = $request->validated();
        [$data['project_id'], $data['request_id'], $data['proposal_id']] = $this->parseLink($data['link'] ?? null);
        unset($data['link']);

        $event->update($data);

        return redirect()
            ->route('events.show', $event)
            ->with('success', 'Событие успешно обновлено.');
    }

    /**
     * Мягкое удаление события (без UI корзины на данный момент).
     */
    public function destroy(Event $event): RedirectResponse
    {
        $this->authorizeEventAccess($event);

        $event->delete();

        return redirect()
            ->route('events.index')
            ->with('success', 'Событие удалено.');
    }

    /**
     * Список сотрудников контрагента для селекта «Сотрудник».
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
     * Опциональные привязки события (проект/запрос/КП) этого контрагента для селектов формы.
     *
     * @return array<string, array<int, string>>
     */
    protected function entitiesForSelect(Contractor $contractor): array
    {
        $employedIds = $contractor->employedPeople()->pluck('id');

        return [
            'projects' => $contractor->projects()
                ->orderBy('id')
                ->get()
                ->mapWithKeys(fn (Project $project) => [$project->id => $project->name])
                ->all(),
            'requests' => Request::query()
                ->whereIn('employed_person_id', $employedIds)
                ->orderBy('id')
                ->get()
                ->mapWithKeys(fn (Request $request) => [$request->id => "Запрос №{$request->id}"])
                ->all(),
            'proposals' => Proposal::query()
                ->whereIn('employed_person_id', $employedIds)
                ->orderBy('id')
                ->get()
                ->mapWithKeys(fn (Proposal $proposal) => [$proposal->id => "КП №{$proposal->id}"])
                ->all(),
        ];
    }

    /**
     * Разбирает строку привязки «type:id» в набор id для колонок project_id/request_id/proposal_id.
     *
     * @return array{0: ?int, 1: ?int, 2: ?int}
     */
    protected function parseLink(?string $link): array
    {
        if ($link && preg_match('/^(project|request|proposal):(\d+)$/', $link, $m)) {
            return match ($m[1]) {
                'project' => [(int) $m[2], null, null],
                'request' => [null, (int) $m[2], null],
                'proposal' => [null, null, (int) $m[2]],
            };
        }

        return [null, null, null];
    }

    /**
     * Текущая привязка события в формате «type:id» для предзаполнения селекта редактирования.
     */
    protected function currentLink(Event $event): ?string
    {
        if ($event->project_id) {
            return "project:{$event->project_id}";
        }
        if ($event->request_id) {
            return "request:{$event->request_id}";
        }
        if ($event->proposal_id) {
            return "proposal:{$event->proposal_id}";
        }

        return null;
    }
}
