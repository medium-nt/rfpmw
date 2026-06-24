<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\Project;
use App\Models\ProjectItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectItem>
 */
class ProjectItemFactory extends Factory
{
    /**
     * Состояние по умолчанию модели.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'item_id' => Item::factory(),
            'quantity' => fake()->optional()->numberBetween(1, 1000),
            'price' => fake()->optional()->randomFloat(2, 0, 1000),
            'status' => null,
            'production_start_date' => fake()->optional()->date(),
        ];
    }
}
