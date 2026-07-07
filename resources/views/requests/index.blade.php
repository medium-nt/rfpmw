@extends('layouts.admin')

@section('title', 'Запросы')

@section('content_header')
    <h1>Запросы</h1>
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
                        <th>Сотрудник</th>
                        <th>Контрагент</th>
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
