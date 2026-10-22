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
        Schema::table('store_settings', function (Blueprint $table) {
            $table->unsignedBigInteger('home_hero_product_id')->nullable()->after('payment_strip_height');
            $table->string('home_hero_image_url', 500)->nullable()->after('home_hero_product_id');
            $table->string('home_hero_title')->nullable()->after('home_hero_image_url');
            $table->string('home_hero_subtitle')->nullable()->after('home_hero_title');

            $table->unsignedBigInteger('smart_living_promo_product_id')->nullable()->after('home_hero_subtitle');
            $table->string('smart_living_promo_image_url', 500)->nullable()->after('smart_living_promo_product_id');
            $table->string('smart_living_promo_title')->nullable()->after('smart_living_promo_image_url');
            $table->string('smart_living_promo_subtitle')->nullable()->after('smart_living_promo_title');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->dropColumn([
                'home_hero_product_id',
                'home_hero_image_url',
                'home_hero_title',
                'home_hero_subtitle',
                'smart_living_promo_product_id',
                'smart_living_promo_image_url',
                'smart_living_promo_title',
                'smart_living_promo_subtitle',
            ]);
        });
    }
};
