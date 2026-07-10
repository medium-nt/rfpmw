@extends('layouts.admin')

@section('title', 'Контрагенты')

@section('content_header')
    <h1>Контрагенты</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="d-flex align-items-center flex-wrap">
                <a href="{{ route('contractors.create') }}" class="btn btn-primary btn-sm mb-2 mr-2">
                    <i class="fas fa-plus"></i> <span class="d-none d-md-inline">Добавить контрагента</span>
                </a>
                <form action="{{ route('contractors.index') }}" method="get" class="form-inline mb-2 flex-fill ml-md-auto">
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                        <input type="text" name="q" value="{{ request('q') }}"
                               class="form-control" placeholder="Название, ИНН или адрес...">
                        @if (request('q'))
                            <div class="input-group-append">
                                <a href="{{ route('contractors.index') }}" class="btn btn-outline-secondary" title="Очистить">
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
                            <x-sort-link field="name" title="Название" :current-field="$sortField" :current-direction="$sortDirection" />
                        </th>
                        <th class="d-none d-lg-table-cell">Головной контрагент</th>
                        <th>Факт адрес</th>
                        <th class="d-none d-lg-table-cell">Сайт</th>
                        <th>
                            <x-sort-link field="inn" title="ИНН" :current-field="$sortField" :current-direction="$sortDirection" />
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($contractors as $contractor)
                        <tr>
                            <td><a href="{{ route('contractors.show', $contractor) }}" title="{{ $contractor->name }}">{{ \Illuminate\Support\Str::limit($contractor->name, 20) }}</a></td>
                            <td class="d-none d-lg-table-cell">
                                @if ($contractor->parent)
                                    <a href="{{ route('contractors.show', $contractor->parent) }}">{{ $contractor->parent->name }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if ($contractor->actual_address)
                                    <span class="d-lg-none" title="{{ $contractor->actual_address }}">{{ \Illuminate\Support\Str::limit($contractor->actual_address, 20) }}</span>
                                    <span class="d-none d-lg-inline" title="{{ $contractor->actual_address }}">{{ \Illuminate\Support\Str::limit($contractor->actual_address, 30) }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="d-none d-lg-table-cell">
                                @if ($contractor->website)
                                    <a href="{{ $contractor->website }}" target="_blank" title="{{ $contractor->website }}">{{ \Illuminate\Support\Str::limit($contractor->website, 35) }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $contractor->inn }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $contractors->links() }}
        </div>
    </div>
@endsection
