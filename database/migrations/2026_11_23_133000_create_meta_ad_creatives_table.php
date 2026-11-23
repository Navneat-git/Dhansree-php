<?php

declare(strict_types=1);

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
        Schema::create('meta_ad_creatives', function (Blueprint $table) {
            $table->id();
            $table->integer('product_id')->nullable();
            $table->string('campaign_name', 191)->nullable();
            $table->string('ad_name', 191)->nullable();
            $table->string('angle', 50)->default('festive'); // festive, comfort, offer, viral, fit
            $table->text('primary_text');
            $table->string('headline', 255);
            $table->string('description', 255)->nullable();
            $table->string('cta', 50)->default('Shop Now');
            $table->text('destination_url');
            $table->string('utm_source', 50)->default('meta');
            $table->string('utm_medium', 50)->default('paid_social');
            $table->string('utm_campaign', 100)->nullable();
            $table->string('utm_content', 100)->nullable();
            $table->text('creative_image_url')->nullable();
            $table->string('ai_provider', 50)->default('smart_ai'); // smart_ai, gemini, chatgpt
            $table->timestamps();

            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');

            $table->index(['product_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meta_ad_creatives');
    }
};
