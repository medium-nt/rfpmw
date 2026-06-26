@extends('layouts.admin')

@section('title', 'Редактирование контрагента')

@section('content_header')
    <h1>Редактирование контрагента</h1>
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

            <form method="POST" action="{{ route('contractors.update', $contractor) }}">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label for="name">Название</label>
                    <input type="text" id="name" name="name"
                        class="form-control @error('name') is-invalid @enderror"
                        value="{{ old('name', $contractor->name) }}" required>
                    @error('name')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="inn">ИНН</label>
                    <input type="text" id="inn" name="inn"
                        class="form-control @error('inn') is-invalid @enderror"
                        value="{{ old('inn', $contractor->inn) }}" required>
                    @error('inn')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="type">Тип</label>
                    <select id="type" name="type"
                        class="form-control @error('type') is-invalid @enderror" required>
                        @foreach ($types as $key => $label)
                            <option value="{{ $key }}" @selected(old('type', $contractor->type) == $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('type')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="address">Адрес</label>
                    <textarea id="address" name="address"
                        class="form-control @error('address') is-invalid @enderror"
                        rows="3">{{ old('address', $contractor->address) }}</textarea>
                    @error('address')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="website">Сайт</label>
                    <input type="url" id="website" name="website"
                        class="form-control @error('website') is-invalid @enderror"
                        value="{{ old('website', $contractor->website) }}">
                    @error('website')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="user_id">Менеджер</label>
                    <select id="user_id" name="user_id"
                        class="form-control @error('user_id') is-invalid @enderror"
                        @if (auth()->user()->isManager()) disabled @endif>
                        <option value="" @selected(blank(old('user_id', $contractor->user_id)))>— Без менеджера —</option>
                        @foreach ($managers as $id => $name)
                            <option value="{{ $id }}" @selected(old('user_id', $contractor->user_id) == $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('user_id')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">Сохранить</button>
                <a href="{{ route('contractors.show', $contractor) }}" class="btn btn-secondary">Отмена</a>
            </form>
        </div>
    </div>
@endsection
