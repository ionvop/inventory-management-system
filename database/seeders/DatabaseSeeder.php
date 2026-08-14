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
        $maria = User::create(['username' => 'maria', 'time' => now()->timestamp]);
        $juan = User::create(['username' => 'juan', 'time' => now()->timestamp]);

        $sugar = Item::create(['name' => 'Sugar', 'unit' => 'kg', 'minimum_stock' => 10, 'time' => now()->timestamp]);
        $salt = Item::create(['name' => 'Salt', 'unit' => 'kg', 'minimum_stock' => 5, 'time' => now()->timestamp]);

        Transaction::create(['item_id' => $sugar->id, 'user_id' => $maria->id, 'movement' => 'in', 'quantity' => 20, 'posted_time' => now()->subDays(5)->timestamp, 'time' => now()->timestamp]);
        Transaction::create(['item_id' => $sugar->id, 'user_id' => $juan->id, 'movement' => 'out', 'quantity' => 15, 'posted_time' => now()->subDay()->timestamp, 'time' => now()->timestamp]);
    }
}
