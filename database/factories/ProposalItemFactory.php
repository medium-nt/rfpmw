<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\Proposal;
use App\Models\ProposalItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProposalItem>
 */
class ProposalItemFactory extends Factory
{
    /**
     * Состояние по умолчанию модели.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'proposal_id' => Proposal::factory(),
            'item_id' => Item::factory(),
            'quantity' => fake()->numberBetween(1, 1000),
            'price' => fake()->randomFloat(2, 0, 1000),
        ];
    }
}
