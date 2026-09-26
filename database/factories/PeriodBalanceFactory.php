<?php

namespace Database\Factories;

use App\Models\PeriodBalance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PeriodBalance>
 */
class PeriodBalanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'period_id' => 1,
            'supplier_item_id' => 1,
            'beginning_quantity' => 0,
            'beginning_cost' => 0,
            'ending_quantity' => 0,
            'ending_cost' => 0,
        ];
    }
}
