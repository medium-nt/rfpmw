@extends('layouts.admin')

@section('title', 'Артикулы')

@section('content_header')
    <h1>Артикулы</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="d-flex align-items-center flex-wrap">
                <a href="{{ route('items.create') }}" class="btn btn-primary btn-sm mr-0 mr-md-2 mb-2 mb-md-0">
                    <i class="fas fa-plus"></i> <span class="d-none d-md-inline">Добавить артикул</span>
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
                        <th>
                            <x-sort-link field="sku" title="SKU (артикул)" :current-field="$sortField" :current-direction="$sortDirection" />
                        </th>
                        <th>
                            <x-sort-link field="vendor" title="Вендор" :current-field="$sortField" :current-direction="$sortDirection" />
                        </th>
                        <th>Описание</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items as $item)
                        <tr>
                            <td><a href="{{ route('items.show', $item) }}">{{ $item->sku }}</a></td>
                            <td title="{{ $item->vendor?->name ?? '' }}">{{ \Illuminate\Support\Str::limit($item->vendor?->name ?? '—', 20) }}</td>
                            <td title="{{ $item->description ?? '' }}">{{ \Illuminate\Support\Str::limit($item->description ?? '—', 20) }}</td>
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
