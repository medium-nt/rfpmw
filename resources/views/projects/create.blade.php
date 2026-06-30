@extends('layouts.admin')

@section('title', 'Новый проект')

@section('content_header')
    <h1>Новый проект</h1>
    <p class="text-muted mb-0">для контрагента «{{ $contractor->name }}»</p>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('projects.store', $contractor) }}">
                @csrf

                <div class="form-row">
                    <div class="form-group col-12 col-md-6">
                        <label for="name">Название <span class="text-danger">*</span></label>
                        <input type="text" id="name" name="name"
                            class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name') }}" required>
                        @error('name')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group col-12 col-md-3">
                        <label for="date">Дата выхода в серию <span class="text-danger">*</span></label>
                        <input type="date" id="date" name="date"
                            class="form-control @error('date') is-invalid @enderror"
                            value="{{ old('date') }}" required>
                        @error('date')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group col-12 col-md-3">
                        <label for="status">Статус</label>
                        <select id="status" name="status" class="form-control @error('status') is-invalid @enderror">
                            <option value="">— Не выбран —</option>
                            @foreach (\App\Models\Project::getStatuses() as $key => $label)
                                <option value="{{ $key }}" @selected(old('status') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('status')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-12 col-md-6">
                        <label for="responsible_person_id">Ответственный</label>
                        <select id="responsible_person_id" name="responsible_person_id"
                            class="form-control @error('responsible_person_id') is-invalid @enderror">
                            <option value="">— Не назначен —</option>
                            @foreach ($responsiblePeople as $id => $label)
                                <option value="{{ $id }}" @selected(old('responsible_person_id') == $id)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('responsible_person_id')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Описание</label>
                    <textarea id="description" name="description"
                        class="form-control @error('description') is-invalid @enderror"
                        rows="3">{{ old('description') }}</textarea>
                    @error('description')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-check"></i> Создать
                </button>
                <a href="{{ route('contractors.show', $contractor) }}" class="btn btn-secondary">Отмена</a>
            </form>
        </div>
    </div>

    @push('js')
        <script>
            $('#responsible_person_id').select2({
                placeholder: 'Выберите ответственного сотрудника',
                width: '100%',
                allowClear: true,
                language: { noResults: () => 'Нет сотрудников' }
            });
        </script>
    @endpush
@endsection
