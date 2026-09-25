<?php

namespace Database\Factories;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
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
            'batch_id' => null,
            'type' => 'received',
            'quantity' => 1,
            'unit_cost' => 0,
            'total_cost' => 0,
            'transaction_date' => now()->toDateString(),
            'profile_id' => 1,
            'ward_id' => null,
            'remark' => null,
            'override_reason' => null,
            'reverses_transaction_id' => null,
        ];
    }
}
