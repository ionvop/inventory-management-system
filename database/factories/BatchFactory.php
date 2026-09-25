<?php

namespace Database\Factories;

use App\Models\Batch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Batch>
 */
class BatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'supplier_item_id' => 1,
            'batch_number' => fake()->bothify('BATCH-####'),
            'expiration_date' => now()->addMonths(6)->toDateString(),
            'status' => 'active',
        ];
    }
}
