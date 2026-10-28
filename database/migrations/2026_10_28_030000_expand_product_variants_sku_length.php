<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('product_variants')) {
            try {
                DB::statement('ALTER TABLE product_variants MODIFY sku VARCHAR(100) NOT NULL');
            } catch (\Throwable $e) {
                // SQLite or other drivers handle column types dynamically
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Safe no-op to prevent truncation of any existing SKUs
    }
};
