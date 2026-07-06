@extends('layouts.admin')

@section('title', 'Контактные лица')

@section('content_header')
    <h1>Контактные лица</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-end">
                <form action="{{ route('contact-people.index') }}" method="get" class="form-inline mb-2">
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                        <input type="text" name="q" value="{{ request('q') }}"
                               class="form-control" placeholder="ФИО, телефон или email...">
                        @if (request('q'))
                            <div class="input-group-append">
                                <a href="{{ route('contact-people.index') }}" class="btn btn-outline-secondary" title="Очистить">
                                    <i class="fas fa-times text-danger"></i>
                                </a>
                            </div>
                        @endif
                    </div>
                </form>
            </div>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ФИО</th>
                        <th>Телефон</th>
                        <th>Email</th>
                        <th>Контрагенты</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($people as $person)
                        <tr>
                            <td>
                                <a href="{{ route('contact-people.show', $person) }}">{{ $person->fio }}</a>
                            </td>
                            <td>{{ $person->phone ?? '—' }}</td>
                            <td>{{ $person->email ?? '—' }}</td>
                            <td>{{ $person->employedPeople->pluck('contractor.name')->filter()->join(', ') ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">Контактные лица не найдены.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $people->links() }}
        </div>
    </div>
@endsection
