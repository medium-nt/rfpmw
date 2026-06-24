<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Proposal;
use App\Models\ProposalItem;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProposalTest extends TestCase
{
    use RefreshDatabase;

    /**
     * КП создаётся через factory.
     */
    public function test_can_create_proposal(): void
    {
        $proposal = Proposal::factory()->create();

        $this->assertDatabaseHas('proposals', ['id' => $proposal->id]);
    }

    /**
     * Позиция КП требует цену (NOT NULL по ТЗ).
     */
    public function test_proposal_item_requires_price(): void
    {
        $proposal = Proposal::factory()->create();
        $item = Item::factory()->create();

        $this->expectException(QueryException::class);

        ProposalItem::factory()->create([
            'proposal_id' => $proposal->id,
            'item_id' => $item->id,
            'price' => null,
        ]);
    }

    /**
     * Позиции КП каскадно удаляются при жёстком удалении КП.
     */
    public function test_proposal_items_cascade_on_proposal_force_delete(): void
    {
        $proposal = Proposal::factory()->create();
        $item = Item::factory()->create();
        ProposalItem::factory()->create([
            'proposal_id' => $proposal->id,
            'item_id' => $item->id,
        ]);

        $proposal->forceDelete();

        $this->assertDatabaseMissing('proposal_items', ['proposal_id' => $proposal->id]);
    }
}
