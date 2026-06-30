@extends('layouts.admin')

@section('title', 'Запрос №' . $request->id)

@section('content_header')
    <h1>Запрос №{{ $request->id }}</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header d-flex flex-wrap align-items-center">
            <a href="{{ route('requests.index') }}" class="btn btn-default btn-sm mr-2">
                <i class="fas fa-arrow-left"></i> К списку
            </a>
            <a href="{{ route('requests.edit', $request) }}" class="btn btn-primary btn-sm">
                <i class="fas fa-edit"></i> Редактировать
            </a>
        </div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3 col-md-2">Дата</dt>
                <dd class="col-sm-9 col-md-10">{{ $request->date?->format('d.m.Y') ?? '—' }}</dd>

                <dt class="col-sm-3 col-md-2">Статус</dt>
                <dd class="col-sm-9 col-md-10">
                    @if ($request->status)
                        <span class="badge badge-info">{{ \App\Models\Request::getStatuses()[$request->status] ?? $request->status }}</span>
                    @else
                        —
                    @endif
                </dd>

                <dt class="col-sm-3 col-md-2">Сотрудник</dt>
                <dd class="col-sm-9 col-md-10">
                    @if ($request->employedPerson)
                                        {{ $request->employedPerson->contactPerson?->fio ?? '—' }}
                        @if ($request->employedPerson->position)
                                            <span class="text-muted">({{ $request->employedPerson->position }})</span>
                        @endif
                    @else
                        —
                    @endif
                </dd>

                <dt class="col-sm-3 col-md-2">Контрагент</dt>
                <dd class="col-sm-9 col-md-10">
                    <a href="{{ route('contractors.show', $request->employedPerson->contractor) }}">{{ $request->employedPerson->contractor?->name }}</a>
                </dd>

                <dt class="col-sm-3 col-md-2">Менеджер</dt>
                <dd class="col-sm-9 col-md-10">{{ $request->user?->name ?? '—' }}</dd>

                <dt class="col-sm-3 col-md-2">Сумма, USD</dt>
                <dd class="col-sm-9 col-md-10">{{ number_format((float) $request->usd_value, 2, '.', ' ') }}</dd>
            </dl>
        </div>
    </div>

    {{-- Заготовка: позиции запроса (Items) будут добавлены отдельной задачей. --}}
    {{-- <div class="card card-info card-outline mt-3"> ... Позиции запроса ... </div> --}}

    <div class="card card-danger mt-3">
        <div class="card-header">
            <h3 class="card-title">Удаление запроса</h3>
        </div>
        <div class="card-body">
            <p>Запрос будет перемещён в корзину (мягкое удаление).</p>
            <form method="POST" action="{{ route('requests.destroy', $request) }}" onsubmit="return confirm(@js('Удалить запрос №' . $request->id . '?'));">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Удалить
                </button>
            </form>
        </div>
    </div>
@endsection
