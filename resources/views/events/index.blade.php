@extends('layouts.admin')

@section('title', 'События')

@section('content_header')
    <h1>События</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="d-flex flex-column flex-md-row align-items-center flex-wrap">
                <form method="get" class="form-inline mb-2 mb-md-0 w-100">
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
                <form action="{{ route('events.index') }}" method="get" class="form-inline mb-2 mb-md-0 w-100">
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                        <input type="text" name="q" value="{{ request('q') }}"
                               onchange="updatePageWithQueryParam(this)"
                               onkeydown="if(event.key==='Enter'){event.preventDefault();updatePageWithQueryParam(this);}"
                               class="form-control" placeholder="Тема, описание или заказчик...">
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
                        <th>Тип</th>
                        <th>
                            <x-sort-link field="employee" title="Сотрудник" :current-field="$sortField" :current-direction="$sortDirection" />
                        </th>
                        <th>
                            <x-sort-link field="contractor" title="Контрагент" :current-field="$sortField" :current-direction="$sortDirection" />
                        </th>
                        <th>Тема</th>
                        <th>Привязка</th>
                        <th>Менеджер</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($events as $event)
                        <tr>
                            <td>
                                <a href="{{ route('events.show', $event) }}">{{ $event->date?->format('d.m.Y') ?? '—' }}</a>
                            </td>
                            <td>{{ \App\Models\Event::getEventTypes()[$event->event_type] ?? $event->event_type }}</td>
                            <td title="{{ $event->employedPerson?->contactPerson?->fio ?? '' }}">{{ \Illuminate\Support\Str::limit($event->employedPerson?->contactPerson?->fio ?? '—', 20) }}</td>
                            <td>
                                <a href="{{ route('contractors.show', $event->employedPerson->contractor) }}" title="{{ $event->employedPerson?->contractor?->name ?? '' }}">{{ \Illuminate\Support\Str::limit($event->employedPerson?->contractor?->name ?? '—', 20) }}</a>
                            </td>
                            <td title="{{ $event->subject ?? '' }}">{{ \Illuminate\Support\Str::limit($event->subject ?? '—', 20) }}</td>
                            <td>
                                @if ($event->project)
                                    <a href="{{ route('projects.show', $event->project) }}"><i class="fas fa-folder"></i> {{ $event->project->name }}</a>
                                @elseif ($event->request)
                                    <a href="{{ route('requests.show', $event->request) }}"><i class="fas fa-inbox"></i> Запрос №{{ $event->request->id }}</a>
                                @elseif ($event->proposal)
                                    <a href="{{ route('proposals.show', $event->proposal) }}"><i class="fas fa-file-invoice-dollar"></i> КП №{{ $event->proposal->id }}</a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td title="{{ $event->user?->name ?? '' }}">{{ \Illuminate\Support\Str::limit($event->user?->name ?? '—', 20) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">События не найдены.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $events->links() }}
        </div>
    </div>
@endsection

@push('js')
    <script src="{{ asset('js/page-query-param.js') }}"></script>
@endpush
