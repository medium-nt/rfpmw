<?php

namespace Database\Factories;

use App\Models\Contractor;
use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Item>
 */
class ItemFactory extends Factory
{
    /**
     * Состояние по умолчанию модели.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sku' => fake()->bothify('???-#####'),
            'vendor_id' => Contractor::factory()->vendor()->create(),
            'description' => fake()->optional()->paragraph(),
        ];
    }
}
