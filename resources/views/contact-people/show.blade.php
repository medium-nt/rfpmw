@extends('layouts.admin')

@section('title', $person->fio)

@section('content_header')
    <h1>{{ $person->fio }}</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header d-flex flex-wrap align-items-center">
            @include('partials.back-button', ['fallbackRoute' => route('contact-people.index')])
            <a href="{{ route('contact-people.edit', $person) }}" class="btn btn-primary btn-sm">
                <i class="fas fa-edit"></i> Редактировать данные
            </a>
        </div>
        <div class="card-body">
            <div class="dl-horizontal-scroll">
            <dl class="row mb-0">
                <dt class="col-5 col-sm-3 col-md-2">ФИО</dt>
                <dd class="col-7 col-sm-9 col-md-10">{{ $person->fio }}</dd>

                <dt class="col-5 col-sm-3 col-md-2">Телефон</dt>
                <dd class="col-7 col-sm-9 col-md-10">{{ $person->phone ?? '—' }}</dd>

                <dt class="col-5 col-sm-3 col-md-2">Email</dt>
                <dd class="col-7 col-sm-9 col-md-10">{{ $person->email ?? '—' }}</dd>

                <dt class="col-5 col-sm-3 col-md-2">Доп.информация</dt>
                <dd class="col-7 col-sm-9 col-md-10">{{ $person->interests ?? '—' }}</dd>
            </dl>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header">
            <h3 class="card-title">Контрагенты</h3>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-bordered table-striped mb-0">
                <thead>
                    <tr>
                        <th>Контрагент</th>
                        <th>Тип</th>
                        <th style="width: 35%;">Должность</th>
                        <th>Менеджер</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($employments as $employed)
                        <tr>
                            <td>
                                <a href="{{ route('contractors.show', [$employed->contractor, 'from' => '/' . request()->path()]) }}">{{ $employed->contractor->name }}</a>
                            </td>
                            <td>{{ \App\Models\Contractor::getTypes()[$employed->contractor->type] ?? $employed->contractor->type }}</td>
                            <td>
                                <form method="POST" action="{{ route('employed-people.update', [$employed->contractor, $employed]) }}" class="d-flex">
                                    @csrf
                                    @method('PUT')
                                    <input type="text" name="position" class="form-control form-control-sm"
                                        value="{{ old('position', $employed->position) }}" placeholder="Должность">
                                </form>
                            </td>
                            <td>{{ $employed->contractor->user?->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted">Нет привязок к контрагентам.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($employments->isNotEmpty())
            <div class="card-footer">
                <small class="text-muted"><i class="fas fa-info-circle"></i> Должность сохраняется по нажатию Enter.</small>
            </div>
        @endif
    </div>
@endsection
