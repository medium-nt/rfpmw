@extends('layouts.admin')

@section('title', 'Событие №' . $event->id)

@section('content_header')
    <h1>Событие №{{ $event->id }}</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header d-flex flex-wrap align-items-center">
            <a href="{{ route('events.index') }}" class="btn btn-default btn-sm mr-2">
                <i class="fas fa-arrow-left"></i> К списку
            </a>
            <a href="{{ route('events.edit', $event) }}" class="btn btn-primary btn-sm">
                <i class="fas fa-edit"></i> Редактировать
            </a>
        </div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3 col-md-2">Тип</dt>
                <dd class="col-sm-9 col-md-10">
                    <span class="badge badge-info">{{ \App\Models\Event::getEventTypes()[$event->event_type] ?? $event->event_type }}</span>
                </dd>

                <dt class="col-sm-3 col-md-2">Дата</dt>
                <dd class="col-sm-9 col-md-10">{{ $event->date?->format('d.m.Y') ?? '—' }}</dd>

                <dt class="col-sm-3 col-md-2">Сотрудник</dt>
                <dd class="col-sm-9 col-md-10">
                    @if ($event->employedPerson)
                        {{ $event->employedPerson->contactPerson?->fio ?? '—' }}
                        @if ($event->employedPerson->position)
                            <span class="text-muted">({{ $event->employedPerson->position }})</span>
                        @endif
                    @else
                        —
                    @endif
                </dd>

                <dt class="col-sm-3 col-md-2">Контрагент</dt>
                <dd class="col-sm-9 col-md-10">
                    <a href="{{ route('contractors.show', $event->employedPerson->contractor) }}">{{ $event->employedPerson->contractor?->name }}</a>
                </dd>

                <dt class="col-sm-3 col-md-2">Тема</dt>
                <dd class="col-sm-9 col-md-10">{{ $event->subject ?? '—' }}</dd>

                <dt class="col-sm-3 col-md-2">Описание</dt>
                <dd class="col-sm-9 col-md-10">{{ $event->description ?? '—' }}</dd>

                <dt class="col-sm-3 col-md-2">Менеджер</dt>
                <dd class="col-sm-9 col-md-10">{{ $event->user?->name ?? '—' }}</dd>

                <dt class="col-sm-3 col-md-2">Привязка</dt>
                <dd class="col-sm-9 col-md-10">
                    @if ($event->project)
                        <div><i class="fas fa-folder"></i> Проект: <a href="{{ route('projects.show', $event->project) }}">{{ $event->project->name }}</a></div>
                    @endif
                    @if ($event->request)
                        <div><i class="fas fa-inbox"></i> <a href="{{ route('requests.show', $event->request) }}">Запрос №{{ $event->request->id }}</a></div>
                    @endif
                    @if ($event->proposal)
                        <div><i class="fas fa-file-invoice-dollar"></i> <a href="{{ route('proposals.show', $event->proposal) }}">КП №{{ $event->proposal->id }}</a></div>
                    @endif
                    @unless ($event->project || $event->request || $event->proposal)
                        <span class="text-muted">—</span>
                    @endunless
                </dd>
            </dl>
        </div>
    </div>

    @can('is-admin')
    <div class="card card-danger mt-3">
        <div class="card-header">
            <h3 class="card-title">Удаление события</h3>
        </div>
        <div class="card-body">
            <p>Событие будет перемещено в корзину (мягкое удаление).</p>
            <form method="POST" action="{{ route('events.destroy', $event) }}" onsubmit="return confirm(@js('Удалить событие №' . $event->id . '?'));">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Удалить
                </button>
            </form>
        </div>
    </div>
    @endcan
@endsection
