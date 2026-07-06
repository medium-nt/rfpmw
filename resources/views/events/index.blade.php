@extends('layouts.admin')

@section('title', 'События')

@section('content_header')
    <h1>События</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <form method="get" class="form-inline">
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
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Дата</th>
                        <th>Тип</th>
                        <th>Сотрудник</th>
                        <th>Контрагент</th>
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
                            <td>{{ $event->employedPerson?->contactPerson?->fio ?? '—' }}</td>
                            <td>
                                <a href="{{ route('contractors.show', $event->employedPerson->contractor) }}">{{ $event->employedPerson?->contractor?->name ?? '—' }}</a>
                            </td>
                            <td>{{ $event->subject ?? '—' }}</td>
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
                            <td>{{ $event->user?->name ?? '—' }}</td>
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
