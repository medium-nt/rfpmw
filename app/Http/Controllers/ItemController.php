<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreItemRequest;
use App\Http\Requests\UpdateItemRequest;
use App\Models\Contractor;
use App\Models\Item;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ItemController extends Controller
{
    /**
     * Отображает список артикулов.
     * Сортировка по клику на заголовок: ?sort=sku|vendor&direction=asc|desc.
     * По умолчанию — по артикулу (sku) по алфавиту (asc).
     */
    public function index(): View
    {
        $allowedSortFields = ['sku', 'vendor'];
        $sortField = request('sort', 'sku');
        $sortDirection = request('direction', 'asc');

        if (! in_array($sortField, $allowedSortFields)) {
            $sortField = 'sku';
        }
        if (! in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'asc';
        }

        $items = Item::with('vendor')
            ->when(request('q'), function ($query, $q): void {
                $query->where(function ($sub) use ($q): void {
                    $sub->where('sku', 'like', '%'.$q.'%')
                        ->orWhereHas('vendor', fn ($v) => $v->where('name', 'like', '%'.$q.'%'));
                });
            })
            ->when($sortField === 'vendor', function ($query) use ($sortDirection): void {
                // Сортировка по имени вендора (прямая связь vendor_id, может быть NULL — leftJoin)
                $query->leftJoin('contractors as vendors', 'items.vendor_id', '=', 'vendors.id')
                    ->select('items.*')
                    ->orderBy('vendors.name', $sortDirection);
            }, function ($query) use ($sortDirection): void {
                $query->orderBy('sku', $sortDirection);
            })
            ->paginate(20)
            ->appends(['q' => request('q'), 'sort' => $sortField, 'direction' => $sortDirection]);

        return view('items.index', compact('items', 'sortField', 'sortDirection'));
    }

    /**
     * Отображает форму создания артикула.
     */
    public function create(): View
    {
        $vendors = Contractor::where('type', 'vendor')->orderBy('name')->pluck('name', 'id');

        return view('items.create', compact('vendors'));
    }

    /**
     * Сохраняет новый артикул.
     */
    public function store(StoreItemRequest $request): RedirectResponse
    {
        $item = Item::create($request->validated());

        return redirect()
            ->route('items.show', $item)
            ->with('success', 'Артикул успешно создан.');
    }

    /**
     * Отображает карточку артикула.
     */
    public function show(Item $item): View
    {
        $item->load('vendor');

        return view('items.show', compact('item'));
    }

    /**
     * Отображает форму редактирования артикула.
     */
    public function edit(Item $item): View
    {
        $vendors = Contractor::where('type', 'vendor')->orderBy('name')->pluck('name', 'id');

        return view('items.edit', compact('item', 'vendors'));
    }

    /**
     * Обновляет артикул.
     */
    public function update(UpdateItemRequest $request, Item $item): RedirectResponse
    {
        $item->update($request->validated());

        return redirect()
            ->route('items.show', $item)
            ->with('success', 'Артикул успешно обновлён.');
    }

    /**
     * Удаляет артикул (soft delete).
     */
    public function destroy(Item $item): RedirectResponse
    {
        $item->delete();

        return redirect()
            ->route('items.index')
            ->with('success', 'Артикул успешно удалён.');
    }
}
