@extends('layouts.admin')

@section('title', 'Редактирование позиции')

@section('content_header')
    <h1>Редактирование позиции</h1>
    <p class="text-muted mb-0">
        Запрос: <a href="{{ route('requests.show', $request) }}">№{{ $request->id }}</a>
    </p>
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

            <form method="POST" action="{{ route('request-items.update', [$request, $requestItem]) }}">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label>Артикул</label>
                    <input type="text" class="form-control" value="{{ ($requestItem->item?->sku ?? '—') . ' — ' . ($requestItem->item?->vendor?->name ?? 'без вендора') }}" disabled>
                    @if ($requestItem->item?->trashed())
                        <small class="text-warning">Артикул удалён из справочника (в позиции сохранён для истории).</small>
                    @else
                        <small class="text-muted">Артикул позиции не изменяется.</small>
                    @endif
                </div>

                <div class="form-row">
                    <div class="form-group col-12 col-md-6">
                        <label for="quantity">Количество</label>
                        <input type="number" id="quantity" name="quantity" min="1"
                            class="form-control @error('quantity') is-invalid @enderror"
                            value="{{ old('quantity', $requestItem->quantity) }}" required>
                        @error('quantity')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group col-12 col-md-6">
                        <label for="price">Цена, USD</label>
                        <input type="number" id="price" name="price" min="0" step="0.01"
                            class="form-control @error('price') is-invalid @enderror"
                            value="{{ old('price', $requestItem->price) }}">
                        @error('price')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Сохранить</button>
                <a href="{{ route('requests.show', $request) }}" class="btn btn-secondary">Отмена</a>
            </form>
        </div>
    </div>
@endsection
