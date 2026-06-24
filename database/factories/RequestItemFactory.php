<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\Request;
use App\Models\RequestItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RequestItem>
 */
class RequestItemFactory extends Factory
{
    /**
     * Состояние по умолчанию модели.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'request_id' => Request::factory(),
            'item_id' => Item::factory(),
            'quantity' => fake()->numberBetween(1, 1000),
            'price' => fake()->optional()->randomFloat(2, 0, 1000),
        ];
    }
}
