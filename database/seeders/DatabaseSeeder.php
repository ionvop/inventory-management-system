<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Item;
use App\Models\Transaction;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $maria = User::create(['username' => 'maria']);
        $juan = User::create(['username' => 'juan']);

        $sugar = Item::create(['name' => 'Sugar', 'unit' => 'kg', 'minimum_stock' => 10]);
        $salt = Item::create(['name' => 'Salt', 'unit' => 'kg', 'minimum_stock' => 5]);

        Transaction::create(['item_id' => $sugar->id, 'user_id' => $maria->id, 'movement' => 'in', 'quantity' => 20, 'posted_at' => now()->subDays(5)]);
        Transaction::create(['item_id' => $sugar->id, 'user_id' => $juan->id, 'movement' => 'out', 'quantity' => 15, 'posted_at' => now()->subDay()]);
    }
}
