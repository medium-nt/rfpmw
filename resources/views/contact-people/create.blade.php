@extends('layouts.admin')

@section('title', 'Новое контактное лицо')

@section('content_header')
    <h1>Новое контактное лицо</h1>
    <p class="text-muted mb-0">для контрагента «{{ $contractor->name }}»</p>
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

            <form method="POST" action="{{ route('contact-people.store', $contractor) }}">
                @csrf

                <div class="form-row">
                    <div class="form-group col-12 col-md-6">
                        <label for="fio">ФИО <span class="text-danger">*</span></label>
                        <input type="text" id="fio" name="fio"
                            class="form-control @error('fio') is-invalid @enderror"
                            value="{{ old('fio') }}" required>
                        @error('fio')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group col-12 col-md-6">
                        <label for="position">Должность</label>
                        <input type="text" id="position" name="position"
                            class="form-control @error('position') is-invalid @enderror"
                            value="{{ old('position') }}">
                        @error('position')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-12 col-md-6">
                        <label for="phone">Телефон</label>
                        <input type="text" id="phone" name="phone"
                            class="form-control @error('phone') is-invalid @enderror"
                            value="{{ old('phone') }}">
                        @error('phone')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group col-12 col-md-6">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email"
                            class="form-control @error('email') is-invalid @enderror"
                            value="{{ old('email') }}">
                        @error('email')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-12 col-md-6">
                        <label for="birth_date">Дата рождения</label>
                        <input type="date" id="birth_date" name="birth_date"
                            class="form-control @error('birth_date') is-invalid @enderror"
                            value="{{ old('birth_date') }}" max="{{ now()->format('Y-m-d') }}">
                        @error('birth_date')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="form-group">
                    <label for="interests">Доп.информация</label>
                    <textarea id="interests" name="interests"
                        class="form-control @error('interests') is-invalid @enderror"
                        rows="3">{{ old('interests') }}</textarea>
                    @error('interests')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-check"></i> Создать и привязать
                </button>
                <a href="{{ route('contractors.edit', $contractor) }}" class="btn btn-secondary">Отмена</a>
            </form>
        </div>
    </div>
@endsection
