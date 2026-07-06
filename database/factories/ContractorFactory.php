<?php

namespace Database\Factories;

use App\Models\Contractor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contractor>
 */
class ContractorFactory extends Factory
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
            'name' => fake()->company(),
            'inn' => fake()->unique()->numerify('############'),
            'legal_address' => fake()->optional()->address(),
            'actual_address' => fake()->optional()->address(),
            'phone' => fake()->optional()->phoneNumber(),
            'region' => fake()->optional()->city(),
            'industry' => fake()->optional()->word(),
            'parent_id' => null,
            'website' => fake()->optional()->url(),
            'type' => 'customer',
        ];
    }

    /**
     * Контрагент типа «заказчик» (значение по умолчанию).
     */
    public function customer(): static
    {
        return $this->state(['type' => 'customer']);
    }

    /**
     * Контрагент типа «партнёр».
     */
    public function partner(): static
    {
        return $this->state(['type' => 'partner']);
    }

    /**
     * Контрагент типа «поставщик».
     */
    public function supplier(): static
    {
        return $this->state(['type' => 'supplier']);
    }

    /**
     * Контрагент типа «вендор» (производитель товаров для справочника артикулов).
     */
    public function vendor(): static
    {
        return $this->state(['type' => 'vendor']);
    }
}
