@extends('layouts.admin')

@section('title', 'Мой профиль')

@section('content_header')
    <h1>Мой профиль</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Данные аккаунта</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('profile.update') }}">
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
                    <label for="role">Роль</label>
                    <select id="role" name="role" class="form-control" disabled>
                        <option selected>{{ $user->role->title }}</option>
                    </select>
                    <small class="text-muted">Роль нельзя изменить.</small>
                </div>

                <button type="submit" class="btn btn-primary">Сохранить</button>
            </form>
        </div>
    </div>

    <div class="card card-warning mt-3">
        <div class="card-header">
            <h3 class="card-title">Смена пароля</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('profile.password') }}">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label for="current_password">Текущий пароль</label>
                    <input type="password" id="current_password" name="current_password"
                        class="form-control @error('current_password') is-invalid @enderror" required>
                    @error('current_password')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password">Новый пароль</label>
                    <input type="password" id="password" name="password"
                        class="form-control @error('password') is-invalid @enderror" required>
                    @error('password')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password_confirmation">Подтверждение пароля</label>
                    <input type="password" id="password_confirmation" name="password_confirmation"
                        class="form-control" required>
                </div>

                <button type="submit" class="btn btn-warning">Сменить пароль</button>
            </form>
        </div>
    </div>
@endsection
