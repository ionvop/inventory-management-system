<?php

namespace App\Console\Commands;

use App\Models\Item;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class SeedDemoData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:seed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed demo items and an extensive history of transactions for testing date filtering (only when items and transactions are empty)';

    /**
     * Fixed seed so the generated data is reproducible across runs.
     */
    private const RNG_SEED = 20260901;

    /**
     * Common inventory items: [name, unit, minimum_stock].
     *
     * @var array<int, array{0: string, 1: string, 2: int}>
     */
    private const ITEMS = [
        ['Sugar', 'kg', 10],
        ['Salt', 'kg', 5],
        ['Rice', 'kg', 20],
        ['Cooking Oil', 'L', 8],
        ['Coffee', 'g', 15],
        ['Flour', 'kg', 12],
    ];

    /**
     * Number of transactions to generate.
     */
    private const TRANSACTION_COUNT = 500;

    /**
     * How far back (in days) the seeded history should reach.
     */
    private const HISTORY_DAYS = 365;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (Item::exists() || Transaction::exists()) {
            $this->error('Demo data already exists. This command only runs when both items and transactions are empty.');

            return self::FAILURE;
        }

        $users = $this->seedUsers();
        $items = $this->seedItems();
        $this->seedTransactions($users, $items);

        $this->info(sprintf(
            'Seeded %d items, %d users, and %d transactions spanning the past %d days.',
            $items->count(),
            $users->count(),
            Transaction::count(),
            self::HISTORY_DAYS,
        ));

        return self::SUCCESS;
    }

    /**
     * Reuse existing users, or create a few deterministic ones if none exist.
     *
     * @return Collection<int, User>
     */
    private function seedUsers(): Collection
    {
        $existing = User::orderBy('id')->get();

        if ($existing->isNotEmpty()) {
            return $existing;
        }

        $names = ['maria', 'juan', 'ana', 'luis'];
        $users = [];

        foreach ($names as $name) {
            $users[] = User::create(['username' => $name]);
        }

        return collect($users);
    }

    /**
     * Create the fixed set of common items.
     *
     * @return Collection<int, Item>
     */
    private function seedItems(): Collection
    {
        $items = [];

        foreach (self::ITEMS as [$name, $unit, $minimumStock]) {
            $items[] = Item::create([
                'name' => $name,
                'unit' => $unit,
                'minimum_stock' => $minimumStock,
            ]);
        }

        return collect($items);
    }

    /**
     * Generate a deterministic, realistic history of transactions.
     *
     * Each item gets a simulated stock sequence that never goes negative,
     * spread across the past HISTORY_DAYS days (slightly weighted toward
     * recent activity so "recent" filters still return meaningful data).
     *
     * @param  Collection<int, User>  $users
     * @param  Collection<int, Item>  $items
     */
    private function seedTransactions(Collection $users, Collection $items): void
    {
        mt_srand(self::RNG_SEED);

        $now = now();
        $userIds = $users->pluck('id')->all();
        $rows = [];

        // Distribute the total across items, spreading the remainder so the
        // final count is exactly TRANSACTION_COUNT.
        $base = (int) floor(self::TRANSACTION_COUNT / $items->count());
        $remainder = self::TRANSACTION_COUNT % $items->count();

        foreach ($items as $index => $item) {
            $balance = 0;
            $itemCount = $base + ($index < $remainder ? 1 : 0);

            for ($i = 0; $i < $itemCount; $i++) {
                // Weighted random day: bias toward the present, but still
                // spread across the full history so date filters return
                // meaningful subsets for any range.

                $weight = mt_rand(0, 1000) / 1000;
                $dayOffset = (int) round(pow($weight, 2) * self::HISTORY_DAYS);
                $postedAt = $now->copy()->subDays($dayOffset)->setTime(
                    mt_rand(0, 23),
                    mt_rand(0, 59),
                    mt_rand(0, 59),
                );

                // Keep the simulated stock non-negative: an "out" can never
                // exceed the current balance, and we occasionally restock.

                $movement = 'in';
                $quantity = mt_rand(1, 25);

                if ($balance > 0 && mt_rand(0, 1) === 1) {
                    $movement = 'out';
                    $quantity = mt_rand(1, min($balance, 20));
                }

                $balance += $movement === 'in' ? $quantity : -$quantity;

                $rows[] = [
                    'item_id' => $item->id,
                    'user_id' => $userIds[mt_rand(0, count($userIds) - 1)],
                    'movement' => $movement,
                    'quantity' => $quantity,
                    'posted_at' => $postedAt,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        // Insert in chunks to keep memory bounded.

        foreach (array_chunk($rows, 500) as $chunk) {
            Transaction::insert($chunk);
        }
    }
}
