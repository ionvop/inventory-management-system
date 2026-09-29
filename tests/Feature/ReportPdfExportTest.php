<?php

use App\Models\Item;
use App\Models\Period;
use App\Models\Profile;
use App\Models\Supplier;
use App\Models\SupplierItem;
use App\Models\Transaction;

/**
 * Create an active supplier item with a contract price in effect.
 */
function pdfSupplierItem(): SupplierItem
{
    $supplier = Supplier::factory()->create();
    $item = Item::factory()->create();

    return SupplierItem::factory()->create([
        'supplier_id' => $supplier->id,
        'item_id' => $item->id,
        'price' => 100,
        'effective_date' => '2026-01-01',
        'active' => true,
    ]);
}

/**
 * Record a movement in a period for a supplier item.
 */
function pdfMovement(Period $period, SupplierItem $supplierItem, Profile $profile, string $type, float $quantity): Transaction
{
    return Transaction::factory()->create([
        'period_id' => $period->id,
        'supplier_item_id' => $supplierItem->id,
        'type' => $type,
        'quantity' => $quantity,
        'unit_cost' => 100,
        'total_cost' => $quantity * 100,
        'transaction_date' => sprintf('%04d-%02d-05', $period->year, $period->month),
        'profile_id' => $profile->id,
    ]);
}

test('a supervisor can export the monthly report to PDF', function () {
    $supervisor = Profile::factory()->supervisor()->create();
    $period = Period::factory()->create(['year' => 2026, 'month' => 7]);

    $response = $this->withSession(['active_profile_id' => $supervisor->id])
        ->get(route('reports.exportPdf', ['period_id' => $period->id]));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
    $response->assertDownload('inventory-report-2026-07.pdf');
});

test('a staff profile cannot export the monthly report to PDF', function () {
    $staff = Profile::factory()->create();
    $period = Period::factory()->create(['year' => 2026, 'month' => 7]);

    $this->withSession(['active_profile_id' => $staff->id])
        ->get(route('reports.exportPdf', ['period_id' => $period->id]))
        ->assertForbidden();
});

test('exporting a PDF without any period returns not found', function () {
    $supervisor = Profile::factory()->supervisor()->create();

    $this->withSession(['active_profile_id' => $supervisor->id])
        ->get(route('reports.exportPdf'))
        ->assertNotFound();
});

test('the exported file is a valid PDF document', function () {
    $supervisor = Profile::factory()->supervisor()->create();
    $period = Period::factory()->create(['year' => 2026, 'month' => 7]);
    $supplierItem = pdfSupplierItem();

    pdfMovement($period, $supplierItem, $supervisor, 'received', 300);
    pdfMovement($period, $supplierItem, $supervisor, 'consumption', 112);

    $response = $this->withSession(['active_profile_id' => $supervisor->id])
        ->get(route('reports.exportPdf', ['period_id' => $period->id]));

    $response->assertOk();

    $path = $response->baseResponse->getFile()->getPathname();

    expect(file_get_contents($path, length: 5))->toBe('%PDF-');
});
