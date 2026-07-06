@extends('layouts.admin')

@section('title', 'Артикулы')

@section('content_header')
    <h1>Артикулы</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="d-flex align-items-center flex-wrap">
                <a href="{{ route('items.create') }}" class="btn btn-primary btn-sm mb-2 mr-2">
                    <i class="fas fa-plus"></i> Добавить артикул
                </a>
                <form action="{{ route('items.index') }}" method="get" class="form-inline mb-2 ml-auto">
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                        <input type="text" name="q" value="{{ request('q') }}"
                               class="form-control" placeholder="Артикул...">
                        @if (request('q'))
                            <div class="input-group-append">
                                <a href="{{ route('items.index') }}" class="btn btn-outline-secondary" title="Очистить">
                                    <i class="fas fa-times text-danger"></i>
                                </a>
                            </div>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <div class="card-body table-responsive p-0">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>SKU (артикул)</th>
                        <th>Вендор</th>
                        <th>Описание</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items as $item)
                        <tr>
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
