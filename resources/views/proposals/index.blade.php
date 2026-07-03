@extends('layouts.admin')

@section('title', 'Коммерческие предложения')

@section('content_header')
    <h1>Коммерческие предложения</h1>
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
                    @forelse ($proposals as $proposal)
                        <tr>
                            <td>
                                <a href="{{ route('proposals.show', $proposal) }}">{{ $proposal->date?->format('d.m.Y') ?? '—' }}</a>
                            </td>
                            <td>{{ $proposal->employedPerson?->contactPerson?->fio ?? '—' }}</td>
                            <td>
                                <a href="{{ route('contractors.show', $proposal->employedPerson->contractor) }}">{{ $proposal->employedPerson?->contractor?->name ?? '—' }}</a>
                            </td>
                            <td>{{ \App\Models\Proposal::getStatuses()[$proposal->status] ?? $proposal->status ?? '—' }}</td>
                            <td>{{ $proposal->user?->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">Коммерческие предложения не найдены.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $proposals->links() }}
        </div>
    </div>
@endsection
