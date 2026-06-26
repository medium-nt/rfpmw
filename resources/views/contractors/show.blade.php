@extends('layouts.admin')

@section('title', $contractor->name)

@section('content_header')
    <h1>{{ $contractor->name }}</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header d-flex flex-wrap align-items-center">
            <a href="{{ route('contractors.index') }}" class="btn btn-default btn-sm mr-2">
                <i class="fas fa-arrow-left"></i> К списку
            </a>
            <a href="{{ route('contractors.edit', $contractor) }}" class="btn btn-primary btn-sm">
                <i class="fas fa-edit"></i> Редактировать
            </a>
        </div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3 col-md-2">Название</dt>
                <dd class="col-sm-9 col-md-10">{{ $contractor->name }}</dd>

                <dt class="col-sm-3 col-md-2">ИНН</dt>
                <dd class="col-sm-9 col-md-10">{{ $contractor->inn }}</dd>

                <dt class="col-sm-3 col-md-2">Тип</dt>
                <dd class="col-sm-9 col-md-10">{{ \App\Models\Contractor::getTypes()[$contractor->type] ?? $contractor->type }}</dd>

                <dt class="col-sm-3 col-md-2">Адрес</dt>
                <dd class="col-sm-9 col-md-10">{{ $contractor->address ?? '—' }}</dd>

                <dt class="col-sm-3 col-md-2">Сайт</dt>
                <dd class="col-sm-9 col-md-10">
                    @if ($contractor->website)
                        <a href="{{ $contractor->website }}" target="_blank">{{ $contractor->website }}</a>
                    @else
                        —
                    @endif
                </dd>

                <dt class="col-sm-3 col-md-2">Менеджер</dt>
                <dd class="col-sm-9 col-md-10">{{ $contractor->user?->name ?? '—' }}</dd>
            </dl>
        </div>
    </div>

    <div class="card card-info card-outline mt-3">
        <div class="card-header">
            <h3 class="card-title">Контактные лица</h3>
            <div class="card-tools">
                <a href="{{ route('contact-people.create', $contractor) }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Создать новое
                </a>
            </div>
        </div>
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

            <div class="table-responsive">
                <table class="table table-bordered table-striped mb-0">
                    <thead>
                        <tr>
                            <th>ФИО</th>
                            <th>Должность</th>
                            <th>Контакты</th>
                            <th class="text-right" style="width: 1%;">&nbsp;</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($contractor->employedPeople as $employed)
                            <tr>
                                <td>
                                    <a href="{{ route('contact-people.show', $employed->contactPerson) }}">
                                        {{ $employed->contactPerson->fio }}
                                    </a>
                                </td>
                                <td>{{ $employed->position ?: '—' }}</td>
                                <td>
                                    @if ($employed->contactPerson->phone) <div class="text-nowrap"><i class="fas fa-phone"></i> {{ $employed->contactPerson->phone }}</div> @endif
                                    @if ($employed->contactPerson->email) <div class="text-nowrap"><i class="fas fa-envelope"></i> {{ $employed->contactPerson->email }}</div> @endif
                                    @unless ($employed->contactPerson->phone || $employed->contactPerson->email) <span class="text-muted">—</span> @endunless
                                </td>
                                <td class="text-right">
                                    <form method="POST" action="{{ route('employed-people.destroy', [$contractor, $employed]) }}" onsubmit="return confirm(@js('Отвязать «' . $employed->contactPerson->fio . '» от этого контрагента?'));">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm" title="Отвязать" data-toggle="tooltip">
                                            <i class="fas fa-unlink"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">Контактные лица не привязаны.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <hr>

            <h5 class="mb-3">Привязать существующего</h5>
            <form method="POST" action="{{ route('employed-people.store', $contractor) }}">
                @csrf
                <div class="form-row align-items-end">
                    <div class="form-group col-12 col-md-6">
                        <label for="contact_person_id">Контактное лицо</label>
                        <select id="contact_person_id" name="contact_person_id" class="form-control" required>
                            <option value="">— Выберите —</option>
                            @foreach ($availablePeople as $person)
                                <option value="{{ $person->id }}" @selected(old('contact_person_id') == $person->id)>
                                    {{ $person->fio }} — {{ $person->phone ?? $person->email ?? 'без контактов' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-12 col-md-4">
                        <label for="attach_position">Должность</label>
                        <input type="text" id="attach_position" name="position" class="form-control" value="{{ old('position') }}">
                    </div>
                    <div class="form-group col-12 col-md-2">
                        <button type="submit" class="btn btn-success btn-block" @disabled($availablePeople->isEmpty())>
                            <i class="fas fa-link"></i> Привязать
                        </button>
                    </div>
                </div>
                @if ($availablePeople->isEmpty())
                    <small class="text-muted">Все контактные лица уже привязаны или база пуста.</small>
                @endif
            </form>
        </div>
    </div>

    <div class="card card-info card-outline mt-3">
        <div class="card-header">
            <h3 class="card-title">Проекты</h3>
            <div class="card-tools">
                <a href="{{ route('projects.create', $contractor) }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Создать
                </a>
            </div>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-bordered table-striped mb-0">
                <thead>
                    <tr>
                        <th>Название</th>
                        <th>Дата</th>
                        <th>Статус</th>
                        <th>Ответственный</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($contractor->projects as $project)
                        <tr>
                            <td>
                                <a href="{{ route('projects.show', $project) }}">{{ $project->name }}</a>
                            </td>
                            <td>{{ $project->date?->format('d.m.Y') ?? '—' }}</td>
                            <td>{{ \App\Models\Project::getStatuses()[$project->status] ?? $project->status ?? '—' }}</td>
                            <td>{{ $project->responsiblePerson?->contactPerson?->fio ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-3">Проекты отсутствуют.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card card-warning card-outline mt-3">
        <div class="card-header">
            <h3 class="card-title">Запросы</h3>
            <div class="card-tools">
                <a href="{{ route('requests.create', $contractor) }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Создать
                </a>
            </div>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-bordered table-striped mb-0">
                <thead>
                    <tr>
                        <th>№</th>
                        <th>Дата</th>
                        <th>Сотрудник</th>
                        <th>Статус</th>
                        <th>Менеджер</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $request)
                        <tr>
                            <td>
                                <a href="{{ route('requests.show', $request) }}">№{{ $request->id }}</a>
                            </td>
                            <td>{{ $request->date?->format('d.m.Y') ?? '—' }}</td>
                            <td>{{ $request->employedPerson?->contactPerson?->fio ?? '—' }}</td>
                            <td>{{ \App\Models\Request::getStatuses()[$request->status] ?? $request->status ?? '—' }}</td>
                            <td>{{ $request->user?->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-3">Запросы отсутствуют.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card card-danger mt-3">
        <div class="card-header">
            <h3 class="card-title">Удаление контрагента</h3>
        </div>
        <div class="card-body">
            <p>Контрагент будет перемещён в корзину (мягкое удаление). Привязки к проектам и сотрудникам сохраняются.</p>
            <form method="POST" action="{{ route('contractors.destroy', $contractor) }}" onsubmit="return confirm(@js('Удалить контрагента «' . $contractor->name . '»? Он будет перемещён в корзину.'));">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Удалить
                </button>
            </form>
        </div>
    </div>

    @push('js')
        <script>
            $('#contact_person_id').select2({
                placeholder: 'Поиск по имени, телефону или email',
                width: '100%',
                language: { noResults: () => 'Ничего не найдено' }
            });
            $('[data-toggle="tooltip"]').tooltip();
        </script>
    @endpush
@endsection
