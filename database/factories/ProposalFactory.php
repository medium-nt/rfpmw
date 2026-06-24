<?php

namespace Database\Factories;

use App\Models\EmployedPerson;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Proposal>
 */
class ProposalFactory extends Factory
{
    /**
     * Состояние по умолчанию модели.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employed_person_id' => EmployedPerson::factory(),
            'user_id' => User::factory(),
            'date' => fake()->date(),
            'usd_value' => fake()->randomFloat(2, 0, 100000),
            'status' => null,
        ];
    }
}
