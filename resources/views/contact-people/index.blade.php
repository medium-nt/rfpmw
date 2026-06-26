@extends('layouts.admin')

@section('title', 'Контактные лица')

@section('content_header')
    <h1>Контактные лица</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>ФИО</th>
                        <th>Телефон</th>
                        <th>Email</th>
                        <th>Контрагенты</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($people as $person)
                        <tr>
                            <td>{{ $person->id }}</td>
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
