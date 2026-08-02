@extends('layouts.admin')

@section('title', $project->name)

@section('content_header')
    <h1>{{ $project->name }}</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header d-flex flex-wrap align-items-center">
            @include('partials.back-button', ['fallbackRoute' => route('projects.index')])
            <a href="{{ route('projects.edit', $project) }}" class="btn btn-primary btn-sm">
                <i class="fas fa-edit"></i> Редактировать
            </a>
        </div>
        <div class="card-body">
            <div class="dl-horizontal-scroll">
            <dl class="row mb-0">
                <dt class="col-6 col-sm-3 col-md-2">Название</dt>
                <dd class="col-6 col-sm-9 col-md-10">{{ $project->name }}</dd>

                <dt class="col-6 col-sm-3 col-md-2">Контрагент</dt>
                <dd class="col-6 col-sm-9 col-md-10">
                    <a href="{{ route('contractors.show', [$project->contractor, 'from' => '/' . request()->path()]) }}">{{ $project->contractor->name }}</a>
                </dd>

                <dt class="col-6 col-sm-3 col-md-2">Выход в серию</dt>
                <dd class="col-6 col-sm-9 col-md-10">{{ $project->date?->format('d.m.Y') ?? '—' }}</dd>

                <dt class="col-6 col-sm-3 col-md-2">Статус</dt>
                <dd class="col-6 col-sm-9 col-md-10">
                    @if ($project->status)
                        <span class="badge badge-info">{{ \App\Models\Project::getStatuses()[$project->status] ?? $project->status }}</span>
                    @else
                        —
                    @endif
                </dd>

                <dt class="col-6 col-sm-3 col-md-2">Конт. лицо</dt>
                <dd class="col-6 col-sm-9 col-md-10">{{ $project->responsiblePerson?->contactPerson?->fio ?? '—' }}</dd>

                <dt class="col-6 col-sm-3 col-md-2">Сумма, USD</dt>
                <dd class="col-6 col-sm-9 col-md-10">{{ number_format((float) $project->usd_value, 2, '.', ' ') }}</dd>

                <dt class="col-6 col-sm-3 col-md-2">Описание</dt>
                <dd class="col-6 col-sm-9 col-md-10 text-multiline"><x-expandable-text :value="$project->description" /></dd>
            </dl>
            </div>
        </div>
    </div>

    <div class="card card-info card-outline mt-3">
        <div class="card-header">
            <h3 class="card-title">Позиции</h3>
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
                            <th>Артикул</th>
                            <th>Вендор</th>
                            <th class="text-right">Кол-во</th>
                            <th class="text-right">Цена, USD</th>
                            <th>Статус</th>
                            <th>Дата начала пр-ва</th>
                            <th class="text-right">Сумма, USD</th>
                            <th class="text-right" style="width: 1%;">&nbsp;</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($project->projectItems as $projectItem)
                            <tr>
                                <td>
                                    @if ($projectItem->item)
                                        @if ($projectItem->item->trashed())
                                            <span class="text-muted">{{ $projectItem->item->sku }}</span>
                                            <span class="badge badge-secondary">удалён</span>
                                        @else
                                            <a href="{{ route('items.show', [$projectItem->item, 'from' => '/' . request()->path()]) }}">{{ $projectItem->item->sku }}</a>
                                        @endif
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>{{ $projectItem->item?->vendor?->name ?? '—' }}</td>
                                <td class="text-right">{{ $projectItem->quantity ?? '—' }}</td>
                                <td class="text-right">{{ number_format((float) $projectItem->price, 2, '.', ' ') }}</td>
                                <td>{{ \App\Models\ProjectItem::getStatuses()[$projectItem->status] ?? $projectItem->status ?? '—' }}</td>
                                <td>{{ $projectItem->production_start_date?->format('d.m.Y') ?? '—' }}</td>
                                <td class="text-right">{{ number_format(((float) $projectItem->price) * ((int) $projectItem->quantity), 2, '.', ' ') }}</td>
                                <td class="text-right">
                                    <a href="{{ route('project-items.edit', [$project, $projectItem]) }}" class="btn btn-warning btn-sm" title="Изменить" data-toggle="tooltip">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" action="{{ route('project-items.destroy', [$project, $projectItem]) }}" class="d-inline" onsubmit="return confirm(@js('Удалить позицию «' . ($projectItem->item?->sku ?? 'без артикула') . '»?'));">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm" title="Удалить" data-toggle="tooltip">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-3">Позиции отсутствуют.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <hr>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Добавить позицию</h5>
                <a href="{{ route('items.create', ['from' => 'project', 'parent' => $project->id]) }}"
                    class="btn btn-default btn-sm">
                    <i class="fas fa-plus"></i> Создать артикул
                </a>
            </div>
            <form method="POST" action="{{ route('project-items.store', $project) }}">
                @csrf
                <div class="form-row align-items-end">
                    <div class="form-group col-12 col-md-4">
                        <label for="item_id">Артикул</label>
                        <select id="item_id" name="item_id" class="form-control @error('item_id') is-invalid @enderror" required>
                            <option value="">— Выберите артикул —</option>
                            @foreach ($items as $item)
                                <option value="{{ $item->id }}" @selected(old('item_id') == $item->id)>
                                    {{ $item->sku }} — {{ $item->vendor?->name ?? 'без вендора' }}
                                </option>
                            @endforeach
                        </select>
                        @error('item_id')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group col-6 col-md-2">
                        <label for="quantity">Количество</label>
                        <input type="number" id="quantity" name="quantity" min="1"
                            class="form-control @error('quantity') is-invalid @enderror"
                            value="{{ old('quantity') }}">
                        @error('quantity')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group col-6 col-md-2">
                        <label for="price">Цена, USD</label>
                        <input type="number" id="price" name="price" min="0" step="0.01"
                            class="form-control @error('price') is-invalid @enderror"
                            value="{{ old('price') }}">
                        @error('price')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group col-6 col-md-2">
                        <label for="status">Статус</label>
                        <select id="status" name="status" class="form-control @error('status') is-invalid @enderror">
                            <option value="">—</option>
                            @foreach (\App\Models\ProjectItem::getStatuses() as $key => $label)
                                <option value="{{ $key }}" @selected(old('status') == $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('status')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group col-6 col-md-2">
                        <label for="production_start_date">Дата начала пр-ва</label>
                        <input type="date" id="production_start_date" name="production_start_date"
                            class="form-control @error('production_start_date') is-invalid @enderror"
                            value="{{ old('production_start_date') }}">
                        @error('production_start_date')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <button type="submit" class="btn btn-success" @disabled($items->isEmpty())>
                    <i class="fas fa-plus"></i> Добавить
                </button>
                @if ($items->isEmpty())
                    <small class="text-muted d-block mt-2">Справочник артикулов пуст — создайте артикулы в разделе «Настройки → Артикулы».</small>
                @endif
            </form>
        </div>
    </div>

    <div class="card card-info card-outline mt-3">
        <div class="card-header">
            <h3 class="card-title">События</h3>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-bordered table-striped mb-0">
                <thead>
                    <tr>
                        <th>Дата</th>
                        <th>Тип</th>
                        <th>Сотрудник</th>
                        <th>Тема</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($project->events as $event)
                        <tr>
                            <td>
                                <a href="{{ route('events.show', [$event, 'from' => '/' . request()->path()]) }}">{{ $event->date?->format('d.m.Y') ?? '—' }}</a>
                            </td>
                            <td>{{ \App\Models\Event::getEventTypes()[$event->event_type] ?? $event->event_type }}</td>
                            <td>{{ $event->employedPerson?->contactPerson?->fio ?? '—' }}</td>
                            <td>{{ $event->subject ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-3">События отсутствуют.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @can('is-admin')
    <div class="card card-danger mt-3">
        <div class="card-header">
            <h3 class="card-title">Удаление проекта</h3>
        </div>
        <div class="card-body">
            <p>Проект будет перемещён в корзину (мягкое удаление).</p>
            <form method="POST" action="{{ route('projects.destroy', $project) }}" onsubmit="return confirm(@js('Удалить проект «' . $project->name . '»?'));">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Удалить
                </button>
            </form>
        </div>
    </div>
    @endcan

    @push('js')
        <script>
            $('#item_id').select2({
                placeholder: 'Поиск по артикулу или вендору',
                width: '100%',
                language: { noResults: () => 'Ничего не найдено' }
            });
            $('[data-toggle="tooltip"]').tooltip();
        </script>
    @endpush
@endsection
