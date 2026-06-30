@extends('layouts.admin')

@section('title', 'Редактирование позиции')

@section('content_header')
    <h1>Редактирование позиции</h1>
    <p class="text-muted mb-0">
        Проект: <a href="{{ route('projects.show', $project) }}">{{ $project->name }}</a>
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

            <form method="POST" action="{{ route('project-items.update', [$project, $projectItem]) }}">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label>Артикул</label>
                    <input type="text" class="form-control" value="{{ ($projectItem->item?->sku ?? '—') . ' — ' . ($projectItem->item?->vendor?->name ?? 'без вендора') }}" disabled>
                    @if ($projectItem->item?->trashed())
                        <small class="text-warning">Артикул удалён из справочника (в позиции сохранён для истории).</small>
                    @else
                        <small class="text-muted">Артикул позиции не изменяется.</small>
                    @endif
                </div>

                <div class="form-row">
                    <div class="form-group col-12 col-md-3">
                        <label for="quantity">Количество</label>
                        <input type="number" id="quantity" name="quantity" min="1"
                            class="form-control @error('quantity') is-invalid @enderror"
                            value="{{ old('quantity', $projectItem->quantity) }}">
                        @error('quantity')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group col-12 col-md-3">
                        <label for="price">Цена, USD</label>
                        <input type="number" id="price" name="price" min="0" step="0.01"
                            class="form-control @error('price') is-invalid @enderror"
                            value="{{ old('price', $projectItem->price) }}">
                        @error('price')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group col-12 col-md-3">
                        <label for="status">Статус</label>
                        <select id="status" name="status" class="form-control @error('status') is-invalid @enderror">
                            <option value="">—</option>
                            @foreach (\App\Models\ProjectItem::getStatuses() as $key => $label)
                                <option value="{{ $key }}" @selected(old('status', $projectItem->status) == $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('status')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group col-12 col-md-3">
                        <label for="production_start_date">Дата начала пр-ва</label>
                        <input type="date" id="production_start_date" name="production_start_date"
                            class="form-control @error('production_start_date') is-invalid @enderror"
                            value="{{ old('production_start_date', $projectItem->production_start_date?->format('Y-m-d')) }}">
                        @error('production_start_date')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Сохранить</button>
                <a href="{{ route('projects.show', $project) }}" class="btn btn-secondary">Отмена</a>
            </form>
        </div>
    </div>
@endsection
