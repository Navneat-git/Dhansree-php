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
        if (!Schema::hasTable('instagram_posts')) {
            Schema::create('instagram_posts', function (Blueprint $table) {
                $table->id();
                $table->string('type', 20)->default('reel'); // 'reel' or 'post'
                $table->text('caption')->nullable();
                $table->string('media_url', 500);
                $table->string('video_url', 500)->nullable();
                $table->string('instagram_url', 500)->nullable();
                $table->unsignedInteger('likes_count')->default(1250);
                $table->string('views_count', 30)->nullable()->default('18.4K');
                $table->unsignedInteger('comments_count')->default(36);
                $table->integer('tagged_product_id')->nullable();
                $table->integer('position')->default(0);
                $table->boolean('is_visible')->default(true);
                $table->timestamps();

                $table->foreign('tagged_product_id')
                    ->references('id')
                    ->on('products')
                    ->nullOnDelete();

                $table->index(['is_visible', 'position']);
                $table->index('type');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instagram_posts');
    }
};
