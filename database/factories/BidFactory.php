<?php

namespace Database\Factories;

use App\Models\Bid;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bid>
 */
class BidFactory extends Factory
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
            'bid_quantity' => fake()->randomFloat(2, 100, 10000),
            'set_date' => now()->toDateString(),
            'profile_id' => null,
        ];
    }
}
