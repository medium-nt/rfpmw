<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProposalItemRequest;
use App\Http\Requests\UpdateProposalItemRequest;
use App\Models\Proposal;
use App\Models\ProposalItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProposalItemController extends Controller
{
    /**
     * Добавляет позицию (артикул) к КП и пересчитывает сумму КП.
     */
    public function store(StoreProposalItemRequest $request, Proposal $proposal): RedirectResponse
    {
        $this->authorizeProposalAccess($proposal);

        $proposal->proposalItems()->create($request->validated());

        $proposal->recalcUsdValue();

        return redirect()
            ->route('proposals.show', $proposal)
            ->with('success', 'Позиция добавлена.');
    }

    /**
     * Форма редактирования позиции КП (артикул неизменяем).
     */
    public function edit(Proposal $proposal, ProposalItem $proposalItem): View
    {
        $this->authorizeProposalItemAccess($proposal, $proposalItem);

        $proposalItem->load('item.vendor');

        return view('proposal-items.edit', compact('proposal', 'proposalItem'));
    }

    /**
     * Обновляет параметры позиции КП и пересчитывает сумму.
     */
    public function update(UpdateProposalItemRequest $request, Proposal $proposal, ProposalItem $proposalItem): RedirectResponse
    {
        $this->authorizeProposalItemAccess($proposal, $proposalItem);

        $proposalItem->update($request->validated());

        $proposal->recalcUsdValue();

        return redirect()
            ->route('proposals.show', $proposal)
            ->with('success', 'Позиция обновлена.');
    }

    /**
     * Удаляет позицию КП и пересчитывает сумму.
     */
    public function destroy(Proposal $proposal, ProposalItem $proposalItem): RedirectResponse
    {
        $this->authorizeProposalItemAccess($proposal, $proposalItem);

        $proposalItem->delete();

        $proposal->recalcUsdValue();

        return redirect()
            ->route('proposals.show', $proposal)
            ->with('success', 'Позиция удалена.');
    }

    /**
     * Проверка доступа к КП и принадлежности позиции этому КП.
     *
     * Чужая позиция (переданная через URL другого КП) трактуется как отсутствующий ресурс.
     */
    protected function authorizeProposalItemAccess(Proposal $proposal, ProposalItem $proposalItem): void
    {
        $this->authorizeProposalAccess($proposal);

        if ($proposalItem->proposal_id !== $proposal->id) {
            abort(404);
        }
    }
}
