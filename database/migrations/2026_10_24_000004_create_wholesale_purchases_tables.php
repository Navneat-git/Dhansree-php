<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Color Master for Women Fashion & Variations
        Schema::create('wholesale_color_codes', function (Blueprint $table) {
            $table->id();
            $table->string('color_name', 100)->unique();
            $table->string('color_code', 20);
            $table->string('hex_code', 30)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Seed default 18 colors requested by user
        $defaultColors = [
            ['color_name' => 'Black', 'color_code' => 'BLK', 'hex_code' => '#000000', 'sort_order' => 1],
            ['color_name' => 'White', 'color_code' => 'WHT', 'hex_code' => '#FFFFFF', 'sort_order' => 2],
            ['color_name' => 'Red', 'color_code' => 'RED', 'hex_code' => '#EF4444', 'sort_order' => 3],
            ['color_name' => 'Blue', 'color_code' => 'BLU', 'hex_code' => '#3B82F6', 'sort_order' => 4],
            ['color_name' => 'Green', 'color_code' => 'GRN', 'hex_code' => '#22C55E', 'sort_order' => 5],
            ['color_name' => 'Yellow', 'color_code' => 'YLW', 'hex_code' => '#EAB308', 'sort_order' => 6],
            ['color_name' => 'Pink', 'color_code' => 'PNK', 'hex_code' => '#EC4899', 'sort_order' => 7],
            ['color_name' => 'Maroon', 'color_code' => 'MRN', 'hex_code' => '#800000', 'sort_order' => 8],
            ['color_name' => 'Purple', 'color_code' => 'PPL', 'hex_code' => '#A855F7', 'sort_order' => 9],
            ['color_name' => 'Peach', 'color_code' => 'PCH', 'hex_code' => '#FFCBA4', 'sort_order' => 10],
            ['color_name' => 'Orange', 'color_code' => 'ORG', 'hex_code' => '#F97316', 'sort_order' => 11],
            ['color_name' => 'Beige', 'color_code' => 'BEG', 'hex_code' => '#F5F5DC', 'sort_order' => 12],
            ['color_name' => 'Mus Yellow', 'color_code' => 'YLW', 'hex_code' => '#FFDB58', 'sort_order' => 13],
            ['color_name' => 'Off White', 'color_code' => 'OFWHT', 'hex_code' => '#FAF9F6', 'sort_order' => 14],
            ['color_name' => 'Grey', 'color_code' => 'GRE', 'hex_code' => '#6B7280', 'sort_order' => 15],
            ['color_name' => 'Brown', 'color_code' => 'BRW', 'hex_code' => '#8B4513', 'sort_order' => 16],
            ['color_name' => 'Parrot Green', 'color_code' => 'PRGRN', 'hex_code' => '#7CFC00', 'sort_order' => 17],
            ['color_name' => 'Magenta', 'color_code' => 'MGT', 'hex_code' => '#FF00FF', 'sort_order' => 18],
        ];

        $now = now();
        foreach ($defaultColors as &$item) {
            $item['created_at'] = $now;
            $item['updated_at'] = $now;
        }
        DB::table('wholesale_color_codes')->insert($defaultColors);

        // 2. Wholesale Purchases Table
        Schema::create('wholesale_purchases', function (Blueprint $table) {
            $table->id();
            $table->string('purchase_type', 40)->default('women_fashion')->index(); // 'women_fashion' or 'smart_living'
            $table->string('sku', 120)->index();
            $table->string('product_name', 255);
            $table->string('image_path', 255)->nullable();
            $table->text('image_url')->nullable();
            $table->string('status', 50)->default('Available')->index();
            $table->date('purchase_date')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable();

            // Women Fashion specific fields
            $table->string('brand_code', 50)->default('DS')->nullable();
            $table->string('design_number', 50)->nullable();
            $table->string('category_name', 100)->nullable();
            $table->string('vcode', 50)->nullable();
            $table->string('category_code', 20)->nullable();
            $table->string('pcode', 80)->nullable();
            $table->string('color', 100)->nullable();
            $table->string('color_code', 20)->nullable();
            $table->string('size', 50)->nullable();
            $table->string('thread_count', 100)->nullable();
            $table->decimal('cost', 12, 2)->nullable();
            $table->decimal('gst_percent', 5, 2)->default(5.00)->nullable();
            $table->decimal('gst_amount', 12, 2)->nullable();
            $table->decimal('total_cost', 12, 2)->nullable();
            $table->decimal('markup', 8, 3)->nullable();
            $table->decimal('selling_price', 12, 2)->nullable();

            // Smart Living & Gadgets specific fields
            $table->text('description')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->decimal('cost_price', 12, 2)->nullable();
            $table->decimal('wholesale_price', 12, 2)->nullable();
            $table->decimal('retail_markup', 8, 3)->nullable();
            $table->decimal('retail_price', 12, 2)->nullable();
            $table->integer('stock_qty')->default(0)->nullable();
            $table->string('moq', 50)->nullable();
            $table->string('brand', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->string('badge', 50)->nullable();
            $table->string('supplier', 150)->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wholesale_purchases');
        Schema::dropIfExists('wholesale_color_codes');
    }
};
