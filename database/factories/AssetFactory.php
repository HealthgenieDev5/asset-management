<?php

namespace Database\Factories;

use App\Models\AssetCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssetFactory extends Factory
{
    public function definition(): array
    {
        $category = AssetCategory::factory()->create();

        return [
            'asset_code'      => $category->code . '-' . fake()->unique()->numberBetween(1, 9999),
            'asset_name'      => fake()->words(3, true),
            'asset_category_id' => $category->id,
            'serial_number'   => fake()->optional()->bothify('SN-#####??'),
            'manufacturer'    => fake()->optional()->company(),
            'model'           => fake()->optional()->bothify('MDL-##??'),
            'location'        => fake()->optional()->city(),
            'department'      => fake()->optional()->randomElement(['IT', 'Finance', 'Operations', 'HR', 'Admin']),
            'custodian'       => fake()->optional()->name(),
            'vendor_supplier' => fake()->optional()->company(),
            'bill_no'         => fake()->optional()->bothify('BILL-####'),
            'bill_amount'     => fake()->optional()->randomFloat(2, 1000, 500000),
            'bill_date'       => fake()->optional()->dateTimeBetween('-3 years', 'now'),
            'purchase_date'   => fake()->optional()->dateTimeBetween('-3 years', 'now'),
            'status'          => 'active',
        ];
    }

    public function withWarranty(int $daysFromNow = 30): static
    {
        return $this->state([
            'warranty_lapse_date'         => now()->addDays($daysFromNow)->toDateString(),
            'warranty_reminder_before_days' => 7,
        ]);
    }

    public function expiredWarranty(): static
    {
        return $this->state([
            'warranty_lapse_date'         => now()->subDays(10)->toDateString(),
            'warranty_reminder_before_days' => 7,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(['status' => 'inactive']);
    }

    public function disposed(): static
    {
        return $this->state(['status' => 'disposed']);
    }

    public function withPurchaseDate(\DateTimeInterface|string $date): static
    {
        return $this->state(['purchase_date' => $date]);
    }
}
