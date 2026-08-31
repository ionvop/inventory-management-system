<?php

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('has many transactions', function () {
    $user = User::factory()->create();
    Transaction::factory()->count(2)->create(['user_id' => $user->id]);

    expect($user->transactions)->toHaveCount(2);
});