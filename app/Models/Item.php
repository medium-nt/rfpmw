<?php

namespace App\Models;

use Database\Factories\ItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['sku', 'vendor_id', 'description'])]
class Item extends Model
{
    /** @use HasFactory<ItemFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Вендор этого артикула (с мягко-удалёнными — имя сохраняется в позициях после удаления контрагента).
     *
     * @return BelongsTo<Contractor, Item>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Contractor::class)->withTrashed();
    }

    /**
     * Позиции проектов с этим артикулом.
     *
     * @return HasMany<ProjectItem>
     */
    public function projectItems(): HasMany
    {
        return $this->hasMany(ProjectItem::class);
    }

    /**
     * Позиции запросов с этим артикулом.
     *
     * @return HasMany<RequestItem>
     */
    public function requestItems(): HasMany
    {
        return $this->hasMany(RequestItem::class);
    }

    /**
     * Позиции коммерческих предложений с этим артикулом.
     *
     * @return HasMany<ProposalItem>
     */
    public function proposalItems(): HasMany
    {
        return $this->hasMany(ProposalItem::class);
    }

    /**
     * Список артикулов для селекта (по sku): без soft-deleted артикулов и без артикулов
     * удалённых вендоров. Артикулы без вендора (vendor_id null) остаются.
     *
     * @return Collection<int, Item>
     */
    public static function forSelect(): Collection
    {
        return static::with('vendor')
            ->where(fn ($q) => $q
                ->whereNull('vendor_id')
                ->orWhereHas('vendor', fn ($qq) => $qq->whereNull('contractors.deleted_at')))
            ->orderBy('sku')
            ->get();
    }
}
