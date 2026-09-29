<?php

use App\Models\Batch;
use App\Models\Item;
use App\Models\Period;
use App\Models\PeriodBalance;
use App\Models\Profile;
use App\Models\Supplier;
use App\Models\SupplierItem;
use App\Models\Transaction;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Create an active supplier item with a contract price in effect.
 */
function exportSupplierItem(array $attributes = []): SupplierItem
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
 * Record a movement in a period for a supplier item.
 */
function exportMovement(Period $period, SupplierItem $supplierItem, Profile $profile, string $type, float $quantity, float $totalCost, array $attributes = []): Transaction
{
    return Transaction::factory()->create(array_merge([
        'period_id' => $period->id,
        'supplier_item_id' => $supplierItem->id,
        'type' => $type,
        'quantity' => $quantity,
        'unit_cost' => 100,
        'total_cost' => $totalCost,
        'transaction_date' => sprintf('%04d-%02d-05', $period->year, $period->month),
        'profile_id' => $profile->id,
    ], $attributes));
}

/**
 * Export a period and load the resulting workbook.
 */
function exportWorkbook(Profile $profile, Period $period): Spreadsheet
{
    $response = test()->withSession(['active_profile_id' => $profile->id])
        ->get(route('reports.export', ['period_id' => $period->id]));

    $response->assertOk();

    $path = $response->baseResponse->getFile()->getPathname();

    return IOFactory::load($path);
}

test('a supervisor can export the monthly report to Excel', function () {
    $supervisor = Profile::factory()->supervisor()->create();
    $period = Period::factory()->create(['year' => 2026, 'month' => 7]);

    $response = $this->withSession(['active_profile_id' => $supervisor->id])
        ->get(route('reports.export', ['period_id' => $period->id]));

    $response->assertOk();
    $response->assertHeader(
        'content-type',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    );
    $response->assertDownload('inventory-report-2026-07.xlsx');
});

test('a staff profile cannot export the monthly report', function () {
    $staff = Profile::factory()->create();
    $period = Period::factory()->create(['year' => 2026, 'month' => 7]);

    $this->withSession(['active_profile_id' => $staff->id])
        ->get(route('reports.export', ['period_id' => $period->id]))
        ->assertForbidden();
});

test('the exported sheet has the title band, header and sheet name', function () {
    $supervisor = Profile::factory()->supervisor()->create();
    $period = Period::factory()->create(['year' => 2026, 'month' => 7]);

    $spreadsheet = exportWorkbook($supervisor, $period);
    $sheet = $spreadsheet->getActiveSheet();

    expect($sheet->getTitle())->toBe('JUL');
    expect($sheet->getCell('A1')->getValue())->toBe('Davao Regional Medical center');
    expect($sheet->getCell('A2')->getValue())->toBe('Nutrition and Dietetics Department');
    expect($sheet->getCell('A3')->getValue())
        ->toBe(' ISSUANCE OF DRUGS AND MEDICINES AND SUPPLY CONSIGNMENT FOR THE MONTH OF : JULY 2026');
    expect($sheet->getCell('A4')->getValue())->toBe('ITEM');
    expect($sheet->getCell('A5')->getValue())->toBe('CODE');
    expect($sheet->getCell('G4')->getValue())->toBe('BEGINNING BALANCE JULY 2026');
    expect($sheet->getCell('G5')->getValue())->toBe('QTY.');
    expect($sheet->getCell('H5')->getValue())->toBe('TOTAL COST');
    expect($sheet->getCell('S4')->getValue())->toBe('WRITE-OFF');
    expect($sheet->getCell('U4')->getValue())->toBe('BAL. JULY 2026');
});

test('the exported sheet writes one row per batch with live formulas', function () {
    $supervisor = Profile::factory()->supervisor()->create();
    $period = Period::factory()->create(['year' => 2026, 'month' => 7]);
    $supplierItem = exportSupplierItem();

    $firstBatch = Batch::factory()->create([
        'supplier_item_id' => $supplierItem->id,
        'batch_number' => 'BATCH-A',
        'expiration_date' => '2027-01-01',
    ]);
    $secondBatch = Batch::factory()->create([
        'supplier_item_id' => $supplierItem->id,
        'batch_number' => 'BATCH-B',
        'expiration_date' => '2027-06-01',
    ]);

    exportMovement($period, $supplierItem, $supervisor, 'received', 10, 1000, ['batch_id' => $firstBatch->id]);
    exportMovement($period, $supplierItem, $supervisor, 'consumption', 3, 300, ['batch_id' => $secondBatch->id]);

    $sheet = exportWorkbook($supervisor, $period)->getActiveSheet();

    // The first supplier band sits in the header row (spec 3.3).
    expect($sheet->getCell('B5')->getValue())->toBe($supplierItem->supplier->name);

    // Two batch rows, ordered by expiration date.
    expect($sheet->getCell('D6')->getValue())->toBe('BATCH-A');
    expect($sheet->getCell('D7')->getValue())->toBe('BATCH-B');

    // Quantities are written as values; costs are live formulas.
    expect($sheet->getCell('I6')->getValue())->toBe(10.0);
    expect($sheet->getCell('Q7')->getValue())->toBe(3.0);
    expect($sheet->getCell('H6')->getValue())->toBe('=F6*G6');
    expect($sheet->getCell('P7')->getValue())->toBe('=F7*O7');

    // The ending balance is item-level, so only the first batch row carries it.
    expect($sheet->getCell('U6')->getValue())->toContain('=G6+SUM(I6:I6)');
    expect($sheet->getCell('U7')->getValue())->toBe(0);
});

test('the exported sheet subtotals each supplier and writes a grand total', function () {
    $supervisor = Profile::factory()->supervisor()->create();
    $period = Period::factory()->create(['year' => 2026, 'month' => 7]);

    $supplier = Supplier::factory()->create(['name' => 'Acme Nutrition']);
    $first = exportSupplierItem(['supplier_id' => $supplier->id]);
    $second = exportSupplierItem(['supplier_id' => $supplier->id]);

    exportMovement($period, $first, $supervisor, 'received', 10, 1000);
    exportMovement($period, $second, $supervisor, 'received', 4, 400);

    $sheet = exportWorkbook($supervisor, $period)->getActiveSheet();

    // Rows 6-7 are the two items; row 8 is the supplier subtotal.
    expect($sheet->getCell('B8')->getValue())->toBe('SUB TOTAL');
    expect($sheet->getCell('I8')->getValue())->toBe('=SUM(I6:I7)');

    // Row 9 is the grand total, summing the subtotal rows.
    expect($sheet->getCell('B9')->getValue())->toBe('GRAND TOTAL');
    expect($sheet->getCell('I9')->getValue())->toBe('=SUM(I8)');
});

test('the exported sheet includes the remarks block and prepared-by name', function () {
    $supervisor = Profile::factory()->supervisor()->create(['name' => 'Jane Supervisor']);
    $period = Period::factory()->create([
        'year' => 2026,
        'month' => 7,
        'status' => 'closed',
        'closed_at' => now(),
        'closed_by' => $supervisor->id,
    ]);
    $supplierItem = exportSupplierItem();

    // A closed period lists the items in its frozen snapshot (FR-7.4).
    PeriodBalance::factory()->create([
        'period_id' => $period->id,
        'supplier_item_id' => $supplierItem->id,
        'beginning_quantity' => 0,
        'beginning_cost' => 0,
        'ending_quantity' => 0,
        'ending_cost' => 0,
    ]);

    exportMovement($period, $supplierItem, $supervisor, 'write_off', 2, 200, [
        'remark' => 'Expired stock',
    ]);

    $sheet = exportWorkbook($supervisor, $period)->getActiveSheet();

    // Item row 6, subtotal 7, grand total 8, blank 9, remarks label 10.
    expect($sheet->getCell('B10')->getValue())->toBe('Inventory Remarks:');
    expect($sheet->getCell('B11')->getValue())->toContain('Write-off');
    expect($sheet->getCell('B11')->getValue())->toContain('Expired stock');

    // The signature label carries the closing profile's name (FR-7.3).
    expect($sheet->getCell('L12')->getValue())->toBe('Prepared by: Jane Supervisor');
    expect($sheet->getCell('Q12')->getValue())->toBe('Received by:');
});

test('the exported sheet uses landscape Folio page setup', function () {
    $supervisor = Profile::factory()->supervisor()->create();
    $period = Period::factory()->create(['year' => 2026, 'month' => 7]);

    $sheet = exportWorkbook($supervisor, $period)->getActiveSheet();

    expect($sheet->getPageSetup()->getOrientation())->toBe('landscape');
    expect($sheet->getPageSetup()->getPaperSize())->toBe(14);
    expect($sheet->getPageMargins()->getLeft())->toBe(0.25);
    expect($sheet->getPageMargins()->getRight())->toBe(0.0);
});
