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
                    <label for="legal_address">Юридический адрес</label>
                    <textarea id="legal_address" name="legal_address"
                        class="form-control @error('legal_address') is-invalid @enderror"
                        rows="3">{{ old('legal_address', $contractor->legal_address) }}</textarea>
                    @error('legal_address')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="actual_address">Фактический адрес</label>
                    <textarea id="actual_address" name="actual_address"
                        class="form-control @error('actual_address') is-invalid @enderror"
                        rows="3">{{ old('actual_address', $contractor->actual_address) }}</textarea>
                    @error('actual_address')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="phone">Телефон</label>
                    <input type="tel" id="phone" name="phone"
                        class="form-control @error('phone') is-invalid @enderror"
                        value="{{ old('phone', $contractor->phone) }}">
                    @error('phone')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="region">Город</label>
                    <input type="text" id="region" name="region"
                        class="form-control @error('region') is-invalid @enderror"
                        value="{{ old('region', $contractor->region) }}">
                    @error('region')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="industry">Отрасль</label>
                    <input type="text" id="industry" name="industry"
                        class="form-control @error('industry') is-invalid @enderror"
                        value="{{ old('industry', $contractor->industry) }}">
                    @error('industry')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="website">Сайт</label>
                    <input type="text" id="website" name="website"
                        class="form-control @error('website') is-invalid @enderror"
                        value="{{ old('website', $contractor->website) }}">
                    @error('website')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="parent_id">Головной контрагент</label>
                    <select id="parent_id" name="parent_id"
                        class="form-control @error('parent_id') is-invalid @enderror">
                        <option value="" @selected(blank(old('parent_id', $contractor->parent_id)))>— Нет (самостоятельный) —</option>
                        @foreach ($parents as $id => $name)
                            <option value="{{ $id }}" @selected(old('parent_id', $contractor->parent_id) == $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('parent_id')
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

    @include('partials.dadata-inn-search')
@endsection
