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
     */
    public function index(): View
    {
        $items = Item::with('vendor')
            ->when(request('q'), fn ($query) => $query->where('sku', 'like', '%'.request('q').'%'))
            ->orderByDesc('id')
            ->paginate(20)
            ->appends(['q' => request('q')]);

        return view('items.index', compact('items'));
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
