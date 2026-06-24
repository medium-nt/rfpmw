<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Request;
use App\Models\RequestItem;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Запрос создаётся через factory.
     */
    public function test_can_create_request(): void
    {
        $request = Request::factory()->create();

        $this->assertDatabaseHas('requests', ['id' => $request->id]);
    }

    /**
     * Позиция запроса требует количество (NOT NULL).
     */
    public function test_request_item_requires_quantity(): void
    {
        $request = Request::factory()->create();
        $item = Item::factory()->create();

        $this->expectException(QueryException::class);

        RequestItem::factory()->create([
            'request_id' => $request->id,
            'item_id' => $item->id,
            'quantity' => null,
        ]);
    }

    /**
     * Позиции запроса каскадно удаляются при жёстком удалении запроса.
     */
    public function test_request_items_cascade_on_request_force_delete(): void
    {
        $request = Request::factory()->create();
        $item = Item::factory()->create();
        RequestItem::factory()->create([
            'request_id' => $request->id,
            'item_id' => $item->id,
        ]);

        $request->forceDelete();

        $this->assertDatabaseMissing('request_items', ['request_id' => $request->id]);
    }
}
