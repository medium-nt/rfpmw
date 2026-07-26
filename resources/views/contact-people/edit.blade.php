@extends('layouts.admin')

@section('title', 'Редактирование: ' . $person->fio)

@section('content_header')
    <h1>Редактирование: {{ $person->fio }}</h1>
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

            <form method="POST" action="{{ route('contact-people.update', $person) }}">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label for="fio">ФИО <span class="text-danger">*</span></label>
                    <input type="text" id="fio" name="fio"
                        class="form-control @error('fio') is-invalid @enderror"
                        value="{{ old('fio', $person->fio) }}" required>
                    @error('fio')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-row">
                    <div class="form-group col-12 col-md-6">
                        <label for="phone">Телефон</label>
                        <input type="text" id="phone" name="phone"
                            class="form-control @error('phone') is-invalid @enderror"
                            value="{{ old('phone', $person->phone) }}">
                        @error('phone')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group col-12 col-md-6">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email"
                            class="form-control @error('email') is-invalid @enderror"
                            value="{{ old('email', $person->email) }}">
                        @error('email')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="form-group">
                    <label for="interests">Доп.информация</label>
                    <textarea id="interests" name="interests"
                        class="form-control @error('interests') is-invalid @enderror"
                        rows="3">{{ old('interests', $person->interests) }}</textarea>
                    @error('interests')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">Сохранить</button>
                <a href="{{ route('contact-people.show', $person) }}" class="btn btn-secondary">Отмена</a>
            </form>
        </div>
    </div>
@endsection
