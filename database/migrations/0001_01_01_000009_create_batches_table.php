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
        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_item_id');
            $table->string('batch_number');
            $table->date('expiration_date');
            $table->enum('status', ['active', 'near_expiry', 'expired', 'damaged'])->default('active');
            $table->timestamps();

            $table->index(['supplier_item_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batches');
    }
};
