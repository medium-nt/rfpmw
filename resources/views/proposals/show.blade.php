@extends('layouts.admin')

@section('title', 'КП №' . $proposal->id)

@section('content_header')
    <h1>КП №{{ $proposal->id }}</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header d-flex flex-wrap align-items-center">
            <a href="{{ route('proposals.index') }}" class="btn btn-default btn-sm mr-2">
                <i class="fas fa-arrow-left"></i> К списку
            </a>
            <a href="{{ route('proposals.edit', $proposal) }}" class="btn btn-primary btn-sm">
                <i class="fas fa-edit"></i> Редактировать
            </a>
        </div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3 col-md-2">Дата</dt>
                <dd class="col-sm-9 col-md-10">{{ $proposal->date?->format('d.m.Y') ?? '—' }}</dd>

                <dt class="col-sm-3 col-md-2">Статус</dt>
                <dd class="col-sm-9 col-md-10">
                    @if ($proposal->status)
                        <span class="badge badge-info">{{ \App\Models\Proposal::getStatuses()[$proposal->status] ?? $proposal->status }}</span>
                    @else
                        —
                    @endif
                </dd>

                <dt class="col-sm-3 col-md-2">Сотрудник</dt>
                <dd class="col-sm-9 col-md-10">
                    @if ($proposal->employedPerson)
                        {{ $proposal->employedPerson->contactPerson?->fio ?? '—' }}
                        @if ($proposal->employedPerson->position)
                            <span class="text-muted">({{ $proposal->employedPerson->position }})</span>
                        @endif
                    @else
                        —
                    @endif
                </dd>

                <dt class="col-sm-3 col-md-2">Контрагент</dt>
                <dd class="col-sm-9 col-md-10">
                    <a href="{{ route('contractors.show', $proposal->employedPerson->contractor) }}">{{ $proposal->employedPerson->contractor?->name }}</a>
                </dd>

                <dt class="col-sm-3 col-md-2">Менеджер</dt>
                <dd class="col-sm-9 col-md-10">{{ $proposal->user?->name ?? '—' }}</dd>

                <dt class="col-sm-3 col-md-2">Сумма, USD</dt>
                <dd class="col-sm-9 col-md-10">{{ number_format((float) $proposal->usd_value, 2, '.', ' ') }}</dd>
            </dl>
        </div>
    </div>

    {{-- Заготовка: позиции КП (Items) будут добавлены отдельной задачей. --}}
    {{-- <div class="card card-info card-outline mt-3"> ... Позиции КП ... </div> --}}

    <div class="card card-danger mt-3">
        <div class="card-header">
            <h3 class="card-title">Удаление КП</h3>
        </div>
        <div class="card-body">
            <p>КП будет перемещено в корзину (мягкое удаление).</p>
            <form method="POST" action="{{ route('proposals.destroy', $proposal) }}" onsubmit="return confirm(@js('Удалить КП №' . $proposal->id . '?'));">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Удалить
                </button>
            </form>
        </div>
    </div>
@endsection
