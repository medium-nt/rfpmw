<?php

namespace Tests\Feature;

use App\Models\Contractor;
use App\Models\Item;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Артикул создаётся через factory с привязкой к вендору.
     */
    public function test_can_create_item(): void
    {
        $item = Item::factory()->create();

        $this->assertDatabaseHas('items', ['id' => $item->id]);
        $this->assertInstanceOf(Contractor::class, $item->vendor);
    }

    /**
     * Пара артикул + вендор уникальна.
     */
    public function test_sku_vendor_pair_is_unique(): void
    {
        $vendor = Contractor::factory()->vendor()->create();

        Item::factory()->create(['sku' => 'STM32F103', 'vendor_id' => $vendor->id]);

        $this->expectException(QueryException::class);

        Item::factory()->create(['sku' => 'STM32F103', 'vendor_id' => $vendor->id]);
    }

    /**
     * Разные вендоры позволяют одноимённые артикулы.
     */
    public function test_same_sku_different_vendor_is_allowed(): void
    {
        $vendor1 = Contractor::factory()->vendor()->create();
        $vendor2 = Contractor::factory()->vendor()->create();

        Item::factory()->create(['sku' => 'LM358', 'vendor_id' => $vendor1->id]);
        Item::factory()->create(['sku' => 'LM358', 'vendor_id' => $vendor2->id]);

        $this->assertSame(2, Item::count());
    }

    /**
     * При удалении вендора vendor_id артикула обнуляется (SET NULL).
     */
    public function test_vendor_id_becomes_null_on_vendor_delete(): void
    {
        $item = Item::factory()->create();
        $vendor = $item->vendor;

        $vendor->forceDelete();

        $this->assertNull($item->fresh()->vendor_id);
    }
}
