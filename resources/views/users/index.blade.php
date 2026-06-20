@extends('layouts.admin')

@section('title', 'Пользователи')

@section('content_header')
    <h1>Пользователи</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Добавить пользователя
            </a>
        </div>

        <div class="card-body table-responsive p-0">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Имя</th>
                        <th>Email</th>
                        <th>Роль</th>
                        <th class="text-right">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td>{{ $user->id }}</td>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
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
    </div>
@endsection
