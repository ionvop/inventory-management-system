<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('period_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('period_id');
            $table->foreignId('supplier_item_id');
            $table->decimal('beginning_quantity', 12, 2)->default(0);
            $table->decimal('beginning_cost', 12, 2)->default(0);
            $table->decimal('ending_quantity', 12, 2)->default(0);
            $table->decimal('ending_cost', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['period_id', 'supplier_item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('period_balances');
    }
};
