@extends('layouts.admin')

@section('title', 'Проекты')

@section('content_header')
    <h1>Проекты</h1>
@endsection

@section('content')
    <div class="card">
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
                                <a href="{{ route('projects.show', $project) }}">{{ $project->name }}</a>
                            </td>
                            <td>
                                <a href="{{ route('contractors.show', $project->contractor) }}">{{ $project->contractor->name }}</a>
                            </td>
                            <td>{{ $project->date?->format('d.m.Y') ?? '—' }}</td>
                            <td>{{ \App\Models\Project::getStatuses()[$project->status] ?? $project->status ?? '—' }}</td>
                            <td>{{ $project->responsiblePerson?->contactPerson?->fio ?? '—' }}</td>
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
