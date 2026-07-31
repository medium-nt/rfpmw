@extends('layouts.admin')

@section('title', 'Артикул')

@section('content_header')
    <h1>Артикул</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="table-responsive">
                    <table class="table table-bordered">
                        <tr>
                            <th width="30%">ID</th>
                            <td>{{ $item->id }}</td>
                        </tr>
                        <tr>
                            <th>SKU (артикул)</th>
                            <td>{{ $item->sku }}</td>
                        </tr>
                        <tr>
                            <th>Вендор</th>
                            <td>
                                @if ($item->vendor)
                                    @if ($item->vendor->trashed())
                                        <span class="text-muted">{{ $item->vendor->name }}</span>
                                        <span class="badge badge-secondary">удалён</span>
                                    @else
                                        <a href="{{ route('contractors.show', [$item->vendor, 'from' => '/' . request()->path()]) }}">{{ $item->vendor->name }}</a>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Описание</th>
                            <td><x-expandable-text :value="$item->description" /></td>
                        </tr>
                        <tr>
                            <th>Создан</th>
                            <td>{{ $item->created_at->format('d.m.Y H:i') }}</td>
                        </tr>
                        <tr>
                            <th>Обновлён</th>
                            <td>{{ $item->updated_at->format('d.m.Y H:i') }}</td>
                        </tr>
                    </table>
                    </div>
                </div>
            </div>

            <div class="mt-3">
                @can('is-admin')
                <a href="{{ route('items.edit', $item) }}" class="btn btn-warning">
                    <i class="fas fa-edit"></i> Изменить
                </a>
                <form method="POST" action="{{ route('items.destroy', $item) }}" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger" onclick="return confirm('Удалить артикул?')">Удалить</button>
                </form>
                @endcan
                @include('partials.back-button', ['fallbackRoute' => route('items.index')])
            </div>
        </div>
    </div>
@endsection
