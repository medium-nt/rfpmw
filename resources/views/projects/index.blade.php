@extends('layouts.admin')

@section('title', 'Проекты')

@section('content_header')
    <h1>Проекты</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-end">
                <form action="{{ route('projects.index') }}" method="get" class="form-inline mb-2">
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                        <input type="text" name="q" value="{{ request('q') }}"
                               class="form-control" placeholder="Название...">
                        @if (request('q'))
                            <div class="input-group-append">
                                <a href="{{ route('projects.index') }}" class="btn btn-outline-secondary" title="Очистить">
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
                        <th>Название</th>
                        <th>Контрагент</th>
                        <th>Дата выхода в серию</th>
                        <th>Статус</th>
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
                            <td title="{{ $project->responsiblePerson?->contactPerson?->fio ?? '' }}">{{ \Illuminate\Support\Str::limit($project->responsiblePerson?->contactPerson?->fio ?? '—', 20) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">Проекты не найдены.</td>
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
