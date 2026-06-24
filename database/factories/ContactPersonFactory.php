<?php

namespace Database\Factories;

use App\Models\ContactPerson;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactPerson>
 */
class ContactPersonFactory extends Factory
{
    /**
     * Состояние по умолчанию модели.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'fio' => fake()->name(),
            'phone' => fake()->optional()->phoneNumber(),
            'email' => fake()->optional()->safeEmail(),
            'interests' => fake()->optional()->sentence(),
        ];
    }
}
