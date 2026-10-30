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
        Schema::table('product_images', function (Blueprint $table) {
            if (!Schema::hasColumn('product_images', 'is_actual_product_image')) {
                $table->boolean('is_actual_product_image')->default(false)->after('alt_text')->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            if (Schema::hasColumn('product_images', 'is_actual_product_image')) {
                $table->dropIndex(['is_actual_product_image']);
                $table->dropColumn('is_actual_product_image');
            }
        });
    }
};
