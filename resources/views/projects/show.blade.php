@extends('layouts.admin')

@section('title', $project->name)

@section('content_header')
    <h1>{{ $project->name }}</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header d-flex flex-wrap align-items-center">
            <a href="{{ route('projects.index') }}" class="btn btn-default btn-sm mr-2">
                <i class="fas fa-arrow-left"></i> К списку
            </a>
            <a href="{{ route('projects.edit', $project) }}" class="btn btn-primary btn-sm">
                <i class="fas fa-edit"></i> Редактировать
            </a>
        </div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3 col-md-2">Название</dt>
                <dd class="col-sm-9 col-md-10">{{ $project->name }}</dd>

                <dt class="col-sm-3 col-md-2">Контрагент</dt>
                <dd class="col-sm-9 col-md-10">
                    <a href="{{ route('contractors.show', $project->contractor) }}">{{ $project->contractor->name }}</a>
                </dd>

                <dt class="col-sm-3 col-md-2">Дата</dt>
                <dd class="col-sm-9 col-md-10">{{ $project->date?->format('d.m.Y') ?? '—' }}</dd>

                <dt class="col-sm-3 col-md-2">Статус</dt>
                <dd class="col-sm-9 col-md-10">
                    @if ($project->status)
                        <span class="badge badge-info">{{ \App\Models\Project::getStatuses()[$project->status] ?? $project->status }}</span>
                    @else
                        —
                    @endif
                </dd>

                <dt class="col-sm-3 col-md-2">Ответственный</dt>
                <dd class="col-sm-9 col-md-10">{{ $project->responsiblePerson?->contactPerson?->fio ?? '—' }}</dd>

                <dt class="col-sm-3 col-md-2">Сумма, USD</dt>
                <dd class="col-sm-9 col-md-10">{{ number_format((float) $project->usd_value, 2, '.', ' ') }}</dd>

                <dt class="col-sm-3 col-md-2">Описание</dt>
                <dd class="col-sm-9 col-md-10">{{ $project->description ?? '—' }}</dd>
            </dl>
        </div>
    </div>

    {{-- Заготовка: позиции проекта (Items) будут добавлены отдельной задачей. --}}
    {{-- <div class="card card-info card-outline mt-3"> ... Позиции проекта ... </div> --}}

    <div class="card card-info card-outline mt-3">
        <div class="card-header">
            <h3 class="card-title">События</h3>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-bordered table-striped mb-0">
                <thead>
                    <tr>
                        <th>Дата</th>
                        <th>Тип</th>
                        <th>Сотрудник</th>
                        <th>Тема</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($project->events as $event)
                        <tr>
                            <td>
                                <a href="{{ route('events.show', $event) }}">{{ $event->date?->format('d.m.Y') ?? '—' }}</a>
                            </td>
                            <td>{{ \App\Models\Event::getEventTypes()[$event->event_type] ?? $event->event_type }}</td>
                            <td>{{ $event->employedPerson?->contactPerson?->fio ?? '—' }}</td>
                            <td>{{ $event->subject ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-3">События отсутствуют.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card card-danger mt-3">
        <div class="card-header">
            <h3 class="card-title">Удаление проекта</h3>
        </div>
        <div class="card-body">
            <p>Проект будет перемещён в корзину (мягкое удаление).</p>
            <form method="POST" action="{{ route('projects.destroy', $project) }}" onsubmit="return confirm(@js('Удалить проект «' . $project->name . '»?'));">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Удалить
                </button>
            </form>
        </div>
    </div>
@endsection
