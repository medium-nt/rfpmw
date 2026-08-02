@extends('layouts.admin')

@section('title', 'Редактирование: запрос №' . $request->id)

@section('content_header')
    <h1>Редактирование: запрос №{{ $request->id }}</h1>
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

            <form method="POST" action="{{ route('requests.update', $request) }}">
                @csrf
                @method('PUT')

                <div class="form-row">
                    <div class="form-group col-12 col-md-6">
                        <label for="employed_person_id">Сотрудник (от кого поступил) <span class="text-danger">*</span></label>
                        <select id="employed_person_id" name="employed_person_id"
                            class="form-control @error('employed_person_id') is-invalid @enderror" required>
                            <option value="">— Выберите —</option>
                            @foreach ($employedPeople as $id => $label)
                                <option value="{{ $id }}" @selected(old('employed_person_id', $request->employed_person_id) == $id)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('employed_person_id')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group col-12 col-md-6">
                        <label for="project_id">Проект</label>
                        <select id="project_id" name="project_id"
                            class="form-control @error('project_id') is-invalid @enderror">
                            <option value="">— Не выбран —</option>
                            @foreach ($projects as $id => $label)
                                <option value="{{ $id }}" @selected(old('project_id', $request->project_id) == $id)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('project_id')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group col-12 col-md-3">
                        <label for="date">Дата <span class="text-danger">*</span></label>
                        <input type="date" id="date" name="date"
                            class="form-control @error('date') is-invalid @enderror"
                            value="{{ old('date', $request->date?->format('Y-m-d')) }}" required>
                        @error('date')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group col-12 col-md-3">
                        <label for="status">Статус</label>
                        <select id="status" name="status" class="form-control @error('status') is-invalid @enderror">
                            <option value="">— Не выбран —</option>
                            @foreach (\App\Models\Request::getStatuses() as $key => $label)
                                <option value="{{ $key }}" @selected(old('status', $request->status) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('status')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group col-12">
                        <label for="comment">Комментарий</label>
                        <textarea id="comment" name="comment" rows="3"
                            class="form-control @error('comment') is-invalid @enderror">{{ old('comment', $request->comment) }}</textarea>
                        @error('comment')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Сохранить</button>
                <a href="{{ route('requests.show', $request) }}" class="btn btn-secondary">Отмена</a>
            </form>
        </div>
    </div>

    @push('js')
        <script>
            $('#employed_person_id').select2({
                placeholder: 'Выберите сотрудника',
                width: '100%',
                language: { noResults: () => 'Нет сотрудников' }
            });
        </script>
    @endpush
@endsection
