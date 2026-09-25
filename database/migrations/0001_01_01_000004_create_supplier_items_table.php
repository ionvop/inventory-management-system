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
        Schema::create('supplier_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id');
            $table->foreignId('item_id');
            $table->decimal('price', 12, 2);
            $table->date('effective_date');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['supplier_id', 'item_id', 'effective_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_items');
    }
};
