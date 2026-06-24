<?php

namespace Database\Factories;

use App\Models\Contractor;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Состояние по умолчанию модели.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contractor_id' => Contractor::factory(),
            'responsible_person_id' => null,
            'name' => fake()->catchPhrase(),
            'description' => fake()->optional()->paragraph(),
            'date' => fake()->date(),
            'usd_value' => fake()->randomFloat(2, 0, 100000),
            'status' => null,
        ];
    }
}
