<?php

use App\Models\Item;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;

it('seeds demo data when items and transactions are empty', function () {
    $exitCode = Artisan::call('demo:seed');

    expect($exitCode)->toBe(0);

    expect(Item::count())->toBe(6);
    expect(User::count())->toBe(4);
    expect(Transaction::count())->toBe(500);

    $min = Transaction::min('posted_at');
    $max = Transaction::max('posted_at');

    expect($min)->not->toBeNull();
    expect($max)->not->toBeNull();
    expect($min)->toBeGreaterThanOrEqual(now()->subYear()->startOfDay());
    expect($max)->toBeLessThanOrEqual(now()->endOfDay());
});

it('aborts when items already exist', function () {
    Item::factory()->create();

    $exitCode = Artisan::call('demo:seed');

    expect($exitCode)->toBe(1);
    expect(Transaction::count())->toBe(0);
});

it('aborts when transactions already exist', function () {
    User::factory()->create();
    Transaction::factory()->create();

    $exitCode = Artisan::call('demo:seed');

    expect($exitCode)->toBe(1);
    // The factory created one item; the command must not add any more.

    expect(Item::count())->toBe(1);
});

it('generates deterministic data across runs', function () {
    Artisan::call('demo:seed');
    $first = Transaction::orderBy('id')->pluck('posted_at')->map(fn ($d) => $d->format('Y-m-d H:i:s'))->all();

    // Reset the tables and seed again.
    Transaction::query()->delete();
    Item::query()->delete();
    User::query()->delete();

    Artisan::call('demo:seed');
    $second = Transaction::orderBy('id')->pluck('posted_at')->map(fn ($d) => $d->format('Y-m-d H:i:s'))->all();

    expect($second)->toBe($first);
});
