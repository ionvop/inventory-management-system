<?php

use App\Models\Item;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('belongs to an item', function () {
    $item = Item::factory()->create();
    $transaction = Transaction::factory()->create(['item_id' => $item->id]);

    expect($transaction->item->id)->toBe($item->id);
});

it('belongs to a user', function () {
    $user = User::factory()->create();
    $transaction = Transaction::factory()->create(['user_id' => $user->id]);

    expect($transaction->user->id)->toBe($user->id);
});

it('casts posted_at to a Carbon instance', function () {
    $transaction = Transaction::factory()->create(['posted_at' => '2026-08-31 10:00:00']);

    expect($transaction->posted_at)->toBeInstanceOf(\Carbon\Carbon::class);
});