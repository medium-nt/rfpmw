@extends('layouts.admin')

@section('title', 'Корзина контрагентов')

@section('content_header')
    <h1>Корзина контрагентов</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <a href="{{ route('contractors.index') }}" class="btn btn-default btn-sm">
                <i class="fas fa-arrow-left"></i> Назад к списку
            </a>
        </div>

        <div class="card-body table-responsive p-0">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Название</th>
                        <th>ИНН</th>
                        <th>Менеджер</th>
                        <th>Удалён</th>
                        <th class="text-right">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($contractors as $contractor)
                        <tr>
                            <td>{{ $contractor->id }}</td>
                            <td>{{ $contractor->name }}</td>
                            <td>{{ $contractor->inn }}</td>
                            <td>{{ $contractor->user?->name ?? '—' }}</td>
                            <td>{{ $contractor->deleted_at?->format('d.m.Y H:i') }}</td>
                            <td class="text-right">
                                <form method="POST" action="{{ route('contractors.restore', $contractor->id) }}" onsubmit="return confirm(@js('Восстановить контрагента «' . $contractor->name . '»?'));">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="fas fa-trash-restore"></i> Восстановить
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">Корзина пуста.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
