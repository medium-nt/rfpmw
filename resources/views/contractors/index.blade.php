@extends('layouts.admin')

@section('title', 'Контрагенты')

@section('content_header')
    <h1>Контрагенты</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="d-flex align-items-center flex-wrap">
                <a href="{{ route('contractors.create') }}" class="btn btn-primary btn-sm mb-2 mr-2">
                    <i class="fas fa-plus"></i> Добавить контрагента
                </a>
                @if (auth()->user()->isAdmin())
                    <a href="{{ route('contractors.trashed') }}" class="btn btn-default btn-sm mb-2 mr-2">
                        <i class="fas fa-trash"></i> Корзина
                    </a>
                @endif
                <form action="{{ route('contractors.index') }}" method="get" class="form-inline mb-2 ml-auto">
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                        <input type="text" name="q" value="{{ request('q') }}"
                               class="form-control" placeholder="Название или ИНН...">
                        @if (request('q'))
                            <div class="input-group-append">
                                <a href="{{ route('contractors.index') }}" class="btn btn-outline-secondary" title="Очистить">
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
