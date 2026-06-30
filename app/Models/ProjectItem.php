<?php

namespace App\Models;

use Database\Factories\ProjectItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['project_id', 'item_id', 'quantity', 'price', 'status', 'production_start_date'])]
class ProjectItem extends Model
{
    /** @use HasFactory<ProjectItemFactory> */
    use HasFactory;

    /** Позиция не имеет created_at/updated_at. */
    public $timestamps = false;

    /** Имя таблицы не подчиняется конвенции Laravel (единственное число). */
    protected $table = 'project_item';

    /**
     * Атрибуты, приводимые к нативным типам.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'production_start_date' => 'date',
        ];
    }

    /**
     * Проект, которому принадлежит позиция.
     *
     * @return BelongsTo<Project, self>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
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

    /**
     * Справочник статусов производственной позиции для выбора в формах.
     *
     * @return array<string, string>
     */
    public static function getStatuses(): array
    {
        return [
            'planned' => 'Запланировано',
            'in_production' => 'В производстве',
            'produced' => 'Произведено',
            'shipped' => 'Отгружено',
        ];
    }
}
