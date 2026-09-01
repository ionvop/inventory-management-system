<?php

use App\Models\Item;
use App\Models\Transaction;
use App\Models\User;

it('returns the inventory report as excel by default', function () {
    actingAsUser();
    $item = Item::factory()->create(['name' => 'Sugar']);
    Transaction::factory()->create(['item_id' => $item->id, 'movement' => 'in', 'quantity' => 5]);

    $this->get('/api/reports/inventory')
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

it('returns the inventory report as excel', function () {
    actingAsUser();
    $item = Item::factory()->create(['name' => 'Sugar', 'unit' => 'kg']);
    Transaction::factory()->create(['item_id' => $item->id, 'movement' => 'in', 'quantity' => 5]);

    $response = $this->get('/api/reports/inventory?format=excel');

    $response->assertOk();
    $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    // An .xlsx is a ZIP archive; verify it is a valid zip and contains the sheet XML.
    $content = $response->streamedContent();
    $zip = new ZipArchive();
    $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
    file_put_contents($tmp, $content);
    $opened = $zip->open($tmp);
    expect($opened)->toBeTrue();
    expect($zip->getFromName('xl/sharedStrings.xml'))->toContain('Sugar');
    $zip->close();
    @unlink($tmp);
});

it('returns the inventory report as csv', function () {
    actingAsUser();
    $item = Item::factory()->create(['name' => 'Sugar', 'unit' => 'kg']);
    Transaction::factory()->create(['item_id' => $item->id, 'movement' => 'in', 'quantity' => 5]);

    $response = $this->get('/api/reports/inventory?format=csv');

    $response->assertOk();
    $content = $response->streamedContent();

    // fputcsv quotes fields depending on locale, so parse the CSV rather than
    // matching the exact rendered string.
    $rows = array_map('str_getcsv', array_filter(explode("\n", trim($content))));

    expect($rows[0])->toBe(['Item', 'Unit', 'Current Stock', 'Minimum Stock', 'Transaction Date', 'Movement', 'Quantity', 'User']);
    expect($rows[1][0])->toBe('Sugar')
        ->and($rows[1][1])->toBe('kg')
        ->and($rows[1][6])->toBe('5');
});

it('returns the inventory report as pdf', function () {
    actingAsUser();
    Item::factory()->create(['name' => 'Sugar']);

    $this->get('/api/reports/inventory?format=pdf')
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('filters the report by date range', function () {
    actingAsUser();
    $item = Item::factory()->create(['name' => 'Sugar']);
    Transaction::factory()->create(['item_id' => $item->id, 'movement' => 'in', 'quantity' => 5, 'posted_at' => now()->subDays(10)]);
    Transaction::factory()->create(['item_id' => $item->id, 'movement' => 'in', 'quantity' => 3, 'posted_at' => now()]);

    $response = $this->get('/api/reports/inventory?format=csv&date_from='.now()->subDays(2)->format('Y-m-d'));

    $response->assertOk();
    $rows = array_map('str_getcsv', array_filter(explode("\n", trim($response->streamedContent()))));

    // Header + 1 filtered transaction row.
    expect($rows)->toHaveCount(2);
    expect($rows[1][6])->toBe('3');
});