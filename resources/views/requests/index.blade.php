@extends('layouts.admin')

@section('title', 'Запросы')

@section('content_header')
    <h1>Запросы</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Дата</th>
                        <th>Сотрудник</th>
                        <th>Контрагент</th>
                        <th>Статус</th>
                        <th>Менеджер</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $request)
                        <tr>
                            <td>
                                <a href="{{ route('requests.show', $request) }}">{{ $request->date?->format('d.m.Y') ?? '—' }}</a>
                            </td>
                            <td>{{ $request->employedPerson?->contactPerson?->fio ?? '—' }}</td>
                            <td>
                                <a href="{{ route('contractors.show', $request->employedPerson->contractor) }}">{{ $request->employedPerson?->contractor?->name ?? '—' }}</a>
                            </td>
                            <td>{{ \App\Models\Request::getStatuses()[$request->status] ?? $request->status ?? '—' }}</td>
                            <td>{{ $request->user?->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">Запросы не найдены.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $requests->links() }}
        </div>
    </div>
@endsection
