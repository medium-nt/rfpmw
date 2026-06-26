@extends('layouts.admin')

@section('title', 'Редактирование: событие №' . $event->id)

@section('content_header')
    <h1>Редактирование: событие №{{ $event->id }}</h1>
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

            <form method="POST" action="{{ route('events.update', $event) }}">
                @csrf
                @method('PUT')

                <div class="form-row">
                    <div class="form-group col-12 col-md-6">
                        <label for="employed_person_id">Сотрудник <span class="text-danger">*</span></label>
                        <select id="employed_person_id" name="employed_person_id"
                            class="form-control @error('employed_person_id') is-invalid @enderror" required>
                            <option value="">— Выберите —</option>
                            @foreach ($employedPeople as $id => $label)
                                <option value="{{ $id }}" @selected(old('employed_person_id', $event->employed_person_id) == $id)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('employed_person_id')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group col-12 col-md-3">
                        <label for="event_type">Тип <span class="text-danger">*</span></label>
                        <select id="event_type" name="event_type"
                            class="form-control @error('event_type') is-invalid @enderror" required>
                            <option value="">— Выберите —</option>
                            @foreach (\App\Models\Event::getEventTypes() as $key => $label)
                                <option value="{{ $key }}" @selected(old('event_type', $event->event_type) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('event_type')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group col-12 col-md-3">
                        <label for="date">Дата <span class="text-danger">*</span></label>
                        <input type="date" id="date" name="date"
                            class="form-control @error('date') is-invalid @enderror"
                            value="{{ old('date', $event->date?->format('Y-m-d')) }}" required>
                        @error('date')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="form-group">
                    <label for="subject">Тема</label>
                    <input type="text" id="subject" name="subject"
                        class="form-control @error('subject') is-invalid @enderror"
                        value="{{ old('subject', $event->subject) }}">
                    @error('subject')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="description">Описание</label>
                    <textarea id="description" name="description"
                        class="form-control @error('description') is-invalid @enderror"
                        rows="3">{{ old('description', $event->description) }}</textarea>
                    @error('description')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="link">Привязать к (необязательно)</label>
                    <select id="link" name="link" class="form-control @error('link') is-invalid @enderror">
                        <option value="">— Не выбрано —</option>
                        @if (! empty($entities['projects']))
                            <optgroup label="Проекты">
                                @foreach ($entities['projects'] as $id => $label)
                                    <option value="project:{{ $id }}" @selected(old('link', $currentLink) === "project:$id")>{{ $label }}</option>
                                @endforeach
                            </optgroup>
                        @endif
                        @if (! empty($entities['requests']))
                            <optgroup label="Запросы">
                                @foreach ($entities['requests'] as $id => $label)
                                    <option value="request:{{ $id }}" @selected(old('link', $currentLink) === "request:$id")>{{ $label }}</option>
                                @endforeach
                            </optgroup>
                        @endif
                        @if (! empty($entities['proposals']))
                            <optgroup label="КП">
                                @foreach ($entities['proposals'] as $id => $label)
                                    <option value="proposal:{{ $id }}" @selected(old('link', $currentLink) === "proposal:$id")>{{ $label }}</option>
                                @endforeach
                            </optgroup>
                        @endif
                    </select>
                    @error('link')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">Сохранить</button>
                <a href="{{ route('events.show', $event) }}" class="btn btn-secondary">Отмена</a>
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
            $('#link').select2({
                placeholder: 'Без привязки',
                width: '100%',
                allowClear: true,
                language: { noResults: () => 'Нечего привязывать' }
            });
        </script>
    @endpush
@endsection
