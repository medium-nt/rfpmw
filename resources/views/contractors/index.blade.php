@extends('layouts.admin')

@section('title', 'Контрагенты')

@section('content_header')
    <h1>Контрагенты</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <a href="{{ route('contractors.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Добавить контрагента
            </a>
            @if (auth()->user()->isAdmin())
                <a href="{{ route('contractors.trashed') }}" class="btn btn-default btn-sm">
                    <i class="fas fa-trash"></i> Корзина
                </a>
            @endif
        </div>

        <div class="card-body table-responsive p-0">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Название</th>
                        <th>ИНН</th>
                        <th class="d-none d-md-table-cell">Тип</th>
                        <th class="d-none d-md-table-cell">Менеджер</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($contractors as $contractor)
                        <tr>
                            <td><a href="{{ route('contractors.show', $contractor) }}">{{ $contractor->name }}</a></td>
                            <td>{{ $contractor->inn }}</td>
                            <td class="d-none d-md-table-cell">{{ \App\Models\Contractor::getTypes()[$contractor->type] ?? $contractor->type }}</td>
                            <td class="d-none d-md-table-cell">{{ $contractor->user?->name ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $contractors->links() }}
        </div>
    </div>
@endsection
