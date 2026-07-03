@extends('layouts.admin')

@section('title', 'Запрос №' . $request->id)

@section('content_header')
    <h1>Запрос №{{ $request->id }}</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header d-flex flex-wrap align-items-center">
            @include('partials.back-button', ['fallbackRoute' => route('requests.index')])
            <a href="{{ route('requests.edit', $request) }}" class="btn btn-primary btn-sm">
                <i class="fas fa-edit"></i> Редактировать
            </a>
        </div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3 col-md-2">Дата</dt>
                <dd class="col-sm-9 col-md-10">{{ $request->date?->format('d.m.Y') ?? '—' }}</dd>

                <dt class="col-sm-3 col-md-2">Статус</dt>
                <dd class="col-sm-9 col-md-10">
                    @if ($request->status)
                        <span class="badge badge-info">{{ \App\Models\Request::getStatuses()[$request->status] ?? $request->status }}</span>
                    @else
                        —
                    @endif
                </dd>

                <dt class="col-sm-3 col-md-2">Сотрудник</dt>
                <dd class="col-sm-9 col-md-10">
                    @if ($request->employedPerson)
                                        {{ $request->employedPerson->contactPerson?->fio ?? '—' }}
                        @if ($request->employedPerson->position)
                                            <span class="text-muted">({{ $request->employedPerson->position }})</span>
                        @endif
                    @else
                        —
                    @endif
                </dd>

                <dt class="col-sm-3 col-md-2">Контрагент</dt>
                <dd class="col-sm-9 col-md-10">
                    <a href="{{ route('contractors.show', [$request->employedPerson->contractor, 'from' => '/' . request()->path()]) }}">{{ $request->employedPerson->contractor?->name }}</a>
                </dd>

                <dt class="col-sm-3 col-md-2">Менеджер</dt>
                <dd class="col-sm-9 col-md-10">{{ $request->user?->name ?? '—' }}</dd>

                <dt class="col-sm-3 col-md-2">Сумма, USD</dt>
                <dd class="col-sm-9 col-md-10">{{ number_format((float) $request->usd_value, 2, '.', ' ') }}</dd>
            </dl>
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
                            <th class="text-right">Сумма, USD</th>
                            <th class="text-right" style="width: 1%;">&nbsp;</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($request->requestItems as $requestItem)
                            <tr>
                                <td>
                                    @if ($requestItem->item)
                                        @if ($requestItem->item->trashed())
                                            <span class="text-muted">{{ $requestItem->item->sku }}</span>
                                            <span class="badge badge-secondary">удалён</span>
                                        @else
                                            <a href="{{ route('items.show', [$requestItem->item, 'from' => '/' . request()->path()]) }}">{{ $requestItem->item->sku }}</a>
                                        @endif
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>{{ $requestItem->item?->vendor?->name ?? '—' }}</td>
                                <td class="text-right">{{ $requestItem->quantity ?? '—' }}</td>
                                <td class="text-right">{{ number_format((float) $requestItem->price, 2, '.', ' ') }}</td>
                                <td class="text-right">{{ number_format(((float) $requestItem->price) * ((int) $requestItem->quantity), 2, '.', ' ') }}</td>
                                <td class="text-right">
                                    <a href="{{ route('request-items.edit', [$request, $requestItem]) }}" class="btn btn-warning btn-sm" title="Изменить" data-toggle="tooltip">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" action="{{ route('request-items.destroy', [$request, $requestItem]) }}" class="d-inline" onsubmit="return confirm(@js('Удалить позицию «' . ($requestItem->item?->sku ?? 'без артикула') . '»?'));">
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
                                <td colspan="6" class="text-center text-muted py-3">Позиции отсутствуют.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <hr>

            <h5 class="mb-3">Добавить позицию</h5>
            <form method="POST" action="{{ route('request-items.store', $request) }}">
                @csrf
                <div class="form-row align-items-end">
                    <div class="form-group col-12 col-md-6">
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
                    <div class="form-group col-6 col-md-3">
                        <label for="quantity">Количество</label>
                        <input type="number" id="quantity" name="quantity" min="1"
                            class="form-control @error('quantity') is-invalid @enderror"
                            value="{{ old('quantity') }}" required>
                        @error('quantity')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group col-6 col-md-3">
                        <label for="price">Цена, USD</label>
                        <input type="number" id="price" name="price" min="0" step="0.01"
                            class="form-control @error('price') is-invalid @enderror"
                            value="{{ old('price') }}">
                        @error('price')
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

    @can('is-admin')
    <div class="card card-danger mt-3">
        <div class="card-header">
            <h3 class="card-title">Удаление запроса</h3>
        </div>
        <div class="card-body">
            <p>Запрос будет перемещён в корзину (мягкое удаление).</p>
            <form method="POST" action="{{ route('requests.destroy', $request) }}" onsubmit="return confirm(@js('Удалить запрос №' . $request->id . '?'));">
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
