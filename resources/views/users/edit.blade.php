@extends('layouts.admin')

@section('title', 'Редактирование пользователя')

@section('content_header')
    <h1>Редактирование пользователя</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('users.update', $user) }}">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label for="name">Имя</label>
                    <input type="text" id="name" name="name"
                        class="form-control @error('name') is-invalid @enderror"
                        value="{{ old('name', $user->name) }}" required>
                    @error('name')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="username">Логин <span class="text-danger">*</span></label>
                    <input type="text" id="username" name="username"
                        class="form-control @error('username') is-invalid @enderror"
                        value="{{ old('username', $user->username) }}" required>
                    @error('username')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="email">Email (опционально)</label>
                    <input type="email" id="email" name="email"
                        class="form-control @error('email') is-invalid @enderror"
                        value="{{ old('email', $user->email) }}" placeholder="оставьте пустым, чтобы не указывать">
                    @error('email')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password">Пароль</label>
                    <input type="password" id="password" name="password"
                        class="form-control @error('password') is-invalid @enderror"
                        placeholder="оставьте пустым, чтобы не менять">
                    @error('password')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password_confirmation">Подтверждение пароля</label>
                    <input type="password" id="password_confirmation" name="password_confirmation"
                        class="form-control">
                </div>

                <div class="form-group">
                    <label for="role_id">Роль</label>
                    <select id="role_id" name="role_id" class="form-control" disabled>
                        @foreach ($roles as $id => $title)
                            <option value="{{ $id }}" @selected($user->role_id == $id)>{{ $title }}</option>
                        @endforeach
                    </select>
                    <small class="text-muted">Роль нельзя изменить после создания.</small>
                </div>

                <button type="submit" class="btn btn-primary">Сохранить</button>
                <a href="{{ route('users.index') }}" class="btn btn-secondary">Отмена</a>
            </form>
        </div>
    </div>

    @can('is-admin')
    <div class="card card-danger mt-3">
        <div class="card-header">
            <h3 class="card-title">Удаление пользователя</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('users.destroy', $user) }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Удалить
                </button>
            </form>
        </div>
    </div>
    @endcan
@endsection
