<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class VendorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'   => fake()->unique()->company(),
            'code'   => strtoupper(fake()->unique()->bothify('VEN-###')),
            'type'   => fake()->randomElement(['company', 'individual']),
            'phone'  => fake()->optional()->phoneNumber(),
            'email'  => fake()->optional()->companyEmail(),
            'address' => fake()->optional()->address(),
            'status' => 'active',
        ];
    }

    public function inactive(): static
    {
        return $this->state(['status' => 'inactive']);
    }

    public function company(): static
    {
        return $this->state(['type' => 'company']);
    }

    public function individual(): static
    {
        return $this->state(['type' => 'individual']);
    }
}
