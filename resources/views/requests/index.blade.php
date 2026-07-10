@extends('layouts.admin')

@section('title', 'Запросы')

@section('content_header')
    <h1>Запросы</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
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
                <form action="{{ route('requests.index') }}" method="get" class="form-inline mb-2 mb-md-0">
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                        <input type="text" name="q" value="{{ request('q') }}"
                               onchange="updatePageWithQueryParam(this)"
                               onkeydown="if(event.key==='Enter'){event.preventDefault();updatePageWithQueryParam(this);}"
                               class="form-control" placeholder="Заказчик...">
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
                            <x-sort-link field="date" title="Дата" :current-field="$sortField" :current-direction="$sortDirection" />
                        </th>
                        <th>Сотрудник</th>
                        <th>
                            <x-sort-link field="contractor" title="Контрагент" :current-field="$sortField" :current-direction="$sortDirection" />
                        </th>
                        <th>Статус</th>
                        <th>Менеджер</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $request)
                        <tr>
                            <td>
                                <a href="{{ route('requests.show', $request) }}">{{ $request->date?->format('d.m.Y') ?? '—' }}</a>
                            </td>
                            <td title="{{ $request->employedPerson?->contactPerson?->fio ?? '' }}">{{ \Illuminate\Support\Str::limit($request->employedPerson?->contactPerson?->fio ?? '—', 20) }}</td>
                            <td>
                                <a href="{{ route('contractors.show', $request->employedPerson->contractor) }}" title="{{ $request->employedPerson?->contractor?->name ?? '' }}">{{ \Illuminate\Support\Str::limit($request->employedPerson?->contractor?->name ?? '—', 20) }}</a>
                            </td>
                            <td>{{ \App\Models\Request::getStatuses()[$request->status] ?? $request->status ?? '—' }}</td>
                            <td title="{{ $request->user?->name ?? '' }}">{{ \Illuminate\Support\Str::limit($request->user?->name ?? '—', 20) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">Запросы не найдены.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $requests->links() }}
        </div>
    </div>
@endsection

@push('js')
    <script src="{{ asset('js/page-query-param.js') }}"></script>
@endpush
