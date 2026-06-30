<?php

namespace App\Models;

use Database\Factories\RequestItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['request_id', 'item_id', 'quantity', 'price'])]
class RequestItem extends Model
{
    /** @use HasFactory<RequestItemFactory> */
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
     * Запрос, которому принадлежит позиция.
     *
     * @return BelongsTo<Request, self>
     */
    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class);
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
