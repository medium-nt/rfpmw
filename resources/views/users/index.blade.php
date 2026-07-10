@extends('layouts.admin')

@section('title', 'Пользователи')

@section('content_header')
    <h1>Пользователи</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="d-flex align-items-center flex-wrap">
                <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm mr-0 mr-md-2 mb-2 mb-md-0">
                    <i class="fas fa-plus"></i> <span class="d-none d-md-inline">Добавить пользователя</span>
                </a>
                <form action="{{ route('users.index') }}" method="get" class="form-inline mb-2 ml-auto">
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                        <input type="text" name="q" value="{{ request('q') }}"
                               class="form-control" placeholder="Имя или логин...">
                        @if (request('q'))
                            <div class="input-group-append">
                                <a href="{{ route('users.index') }}" class="btn btn-outline-secondary" title="Очистить">
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
                        <th>Имя</th>
                        <th>Логин</th>
                        <th>Роль</th>
                        <th class="text-right">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td title="{{ $user->name }}">{{ \Illuminate\Support\Str::limit($user->name, 20) }}</td>
                            <td>{{ $user->username }}</td>
                            <td>{{ $user->role->title }}</td>
                            <td class="text-right">
                                <a href="{{ route('users.edit', $user) }}" class="btn btn-info btn-sm">
                                    <i class="fas fa-edit"></i> Редактировать
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $users->links() }}
        </div>
    </div>
@endsection
