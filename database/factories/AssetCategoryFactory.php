<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AssetCategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'   => fake()->unique()->words(2, true),
            'code'   => strtoupper(fake()->unique()->lexify('?!')),
            'status' => 'active',
        ];
    }

    public function inactive(): static
    {
        return $this->state(['status' => 'inactive']);
    }
}
