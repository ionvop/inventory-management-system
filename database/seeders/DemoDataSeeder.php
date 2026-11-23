<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Batch;
use App\Models\Bid;
use App\Models\Item;
use App\Models\Period;
use App\Models\Profile;
use App\Models\Supplier;
use App\Models\SupplierItem;
use App\Models\Transaction;
use App\Models\Ward;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Seeds a complete demo dataset for local development and evaluation.
 *
 * Intended for a fresh migration only: the unique constraints on periods,
 * supplier items and wards make a second run fail. Use
 * `php artisan migrate:fresh --seed` to rebuild the dataset.
 */
class DemoDataSeeder extends Seeder
{
    use WithoutModelEvents;

    /** The exact number of transactions this seeder creates. */
    private const int TRANSACTION_COUNT = 100;

    /** The opening stock recorded for each supplier item in each period. */
    private const float OPENING_QUANTITY = 200.0;

    /** @var array<int, Profile> */
    private array $profiles = [];

    /** @var array<int, SupplierItem> */
    private array $supplierItems = [];

    /** @var array<int, Ward> */
    private array $wards = [];

    /** @var array<int, Period> */
    private array $periods = [];

    /** @var array<int, array<int, Batch>> Batches keyed by supplier item id. */
    private array $batches = [];

    /** @var array<int, Transaction> */
    private array $transactions = [];

    /** @var array<int, Model> Persisted subjects for the audit trail. */
    private array $auditable = [];

    /**
     * Seed the application's database with a coherent demo dataset.
     */
    public function run(): void
    {
        $this->seedProfiles();
        $this->seedCatalog();
        $this->seedPeriodsAndBatches();
        $this->seedBids();
        $this->seedTransactions();
        $this->seedReversal();
        $this->seedAuditTrail();
    }

    /**
     * Create the test accounts, including one soft-deleted historical profile.
     */
    private function seedProfiles(): void
    {
        $this->profiles = [
            Profile::factory()->create(['name' => 'Maria Santos', 'role' => 'staff']),
            Profile::factory()->supervisor()->create(['name' => 'Jose Rizal']),
            Profile::factory()->administrator()->create(['name' => 'Ana Cruz']),
            Profile::factory()->create(['name' => 'Lena Torres', 'role' => 'staff']),
            Profile::factory()->create(['name' => 'Ramon Reyes', 'role' => 'staff']),
        ];

        // A soft-deleted profile still appears in historical attribution (FR-1.5).
        $this->profiles[4]->delete();

        foreach ($this->profiles as $profile) {
            $this->auditable[] = $profile;
        }
    }

    /**
     * Create the suppliers, items, contract prices and wards used by the demo.
     */
    private function seedCatalog(): void
    {
        /** @var array<int, array<string, string>> $supplierData */
        $supplierData = [
            ['name' => 'Zuellig Pharma Corporation', 'contract_status' => 'Active'],
            ['name' => 'DSPI', 'contract_status' => 'Active'],
            ['name' => 'RBC-MDC Corporation', 'contract_status' => 'Active'],
        ];

        /** @var array<int, array{code: string, description: string, unit: string, price: float}> $itemData */
        $itemData = [
            ['code' => 'CONMED0367', 'description' => 'Ensure Gold, 1.6kg', 'unit' => 'can', 'price' => 2122.90],
            ['code' => 'CONMED0370', 'description' => 'Pediasure Plus, 370g', 'unit' => 'sach', 'price' => 456.32],
            ['code' => 'CONMED0196', 'description' => 'Alitraq, 76g', 'unit' => 'sach', 'price' => 212.86],
            ['code' => 'CONMED0219', 'description' => 'Nepro HP, 237mL', 'unit' => 'can', 'price' => 155.00],
            ['code' => 'CONMED0141', 'description' => 'Peptamen', 'unit' => 'can', 'price' => 1340.92],
        ];

        // Supplier/item pairs. Peptamen appears under two suppliers, mirroring
        // the report fixture where the same item code has two contract prices.
        /** @var array<int, array{0: int, 1: int}> $pairs */
        $pairs = [
            [0, 0],
            [0, 1],
            [0, 2],
            [0, 3],
            [1, 4],
            [2, 4],
        ];

        /** @var array<int, Supplier> $suppliers */
        $suppliers = [];
        foreach ($supplierData as $data) {
            $supplier = Supplier::factory()->create($data);
            $suppliers[] = $supplier;
            $this->auditable[] = $supplier;
        }

        /** @var array<int, Item> $items */
        $items = [];
        foreach ($itemData as $data) {
            $item = Item::factory()->create([
                'code' => $data['code'],
                'description' => $data['description'],
                'unit' => $data['unit'],
                'active' => true,
            ]);
            $items[] = $item;
            $this->auditable[] = $item;
        }

        $effectiveDate = Carbon::now()->subYear()->toDateString();
        foreach ($pairs as [$supplierIndex, $itemIndex]) {
            $supplierItem = SupplierItem::factory()->create([
                'supplier_id' => $suppliers[$supplierIndex]->id,
                'item_id' => $items[$itemIndex]->id,
                'price' => $itemData[$itemIndex]['price'],
                'effective_date' => $effectiveDate,
                'active' => true,
            ]);
            $this->supplierItems[] = $supplierItem;
            $this->auditable[] = $supplierItem;
        }

        foreach (['Pediatric Ward', 'ICU'] as $name) {
            $this->wards[] = Ward::factory()->create(['name' => $name, 'active' => true]);
        }
    }

    /**
     * Open the periods covering the window and create a varied batch pool.
     */
    private function seedPeriodsAndBatches(): void
    {
        $start = Carbon::now()->subDays(29)->startOfDay();
        $end = Carbon::now()->startOfDay();

        $firstPeriod = Period::forDate($start);
        $lastPeriod = Period::forDate($end);

        $this->periods = $firstPeriod->is($lastPeriod)
            ? [$firstPeriod]
            : [$firstPeriod, $lastPeriod];

        foreach ($this->periods as $period) {
            $this->auditable[] = $period;
        }

        foreach ($this->supplierItems as $supplierItem) {
            $batches = [
                Batch::factory()->create([
                    'supplier_item_id' => $supplierItem->id,
                    'batch_number' => 'DEMO-ACTIVE-'.$supplierItem->id,
                    'expiration_date' => Carbon::now()->addMonths(6)->toDateString(),
                    'status' => Batch::STATUS_ACTIVE,
                ]),
                Batch::factory()->create([
                    'supplier_item_id' => $supplierItem->id,
                    'batch_number' => 'DEMO-NEAR-'.$supplierItem->id,
                    'expiration_date' => Carbon::now()->addDays(30)->toDateString(),
                    'status' => Batch::STATUS_ACTIVE,
                ]),
                Batch::factory()->create([
                    'supplier_item_id' => $supplierItem->id,
                    'batch_number' => 'DEMO-EXPIRED-'.$supplierItem->id,
                    'expiration_date' => Carbon::now()->subDays(5)->toDateString(),
                    'status' => Batch::STATUS_ACTIVE,
                ]),
                Batch::factory()->create([
                    'supplier_item_id' => $supplierItem->id,
                    'batch_number' => 'DEMO-DAMAGED-'.$supplierItem->id,
                    'expiration_date' => Carbon::now()->addMonths(3)->toDateString(),
                    'status' => Batch::STATUS_DAMAGED,
                    'damaged_reason' => 'Damaged during handling',
                ]),
            ];

            $this->batches[$supplierItem->id] = $batches;
            $this->auditable[] = $batches[0];
        }
    }

    /**
     * Seed bids for the first three pairs, including one due for reorder.
     */
    private function seedBids(): void
    {
        $bidQuantities = [1300, 5000, 1500];

        foreach ($bidQuantities as $index => $quantity) {
            $supplierItem = $this->supplierItems[$index];

            Bid::factory()->create([
                'supplier_id' => $supplierItem->supplier_id,
                'item_id' => $supplierItem->item_id,
                'bid_quantity' => $quantity,
                'set_date' => Carbon::now()->subDays(30)->toDateString(),
                'profile_id' => $this->profiles[1]->id,
            ]);
        }
    }

    /**
     * Record the transactions, front-loading receipts so balances stay positive.
     */
    private function seedTransactions(): void
    {
        $start = Carbon::now()->subDays(29)->startOfDay();
        $receiptCount = count($this->supplierItems) * count($this->periods);
        $movementCount = self::TRANSACTION_COUNT - $receiptCount - 1;

        /** @var array<string, float> $running Running quantity per supplier item and period. */
        $running = [];

        foreach ($this->periods as $period) {
            foreach ($this->supplierItems as $supplierItem) {
                $date = $this->firstDateInPeriod($period, $start);
                $unitCost = (float) $supplierItem->price;

                $transaction = Transaction::factory()->create([
                    'period_id' => $period->id,
                    'supplier_item_id' => $supplierItem->id,
                    'batch_id' => $this->batches[$supplierItem->id][0]->id,
                    'type' => 'received',
                    'quantity' => self::OPENING_QUANTITY,
                    'unit_cost' => $unitCost,
                    'total_cost' => round(self::OPENING_QUANTITY * $unitCost, 2),
                    'transaction_date' => $date->toDateString(),
                    'profile_id' => $this->profiles[0]->id,
                ]);

                $this->transactions[] = $transaction;
                $this->auditable[] = $transaction;
                $running[$this->runningKey($supplierItem->id, $period->id)] = self::OPENING_QUANTITY;
            }
        }

        /** @var array<int, string> $types */
        $types = [
            'consumption',
            'return_from_ward',
            'return_to_supplier',
            'transfer_to_pharmacy',
            'write_off',
        ];

        for ($i = 0; $i < $movementCount; $i++) {
            $date = $start->copy()->addDays($i % 30);
            $period = Period::forDate($date);
            $supplierItem = $this->supplierItems[$i % count($this->supplierItems)];
            $type = $types[$i % count($types)];
            $profile = $this->profiles[$i % count($this->profiles)];
            $key = $this->runningKey($supplierItem->id, $period->id);

            $quantity = (float) fake()->numberBetween(1, 12);
            if ($type !== 'return_from_ward') {
                $quantity = min($quantity, $running[$key] ?? 0.0);
            }
            $quantity = max(1.0, round($quantity, 2));

            $running[$key] = ($running[$key] ?? 0.0)
                + ($type === 'return_from_ward' ? $quantity : -$quantity);

            $unitCost = (float) $supplierItem->price;
            $batchId = null;
            $wardId = null;
            $remark = null;

            if ($type === 'write_off') {
                $batchId = $this->batches[$supplierItem->id][2]->id;
                $remark = 'Expired stock pulled out for disposal';
            } elseif ($type === 'consumption' || $type === 'return_from_ward') {
                $wardId = $this->wards[$i % count($this->wards)]->id;
            }

            $transaction = Transaction::factory()->create([
                'period_id' => $period->id,
                'supplier_item_id' => $supplierItem->id,
                'batch_id' => $batchId,
                'type' => $type,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'total_cost' => round($quantity * $unitCost, 2),
                'transaction_date' => $date->toDateString(),
                'profile_id' => $profile->id,
                'ward_id' => $wardId,
                'remark' => $remark,
            ]);

            $this->transactions[] = $transaction;
        }
    }

    /**
     * Reverse one consumption to demonstrate the correction workflow (FR-4.4).
     */
    private function seedReversal(): void
    {
        $original = collect($this->transactions)
            ->first(fn (Transaction $transaction): bool => $transaction->type === 'consumption');

        if (! $original instanceof Transaction) {
            return;
        }

        $reversal = Transaction::factory()->create([
            'period_id' => Period::forDate(Carbon::now())->id,
            'supplier_item_id' => $original->supplier_item_id,
            'batch_id' => $original->batch_id,
            'type' => $original->type,
            'quantity' => $original->quantity,
            'unit_cost' => $original->unit_cost,
            'total_cost' => $original->total_cost,
            'transaction_date' => Carbon::now()->toDateString(),
            'profile_id' => $this->profiles[2]->id,
            'ward_id' => $original->ward_id,
            'remark' => 'Correction: consumption recorded against the wrong item',
            'reverses_transaction_id' => $original->id,
        ]);

        $this->transactions[] = $reversal;
        $this->auditable[] = $reversal;
    }

    /**
     * Write a modest audit trail over the persisted demo records (FR-8.1).
     */
    private function seedAuditTrail(): void
    {
        foreach ($this->auditable as $index => $model) {
            AuditLog::factory()->create([
                'profile_id' => $this->profiles[$index % count($this->profiles)]->id,
                'auditable_type' => $model->getMorphClass(),
                'auditable_id' => $model->getKey(),
                'action' => 'create',
                'before' => null,
                'after' => $model->only([
                    'name',
                    'code',
                    'description',
                    'batch_number',
                    'type',
                    'quantity',
                    'total_cost',
                    'year',
                    'month',
                    'price',
                ]),
            ]);
        }
    }

    /**
     * Find the first day of a period that falls inside the seeding window.
     */
    private function firstDateInPeriod(Period $period, Carbon $start): Carbon
    {
        $date = $start->copy();

        while ((int) $date->format('Y') !== $period->year || (int) $date->format('n') !== $period->month) {
            $date->addDay();
        }

        return $date;
    }

    /**
     * Build the in-memory running-stock key for a supplier item and period.
     */
    private function runningKey(int $supplierItemId, int $periodId): string
    {
        return $supplierItemId.':'.$periodId;
    }
}
