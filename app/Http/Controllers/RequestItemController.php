<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRequestItemRequest;
use App\Http\Requests\UpdateRequestItemRequest;
use App\Models\Request;
use App\Models\RequestItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RequestItemController extends Controller
{
    /**
     * Добавляет позицию (артикул) к запросу и пересчитывает сумму запроса.
     */
    public function store(StoreRequestItemRequest $input, Request $request): RedirectResponse
    {
        $this->authorizeRequestAccess($request);

        $request->requestItems()->create($input->validated());

        $request->recalcUsdValue();

        return redirect()
            ->route('requests.show', $request)
            ->with('success', 'Позиция добавлена.');
    }

    /**
     * Форма редактирования позиции запроса (артикул неизменяем).
     */
    public function edit(Request $request, RequestItem $requestItem): View
    {
        $this->authorizeRequestItemAccess($request, $requestItem);

        $requestItem->load('item.vendor');

        return view('request-items.edit', compact('request', 'requestItem'));
    }

    /**
     * Обновляет параметры позиции запроса и пересчитывает сумму.
     */
    public function update(UpdateRequestItemRequest $input, Request $request, RequestItem $requestItem): RedirectResponse
    {
        $this->authorizeRequestItemAccess($request, $requestItem);

        $requestItem->update($input->validated());

        $request->recalcUsdValue();

        return redirect()
            ->route('requests.show', $request)
            ->with('success', 'Позиция обновлена.');
    }

    /**
     * Удаляет позицию запроса и пересчитывает сумму.
     */
    public function destroy(Request $request, RequestItem $requestItem): RedirectResponse
    {
        $this->authorizeRequestItemAccess($request, $requestItem);

        $requestItem->delete();

        $request->recalcUsdValue();

        return redirect()
            ->route('requests.show', $request)
            ->with('success', 'Позиция удалена.');
    }

    /**
     * Проверка доступа к запросу и принадлежности позиции этому запросу.
     *
     * Чужая позиция (переданная через URL другого запроса) трактуется как отсутствующий ресурс.
     */
    protected function authorizeRequestItemAccess(Request $request, RequestItem $requestItem): void
    {
        $this->authorizeRequestAccess($request);

        if ($requestItem->request_id !== $request->id) {
            abort(404);
        }
    }
}
