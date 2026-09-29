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
        Schema::table('batches', function (Blueprint $table) {
            // The reason a batch was flagged as damaged (FR-3.2), captured when
            // staff mark it for pull-out. Null for batches that are not damaged.
            $table->string('damaged_reason')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->dropColumn('damaged_reason');
        });
    }
};
