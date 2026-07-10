@extends('layouts.admin')

@section('title', 'Коммерческие предложения')

@section('content_header')
    <h1>Коммерческие предложения</h1>
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
                <form action="{{ route('proposals.index') }}" method="get" class="form-inline mb-2 mb-md-0">
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
                    @forelse ($proposals as $proposal)
                        <tr>
                            <td>
                                <a href="{{ route('proposals.show', $proposal) }}">{{ $proposal->date?->format('d.m.Y') ?? '—' }}</a>
                            </td>
                            <td title="{{ $proposal->employedPerson?->contactPerson?->fio ?? '' }}">{{ \Illuminate\Support\Str::limit($proposal->employedPerson?->contactPerson?->fio ?? '—', 20) }}</td>
                            <td>
                                @if ($proposal->employedPerson?->contractor)
                                    <a href="{{ route('contractors.show', $proposal->employedPerson->contractor) }}" title="{{ $proposal->employedPerson->contractor->name }}">{{ \Illuminate\Support\Str::limit($proposal->employedPerson->contractor->name, 20) }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ \App\Models\Proposal::getStatuses()[$proposal->status] ?? $proposal->status ?? '—' }}</td>
                            <td title="{{ $proposal->user?->name ?? '' }}">{{ \Illuminate\Support\Str::limit($proposal->user?->name ?? '—', 20) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">Коммерческие предложения не найдены.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $proposals->links() }}
        </div>
    </div>
@endsection

@push('js')
    <script src="{{ asset('js/page-query-param.js') }}"></script>
@endpush
