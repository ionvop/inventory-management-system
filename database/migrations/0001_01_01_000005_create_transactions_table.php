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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('period_id');
            $table->foreignId('supplier_item_id');
            $table->foreignId('batch_id')->nullable();
            $table->enum('type', [
                'received',
                'consumption',
                'return_from_ward',
                'return_to_supplier',
                'transfer_to_pharmacy',
                'write_off',
            ]);
            $table->decimal('quantity', 12, 2);
            $table->decimal('unit_cost', 12, 2);
            $table->decimal('total_cost', 12, 2);
            $table->date('transaction_date');
            $table->foreignId('profile_id');
            $table->foreignId('ward_id')->nullable();
            $table->string('remark')->nullable();
            $table->string('override_reason')->nullable();
            $table->foreignId('reverses_transaction_id')->nullable();
            $table->timestamps();

            $table->index(['supplier_item_id', 'period_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
