@extends('layouts.admin')

@section('title', 'Создание артикула')

@section('content_header')
    <h1>Создание артикула</h1>
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

            <form method="POST" action="{{ route('items.store') }}">
                @csrf

                <div class="form-group">
                    <label for="sku">SKU (артикул)</label>
                    <input type="text" id="sku" name="sku"
                        class="form-control @error('sku') is-invalid @enderror"
                        value="{{ old('sku') }}" required>
                    @error('sku')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="vendor_id">Вендор</label>
                    <select id="vendor_id" name="vendor_id"
                        class="form-control @error('vendor_id') is-invalid @enderror" required>
                        <option value="">— Выберите вендора —</option>
                        @foreach ($vendors as $id => $name)
                            <option value="{{ $id }}" @selected(old('vendor_id') == $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('vendor_id')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="description">Описание</label>
                    <textarea id="description" name="description"
                        class="form-control @error('description') is-invalid @enderror"
                        rows="3">{{ old('description') }}</textarea>
                    @error('description')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">Сохранить</button>
                <a href="{{ route('items.index') }}" class="btn btn-secondary">Отмена</a>
            </form>
        </div>
    </div>
@endsection
