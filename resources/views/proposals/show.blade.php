@extends('layouts.admin')

@section('title', 'КП ' . ($proposal->date?->format('d.m.Y') ?? '—'))

@section('content_header')
    <h1>
        КП {{ $proposal->date?->format('d.m.Y') ?? '—' }}
        @if ($proposal->status)
            <span class="badge badge-info">{{ \App\Models\Proposal::getStatuses()[$proposal->status] ?? $proposal->status }}</span>
        @endif
    </h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header d-flex flex-wrap align-items-center">
            @include('partials.back-button', ['fallbackRoute' => route('proposals.index')])
            <a href="{{ route('proposals.edit', $proposal) }}" class="btn btn-primary btn-sm">
                <i class="fas fa-edit"></i> Редактировать
            </a>
        </div>
        <div class="card-body">
            <div class="dl-horizontal-scroll">
            <dl class="row mb-0">
                <dt class="col-5 col-sm-3 col-md-2">Дата</dt>
                <dd class="col-7 col-sm-9 col-md-10">{{ $proposal->date?->format('d.m.Y') ?? '—' }}</dd>

                <dt class="col-5 col-sm-3 col-md-2">Заказчик</dt>
                <dd class="col-7 col-sm-9 col-md-10">
                    <a href="{{ route('contractors.show', [$proposal->employedPerson->contractor, 'from' => '/' . request()->path()]) }}">{{ $proposal->employedPerson->contractor?->name }}</a>
                </dd>

                <dt class="col-5 col-sm-3 col-md-2">Кому</dt>
                <dd class="col-7 col-sm-9 col-md-10">
                    @if ($proposal->employedPerson)
                        {{ $proposal->employedPerson->contactPerson?->fio ?? '—' }}
                        @if ($proposal->employedPerson->position)
                            <span class="text-muted">({{ $proposal->employedPerson->position }})</span>
                        @endif
                    @else
                        —
                    @endif
                </dd>

                <dt class="col-5 col-sm-3 col-md-2">Проект</dt>
                <dd class="col-7 col-sm-9 col-md-10">
                    @if ($proposal->project)
                        <a href="{{ route('projects.show', [$proposal->project, 'from' => '/' . request()->path()]) }}">{{ $proposal->project->name }}</a>
                    @else
                        —
                    @endif
                </dd>

                @if ($proposal->request)
                    <dt class="col-5 col-sm-3 col-md-2">Запрос</dt>
                    <dd class="col-7 col-sm-9 col-md-10">
                        <a href="{{ route('requests.show', [$proposal->request, 'from' => '/' . request()->path()]) }}">Запрос от {{ $proposal->request->date?->format('d.m.Y') ?? '—' }}</a>
                    </dd>
                @endif
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
                            <th class="text-center" style="width: 1%;">№</th>
                            <th>Артикул</th>
                            <th>Вендор</th>
                            <th class="text-right">Кол-во</th>
                            <th class="text-right">Цена, USD</th>
                            <th class="text-right">Сумма, USD</th>
                            <th>Срок поставки</th>
                            <th class="text-right" style="width: 1%;">&nbsp;</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($proposal->proposalItems as $proposalItem)
                            <tr>
                                <td class="text-center">{{ $loop->iteration }}</td>
                                <td>
                                    @if ($proposalItem->item)
                                        @if ($proposalItem->item->trashed())
                                            <span class="text-muted">{{ $proposalItem->item->sku }}</span>
                                            <span class="badge badge-secondary">удалён</span>
                                        @else
                                            <a href="{{ route('items.show', [$proposalItem->item, 'from' => '/' . request()->path()]) }}">{{ $proposalItem->item->sku }}</a>
                                        @endif
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>{{ $proposalItem->item?->vendor?->name ?? '—' }}</td>
                                <td class="text-right">{{ $proposalItem->quantity ?? '—' }}</td>
                                <td class="text-right">{{ number_format((float) $proposalItem->price, 2, '.', ' ') }}</td>
                                <td class="text-right">{{ number_format(((float) $proposalItem->price) * ((int) $proposalItem->quantity), 2, '.', ' ') }}</td>
                                <td>{{ $proposalItem->delivery_term ?? '—' }}</td>
                                <td class="text-right">
                                    <a href="{{ route('proposal-items.edit', [$proposal, $proposalItem]) }}" class="btn btn-warning btn-sm" title="Изменить" data-toggle="tooltip">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" action="{{ route('proposal-items.destroy', [$proposal, $proposalItem]) }}" class="d-inline" onsubmit="return confirm(@js('Удалить позицию «' . ($proposalItem->item?->sku ?? 'без артикула') . '»?'));">
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
                    @if ($proposal->proposalItems->isNotEmpty())
                        @php
                            $total = $proposal->proposalItems->sum(fn ($i) => (float) $i->price * (int) $i->quantity);
                        @endphp
                        <tfoot>
                            <tr class="font-weight-bold">
                                <td colspan="5" class="text-right">Итого, USD</td>
                                <td class="text-right">{{ number_format((float) $total, 2, '.', ' ') }}</td>
                                <td></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>

            @if ($proposal->comment)
                <div class="mt-3"><x-expandable-text :value="$proposal->comment" /></div>
            @endif

            <hr>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Добавить позицию</h5>
                <a href="{{ route('items.create', ['from' => 'proposal', 'parent' => $proposal->id]) }}"
                    class="btn btn-default btn-sm">
                    <i class="fas fa-plus"></i> Создать артикул
                </a>
            </div>
            <form method="POST" action="{{ route('proposal-items.store', $proposal) }}">
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
                    <div class="form-group col-6 col-md-3">
                        <label for="delivery_term">Срок поставки</label>
                        <input type="text" id="delivery_term" name="delivery_term" maxlength="255"
                            class="form-control @error('delivery_term') is-invalid @enderror"
                            value="{{ old('delivery_term') }}"
                            placeholder="например, 2 недели">
                        @error('delivery_term')
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
            <h3 class="card-title">Удаление КП</h3>
        </div>
        <div class="card-body">
            <p>КП будет перемещено в корзину (мягкое удаление).</p>
            <form method="POST" action="{{ route('proposals.destroy', $proposal) }}" onsubmit="return confirm(@js('Удалить КП №' . $proposal->id . '?'));">
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
