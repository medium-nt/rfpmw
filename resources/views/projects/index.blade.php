@extends('layouts.admin')

@section('title', 'Проекты')

@section('content_header')
    <h1>Проекты</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="search-bar d-flex flex-column flex-md-row justify-content-between align-items-md-center flex-wrap">
                <form method="get" class="form-inline mb-2 mb-md-0">
                    <label class="mr-2 mb-0">Дата от:</label>
                    <input type="date" name="from" value="{{ request('from') }}"
                           max="{{ request('to') }}"
                           onchange="updatePageWithQueryParam(this)"
                           class="form-control form-control-sm mr-3">
                    <label class="mr-2 mb-0">до:</label>
                    <input type="date" name="to" value="{{ request('to') }}"
                           min="{{ request('from') }}"
                           onchange="updatePageWithQueryParam(this)"
                           class="form-control form-control-sm">
                </form>
                <form action="{{ route('projects.index') }}" method="get" class="form-inline mb-2 mb-md-0">
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                        <input type="text" name="q" value="{{ request('q') }}"
                               onchange="updatePageWithQueryParam(this)"
                               onkeydown="if(event.key==='Enter'){event.preventDefault();updatePageWithQueryParam(this);}"
                               class="form-control" placeholder="Название или заказчик...">
                        @if (request('q'))
                            <div class="input-group-append">
                                <a href="{{ request()->fullUrlWithoutQuery('q') }}" class="btn btn-outline-secondary" title="Очистить">
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
                        <th>
                            <x-sort-link field="contractor" title="Контрагент" :current-field="$sortField" :current-direction="$sortDirection" />
                        </th>
                        <th>Дата выхода в серию</th>
                        <th>Статус</th>
                        <th class="text-right">Сумма, USD</th>
                        <th>Ответственный</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($projects as $project)
                        <tr>
                            <td>
                                <a href="{{ route('projects.show', $project) }}" title="{{ $project->name }}">{{ \Illuminate\Support\Str::limit($project->name, 20) }}</a>
                            </td>
                            <td>
                                <a href="{{ route('contractors.show', $project->contractor) }}" title="{{ $project->contractor->name }}">{{ \Illuminate\Support\Str::limit($project->contractor->name, 20) }}</a>
                            </td>
                            <td>{{ $project->date?->format('d.m.Y') ?? '—' }}</td>
                            <td>{{ \App\Models\Project::getStatuses()[$project->status] ?? $project->status ?? '—' }}</td>
                            <td class="text-right">{{ number_format((float) $project->usd_value, 2, '.', ' ') }}</td>
                            <td title="{{ $project->responsiblePerson?->contactPerson?->fio ?? '' }}">{{ \Illuminate\Support\Str::limit($project->responsiblePerson?->contactPerson?->fio ?? '—', 20) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">Проекты не найдены.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $projects->links() }}
        </div>
    </div>
@endsection

@push('js')
    <script src="{{ asset('js/page-query-param.js') }}"></script>
@endpush
