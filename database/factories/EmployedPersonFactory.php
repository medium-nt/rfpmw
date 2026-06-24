<?php

namespace Database\Factories;

use App\Models\ContactPerson;
use App\Models\Contractor;
use App\Models\EmployedPerson;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployedPerson>
 */
class EmployedPersonFactory extends Factory
{
    /**
     * Состояние по умолчанию модели.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contact_person_id' => ContactPerson::factory(),
            'contractor_id' => Contractor::factory(),
            'position' => fake()->optional()->jobTitle(),
        ];
    }
}
