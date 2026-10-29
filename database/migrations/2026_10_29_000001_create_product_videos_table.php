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
        Schema::create('product_videos', function (Blueprint $table) {
            $table->id();
            $table->integer('product_id')->nullable();
            $table->string('title', 255);
            $table->string('video_type', 50)->default('slideshow'); // 'slideshow', 'ai_motion'
            $table->string('template', 50)->default('fashion_showcase'); // 'fashion_showcase', 'new_arrivals', 'festive_collection', 'sale_offers', 'clean_showcase'
            $table->string('aspect_ratio', 20)->default('9:16'); // '9:16', '1:1', '4:5'
            $table->unsignedInteger('width')->default(1080);
            $table->unsignedInteger('height')->default(1920);
            $table->unsignedInteger('duration_seconds')->default(10);
            $table->string('status', 30)->default('queued'); // 'queued', 'processing', 'completed', 'failed'
            $table->unsignedTinyInteger('progress')->default(0);
            $table->json('selected_image_ids')->nullable();
            $table->json('render_settings')->nullable();
            $table->string('video_path', 500)->nullable();
            $table->string('thumbnail_path', 500)->nullable();
            $table->unsignedBigInteger('file_size_bytes')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('created_by')->nullable();
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('admin_users')->nullOnDelete();

            $table->index(['product_id', 'status']);
            $table->index('status');
            $table->index('video_type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_videos');
    }
};
