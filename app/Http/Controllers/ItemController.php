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
     *
     * Если запрос пришёл из карточки сущности (валидные параметры from + parent),
     * строит URL кнопки «Отмена» для возврата в эту карточку и передаёт во view
     * значения для hidden-полей, чтобы store() мог вернуть пользователя обратно.
     * Whitelist параметра from и проверка parent совпадают с логикой store().
     */
    public function create(): View
    {
        $vendors = Contractor::where('type', 'vendor')->orderBy('name')->pluck('name', 'id');

        $from = request('from');
        $parentId = request('parent');

        $hasContext = in_array($from, ['project', 'request', 'proposal'], true)
            && ctype_digit((string) $parentId);

        $cancelUrl = $hasContext
            ? match ($from) {
                'project' => route('projects.show', $parentId),
                'request' => route('requests.show', $parentId),
                'proposal' => route('proposals.show', $parentId),
            }
        : route('items.index');

        // Значения для hidden-полей формы — только при валидном контексте
        $contextFrom = $hasContext ? $from : null;
        $contextParent = $hasContext ? $parentId : null;

        return view('items.create', compact(
            'vendors',
            'hasContext',
            'cancelUrl',
            'contextFrom',
            'contextParent',
        ));
    }

    /**
     * Сохраняет новый артикул.
     *
     * Если запрос пришёл из карточки сущности (параметры from + parent),
     * возвращает пользователя обратно в эту карточку — новый артикул
     * будет доступен в выпадающем списке позиций.
     */
    public function store(StoreItemRequest $request): RedirectResponse
    {
        $item = Item::create($request->validated());

        $from = $request->input('from');
        $parentId = $request->input('parent');

        if (in_array($from, ['project', 'request', 'proposal'], true) && ctype_digit((string) $parentId)) {
            $route = match ($from) {
                'project' => 'projects.show',
                'request' => 'requests.show',
                'proposal' => 'proposals.show',
            };

            return redirect()
                ->route($route, $parentId)
                ->with('success', 'Артикул создан. Выберите его в списке, чтобы добавить позицию.');
        }

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
