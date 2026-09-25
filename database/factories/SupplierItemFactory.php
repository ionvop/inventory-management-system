<?php

namespace Database\Factories;

use App\Models\SupplierItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupplierItem>
 */
class SupplierItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'supplier_id' => 1,
            'item_id' => 1,
            'price' => fake()->randomFloat(2, 10, 500),
            'effective_date' => now()->toDateString(),
            'active' => true,
        ];
    }
}
