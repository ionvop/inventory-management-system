<?php

use App\Models\AuditLog;
use App\Models\Item;
use App\Models\Period;
use App\Models\Profile;
use App\Models\Supplier;
use App\Models\SupplierItem;
use App\Models\Transaction;
use App\Services\BalanceService;

/**
 * Create an active supplier item with a contract price in effect.
 */
function reversalSupplierItem(array $attributes = []): SupplierItem
{
    $supplier = Supplier::factory()->create();
    $item = Item::factory()->create();

    return SupplierItem::factory()->create(array_merge([
        'supplier_id' => $supplier->id,
        'item_id' => $item->id,
        'price' => 100,
        'effective_date' => '2026-01-01',
        'active' => true,
    ], $attributes));
}

/**
 * Record a received movement dated today so it lands in the current period.
 */
function recordReceived(Profile $profile, SupplierItem $supplierItem, float $quantity = 10): Transaction
{
    test()->withSession(['active_profile_id' => $profile->id])
        ->post(route('transactions.store'), [
            'supplier_item_id' => $supplierItem->id,
            'type' => 'received',
            'quantity' => $quantity,
            'transaction_date' => now()->toDateString(),
            'batch_number' => 'BATCH-001',
            'expiration_date' => now()->addYear()->toDateString(),
        ])
        ->assertRedirectBack();

    return Transaction::query()->latest('id')->sole();
}

test('an administrator can reverse a transaction', function () {
    $admin = Profile::factory()->administrator()->create();
    $supplierItem = reversalSupplierItem();
    $original = recordReceived($admin, $supplierItem, 10);

    $this->withSession(['active_profile_id' => $admin->id])
        ->post(route('transactions.reverse', $original->id), [
            'remark' => 'Recorded against the wrong item.',
        ])
        ->assertRedirectBack();

    $reversal = Transaction::query()
        ->where('reverses_transaction_id', $original->id)
        ->sole();

    expect($reversal->type)->toBe($original->type);
    expect((float) $reversal->quantity)->toBe(10.0);
    expect((float) $reversal->unit_cost)->toBe((float) $original->unit_cost);
    expect($reversal->profile_id)->toBe($admin->id);
    expect($reversal->remark)->toBe('Recorded against the wrong item.');
});

test('a reversal nets the balance back to zero', function () {
    $admin = Profile::factory()->administrator()->create();
    $supplierItem = reversalSupplierItem(['price' => 50]);
    $original = recordReceived($admin, $supplierItem, 10);

    $period = Period::query()->sole();
    $balances = app(BalanceService::class);

    expect($balances->balanceFor($supplierItem->id, $period->id)['quantity'])->toBe(10.0);

    $this->withSession(['active_profile_id' => $admin->id])
        ->post(route('transactions.reverse', $original->id), [
            'remark' => 'Duplicate entry.',
        ])
        ->assertRedirectBack();

    $balance = $balances->balanceFor($supplierItem->id, $period->id);

    expect($balance['quantity'])->toBe(0.0);
    expect($balance['total_cost'])->toBe(0.0);
});

test('a non-administrator cannot reverse a transaction', function () {
    $staff = Profile::factory()->create();
    $admin = Profile::factory()->administrator()->create();
    $supplierItem = reversalSupplierItem();
    $original = recordReceived($admin, $supplierItem, 10);

    $this->withSession(['active_profile_id' => $staff->id])
        ->post(route('transactions.reverse', $original->id), [
            'remark' => 'Trying to reverse.',
        ])
        ->assertForbidden();

    expect(Transaction::query()->where('reverses_transaction_id', $original->id)->exists())->toBeFalse();
});

test('a transaction cannot be reversed twice', function () {
    $admin = Profile::factory()->administrator()->create();
    $supplierItem = reversalSupplierItem();
    $original = recordReceived($admin, $supplierItem, 10);

    $this->withSession(['active_profile_id' => $admin->id])
        ->post(route('transactions.reverse', $original->id), ['remark' => 'First.'])
        ->assertRedirectBack();

    $this->withSession(['active_profile_id' => $admin->id])
        ->post(route('transactions.reverse', $original->id), ['remark' => 'Second.'])
        ->assertSessionHasErrors('transaction');

    expect(Transaction::query()->where('reverses_transaction_id', $original->id)->count())->toBe(1);
});

test('a reversal transaction cannot itself be reversed', function () {
    $admin = Profile::factory()->administrator()->create();
    $supplierItem = reversalSupplierItem();
    $original = recordReceived($admin, $supplierItem, 10);

    $this->withSession(['active_profile_id' => $admin->id])
        ->post(route('transactions.reverse', $original->id), ['remark' => 'Reverse it.'])
        ->assertRedirectBack();

    $reversal = Transaction::query()->where('reverses_transaction_id', $original->id)->sole();

    $this->withSession(['active_profile_id' => $admin->id])
        ->post(route('transactions.reverse', $reversal->id), ['remark' => 'Reverse the reversal.'])
        ->assertSessionHasErrors('transaction');

    expect(Transaction::query()->count())->toBe(2);
});

test('a reversal requires a reason', function () {
    $admin = Profile::factory()->administrator()->create();
    $supplierItem = reversalSupplierItem();
    $original = recordReceived($admin, $supplierItem, 10);

    $this->withSession(['active_profile_id' => $admin->id])
        ->post(route('transactions.reverse', $original->id), [])
        ->assertSessionHasErrors('remark');

    expect(Transaction::query()->count())->toBe(1);
});

test('reversing a transaction writes audit logs for both rows', function () {
    $admin = Profile::factory()->administrator()->create();
    $supplierItem = reversalSupplierItem();
    $original = recordReceived($admin, $supplierItem, 10);

    AuditLog::query()->delete();

    $this->withSession(['active_profile_id' => $admin->id])
        ->post(route('transactions.reverse', $original->id), ['remark' => 'Correction.'])
        ->assertRedirectBack();

    $reversal = Transaction::query()->where('reverses_transaction_id', $original->id)->sole();

    $reversalLog = AuditLog::query()
        ->where('auditable_type', Transaction::class)
        ->where('auditable_id', $reversal->id)
        ->sole();

    expect($reversalLog->action)->toBe('reverse');
    expect($reversalLog->profile_id)->toBe($admin->id);
    expect($reversalLog->after['reverses_transaction_id'])->toBe($original->id);

    $originalLog = AuditLog::query()
        ->where('auditable_type', Transaction::class)
        ->where('auditable_id', $original->id)
        ->sole();

    expect($originalLog->action)->toBe('reverse');
    expect($originalLog->after['reversed_by_transaction_id'])->toBe($reversal->id);
});

test('the transaction screen flags reversals and reversed rows', function () {
    $admin = Profile::factory()->administrator()->create();
    $supplierItem = reversalSupplierItem();
    $original = recordReceived($admin, $supplierItem, 10);

    $this->withSession(['active_profile_id' => $admin->id])
        ->post(route('transactions.reverse', $original->id), ['remark' => 'Correction.'])
        ->assertRedirectBack();

    $this->withSession(['active_profile_id' => $admin->id])
        ->get(route('transactions.index'))
        ->assertInertia(function ($page) use ($original) {
            $page->component('Transactions');
            $page->where('canReverse', true);
            $page->where('recentTransactions.0.reverses_transaction_id', $original->id);
            $page->where('recentTransactions.1.is_reversed', true);
        });
});
