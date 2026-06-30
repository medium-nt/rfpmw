@extends('layouts.admin')

@section('title', 'Артикулы')

@section('content_header')
    <h1>Артикулы</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <a href="{{ route('items.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Добавить артикул
            </a>
        </div>

        <div class="card-body table-responsive p-0">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>SKU (артикул)</th>
                        <th>Вендор</th>
                        <th>Описание</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items as $item)
                        <tr>
                            <td>{{ $item->id }}</td>
                            <td><a href="{{ route('items.show', $item) }}">{{ $item->sku }}</a></td>
                            <td>{{ $item->vendor?->name ?? '—' }}</td>
                            <td>{{ Str::limit($item->description, 100) ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $items->links() }}
        </div>
    </div>
@endsection
