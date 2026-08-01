<?php

namespace App\Models;

use Database\Factories\ProposalItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['proposal_id', 'item_id', 'quantity', 'price', 'delivery_term'])]
class ProposalItem extends Model
{
    /** @use HasFactory<ProposalItemFactory> */
    use HasFactory;

    /** Позиция не имеет created_at/updated_at. */
    public $timestamps = false;

    /**
     * Атрибуты, приводимые к нативным типам.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    /**
     * КП, которому принадлежит позиция.
     *
     * @return BelongsTo<Proposal, self>
     */
    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    /**
     * Артикул позиции (с withTrashed — удалённый артикул остаётся доступен для истории).
     *
     * @return BelongsTo<Item, self>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class)->withTrashed();
    }
}
