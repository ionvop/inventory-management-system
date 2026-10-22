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
        Schema::create('bids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id');
            $table->foreignId('item_id');
            $table->decimal('bid_quantity', 12, 2);
            $table->date('set_date');
            $table->foreignId('profile_id')->nullable();
            $table->timestamps();

            // Bids are keyed by the supplier/item pair rather than a single
            // supplier_items row, because a price change creates a new priced
            // record (FR-2.3) and the bid must outlive it. There is no unique
            // constraint: a pair is bid on repeatedly (e.g. every six months),
            // and each bid is preserved as history.
            $table->index(['supplier_id', 'item_id', 'set_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bids');
    }
};
