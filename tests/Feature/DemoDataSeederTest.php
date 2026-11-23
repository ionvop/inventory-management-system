<?php

use App\Models\AuditLog;
use App\Models\Batch;
use App\Models\Bid;
use App\Models\Period;
use App\Models\Profile;
use App\Models\SupplierItem;
use App\Models\Transaction;
use App\Services\BalanceService;
use App\Services\BidService;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Support\Carbon;

test('the demo seeder creates a coherent dataset', function () {
    $this->seed(DemoDataSeeder::class);

    $balanceService = app(BalanceService::class);
    $bidService = app(BidService::class);

    expect(Profile::query()->count())->toBe(4)
        ->and(Profile::withTrashed()->count())->toBe(5)
        ->and(Profile::withTrashed()->whereNotNull('deleted_at')->count())->toBe(1)
        ->and(Profile::withTrashed()->where('name', 'Maria Santos')->first()?->role)->toBe('staff')
        ->and(Profile::withTrashed()->where('name', 'Jose Rizal')->first()?->role)->toBe('supervisor')
        ->and(Profile::withTrashed()->where('name', 'Ana Cruz')->first()?->role)->toBe('administrator');

    expect(SupplierItem::query()->count())->toBe(6)
        ->and(Batch::query()->count())->toBe(24)
        ->and(Period::query()->count())->toBe(2)
        ->and(Period::query()->where('status', 'open')->count())->toBe(2)
        ->and(Bid::query()->count())->toBe(3)
        ->and(AuditLog::query()->count())->toBeGreaterThan(0);

    $transactions = Transaction::query()->get();

    expect($transactions)->toHaveCount(100);

    $allowedTypes = [
        'received',
        'consumption',
        'return_from_ward',
        'return_to_supplier',
        'transfer_to_pharmacy',
        'write_off',
    ];

    foreach ($transactions as $transaction) {
        expect($transaction->type)->toBeIn($allowedTypes)
            ->and(Period::query()->find($transaction->period_id))->not->toBeNull()
            ->and(SupplierItem::query()->find($transaction->supplier_item_id))->not->toBeNull()
            ->and(Profile::withTrashed()->find($transaction->profile_id))->not->toBeNull()
            ->and((float) $transaction->total_cost)->toBe(round((float) $transaction->quantity * (float) $transaction->unit_cost, 2))
            ->and($transaction->transaction_date->toDateString() >= Carbon::today()->subDays(29)->toDateString())->toBeTrue()
            ->and($transaction->transaction_date->toDateString() <= Carbon::today()->toDateString())->toBeTrue();
    }

    foreach (Period::query()->get() as $period) {
        expect($balanceService->negativeBalances($period->id))->toBeEmpty();
    }

    $reversal = $transactions->first(fn (Transaction $transaction): bool => $transaction->reverses_transaction_id !== null);

    expect($reversal)->not->toBeNull()
        ->and(Transaction::query()->find($reversal->reverses_transaction_id))->not->toBeNull()
        ->and($balanceService->signedQuantity(
            $reversal->type,
            (float) $reversal->quantity,
            true,
        ))->toBe(-$balanceService->signedQuantity($reversal->type, (float) $reversal->quantity));

    $statuses = Batch::query()->get()->map(fn (Batch $batch): string => $batch->expiryStatus())->unique()->values()->all();

    expect($statuses)->toContain(Batch::STATUS_ACTIVE)
        ->and($statuses)->toContain(Batch::STATUS_NEAR_EXPIRY)
        ->and($statuses)->toContain(Batch::STATUS_EXPIRED)
        ->and($statuses)->toContain(Batch::STATUS_DAMAGED);

    foreach (AuditLog::query()->get() as $log) {
        expect($log->auditable_id)->not->toBeNull()
            ->and($log->profile_id)->not->toBeNull();
    }

    $bids = Bid::query()->get()->map(fn (Bid $bid): bool => $bidService->needsReorder($bid))->all();

    expect($bids)->toContain(true);
});
