<?php

namespace Database\Factories;

use App\Models\EmployedPerson;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    /**
     * Состояние по умолчанию модели.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'employed_person_id' => EmployedPerson::factory(),
            'event_type' => fake()->randomElement(['call', 'email', 'meeting']),
            'date' => fake()->date(),
            'subject' => fake()->optional()->sentence(),
            'description' => fake()->optional()->paragraph(),
            'project_id' => null,
            'request_id' => null,
            'proposal_id' => null,
        ];
    }
}
