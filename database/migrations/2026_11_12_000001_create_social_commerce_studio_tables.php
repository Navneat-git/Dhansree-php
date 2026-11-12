<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for Dhansree Social Commerce Studio.
     */
    public function up(): void
    {
        // 1. Social Suppliers
        if (!Schema::hasTable('social_suppliers')) {
            Schema::create('social_suppliers', function (Blueprint $table) {
                $table->id();
                $table->string('name', 150)->index();
                $table->string('contact_person', 150)->nullable();
                $table->string('phone', 50)->nullable();
                $table->string('whatsapp_group_name', 150)->nullable()->index();
                $table->string('city', 100)->nullable();
                $table->text('notes')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 2. Social Media Assets (Supplier Media Inbox)
        if (!Schema::hasTable('social_media_assets')) {
            Schema::create('social_media_assets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('supplier_id')->nullable()->constrained('social_suppliers')->nullOnDelete();
                $table->string('supplier_group_name', 150)->nullable()->index();
                $table->string('file_path');
                $table->string('file_name');
                $table->unsignedBigInteger('file_size_bytes')->default(0);
                $table->string('mime_type', 100)->default('image/jpeg');
                $table->enum('media_type', ['IMAGE', 'VIDEO'])->default('IMAGE')->index();
                $table->unsignedInteger('width')->nullable();
                $table->unsignedInteger('height')->nullable();
                $table->decimal('duration_seconds', 8, 2)->nullable();
                $table->string('file_hash', 64)->index(); // SHA-256
                $table->string('perceptual_hash', 64)->nullable()->index(); // dHash 64-bit hex
                $table->enum('status', ['UNPROCESSED', 'PROCESSED', 'IN_PRODUCT', 'ARCHIVED'])->default('UNPROCESSED')->index();
                $table->string('category_name', 100)->nullable()->index();
                $table->json('tags')->nullable();
                $table->json('metadata')->nullable();
                $table->integer('created_by_admin_user_id')->nullable();
                $table->foreign('created_by_admin_user_id')->references('id')->on('admin_users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 3. Social Products (Independent Supplier Product Library)
        if (!Schema::hasTable('social_products')) {
            Schema::create('social_products', function (Blueprint $table) {
                $table->id();
                $table->string('product_code', 50)->unique(); // e.g. DS-SP-0001
                $table->string('name', 200)->index();
                $table->string('category_name', 100)->index();
                $table->foreignId('supplier_id')->nullable()->constrained('social_suppliers')->nullOnDelete();
                $table->string('supplier_product_code', 100)->nullable()->index();
                $table->string('fabric', 150)->nullable();
                $table->json('available_colors')->nullable();
                $table->json('available_sizes')->nullable();
                $table->unsignedBigInteger('purchase_price_paise')->nullable();
                $table->unsignedBigInteger('suggested_selling_price_paise')->nullable();
                $table->unsignedBigInteger('special_offer_price_paise')->nullable();
                $table->text('description')->nullable();
                $table->text('notes')->nullable();
                $table->enum('availability_status', [
                    'NEW',
                    'AVAILABLE',
                    'TEMPORARILY_UNAVAILABLE',
                    'OUT_OF_STOCK',
                    'ARCHIVED'
                ])->default('NEW')->index();
                $table->dateTime('last_availability_confirmed_at')->nullable();
                $table->foreignId('primary_media_asset_id')->nullable()->constrained('social_media_assets')->nullOnDelete();
                $table->boolean('is_promoted_to_web')->default(false)->index();
                $table->integer('web_product_id')->nullable();
                $table->foreign('web_product_id')->references('id')->on('products')->nullOnDelete();
                $table->integer('created_by_admin_user_id')->nullable();
                $table->foreign('created_by_admin_user_id')->references('id')->on('admin_users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 4. Social Product Media Pivot
        if (!Schema::hasTable('social_product_media')) {
            Schema::create('social_product_media', function (Blueprint $table) {
                $table->id();
                $table->foreignId('social_product_id')->constrained('social_products')->cascadeOnDelete();
                $table->foreignId('media_asset_id')->constrained('social_media_assets')->cascadeOnDelete();
                $table->unsignedInteger('position')->default(0);
                $table->boolean('is_primary')->default(false);
                $table->string('color_variant', 100)->nullable();
                $table->timestamps();

                $table->index(['social_product_id', 'position']);
            });
        }

        // 5. Social Product Variants (Designs & Colors)
        if (!Schema::hasTable('social_product_variants')) {
            Schema::create('social_product_variants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('social_product_id')->constrained('social_products')->cascadeOnDelete();
                $table->string('variant_code', 100)->nullable();
                $table->string('color', 100)->nullable();
                $table->string('size', 50)->nullable();
                $table->string('design_name', 150)->nullable();
                $table->unsignedBigInteger('purchase_price_paise')->nullable();
                $table->unsignedBigInteger('selling_price_paise')->nullable();
                $table->enum('stock_status', ['AVAILABLE', 'OUT_OF_STOCK'])->default('AVAILABLE')->index();
                $table->foreignId('media_asset_id')->nullable()->constrained('social_media_assets')->nullOnDelete();
                $table->timestamps();
            });
        }

        // 6. Social Reel Templates
        if (!Schema::hasTable('social_reel_templates')) {
            Schema::create('social_reel_templates', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('name', 150);
                $table->string('tagline', 200)->nullable();
                $table->text('description')->nullable();
                $table->string('default_transition', 50)->default('fade');
                $table->unsignedInteger('default_duration_seconds')->default(15);
                $table->json('config')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });

            // Seed the 7 core templates requested
            DB::table('social_reel_templates')->insert([
                [
                    'code' => 'NEW_ARRIVALS',
                    'name' => 'New Arrivals',
                    'tagline' => 'Fresh designs just arrived from artisans',
                    'description' => 'Fast-paced, vibrant intro featuring new women suit and kurti designs.',
                    'default_transition' => 'wipeleft',
                    'default_duration_seconds' => 15,
                    'config' => json_encode(['badge' => 'NEW ARRIVAL', 'color' => '#580000']),
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'TRENDING_DESIGNS',
                    'name' => 'Trending Designs',
                    'tagline' => 'Most loved & viral suits of the week',
                    'description' => 'Elegant showcase with subtle zoom effects highlighting intricate embroidery.',
                    'default_transition' => 'fade',
                    'default_duration_seconds' => 15,
                    'config' => json_encode(['badge' => 'TRENDING NOW', 'color' => '#B08858']),
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'FESTIVE_COLLECTION',
                    'name' => 'Festive Collection',
                    'tagline' => 'Royal festive suits with gold accents',
                    'description' => 'Rich royal aesthetic with gold borders, maroon overlays, and festive typography.',
                    'default_transition' => 'circleopen',
                    'default_duration_seconds' => 18,
                    'config' => json_encode(['badge' => 'FESTIVE EDIT', 'color' => '#580000']),
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'SPECIAL_PRICE_OFFERS',
                    'name' => 'Special Price Offers',
                    'tagline' => 'High-conversion price drop promo',
                    'description' => 'Bold price tags, special discount badges, and instant WhatsApp order CTA.',
                    'default_transition' => 'slideright',
                    'default_duration_seconds' => 12,
                    'config' => json_encode(['badge' => 'SPECIAL OFFER', 'color' => '#B08858']),
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'LIMITED_STOCK_COLLECTION',
                    'name' => 'Limited Stock Collection',
                    'tagline' => 'Urgency & scarcity driver',
                    'description' => 'Creates buyer urgency with limited pieces tags and fast direct order options.',
                    'default_transition' => 'fade',
                    'default_duration_seconds' => 12,
                    'config' => json_encode(['badge' => 'LIMITED PIECES', 'color' => '#7F1D1D']),
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'DAILY_FASHION_COLLECTION',
                    'name' => 'Daily Fashion Collection',
                    'tagline' => 'Everyday comfort kurtis & cotton co-ords',
                    'description' => 'Clean, light, airy aesthetic highlighting fabric breathability and daily elegance.',
                    'default_transition' => 'wipeup',
                    'default_duration_seconds' => 15,
                    'config' => json_encode(['badge' => 'DAILY WEAR', 'color' => '#580000']),
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'MULTIPLE_DESIGNS_SHOWCASE',
                    'name' => 'Multiple Designs Showcase',
                    'tagline' => 'Catalog carousel reel with 5-10 suits',
                    'description' => 'Rapid catalog showcase demonstrating variety in colours and patterns.',
                    'default_transition' => 'slideleft',
                    'default_duration_seconds' => 20,
                    'config' => json_encode(['badge' => 'CATALOG SHOWCASE', 'color' => '#B08858']),
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }

        // 7. Social Reels
        if (!Schema::hasTable('social_reels')) {
            Schema::create('social_reels', function (Blueprint $table) {
                $table->id();
                $table->string('reel_code', 50)->unique();
                $table->string('title', 200);
                $table->string('template_code', 50)->default('NEW_ARRIVALS');
                $table->string('aspect_ratio', 10)->default('9:16');
                $table->unsignedInteger('width')->default(1080);
                $table->unsignedInteger('height')->default(1920);
                $table->unsignedInteger('duration_seconds')->default(15);
                $table->enum('status', [
                    'DRAFT',
                    'AWAITING_APPROVAL',
                    'APPROVED',
                    'RENDERING',
                    'COMPLETED',
                    'FAILED'
                ])->default('DRAFT')->index();
                $table->unsignedTinyInteger('progress')->default(0);
                $table->string('video_path')->nullable();
                $table->string('thumbnail_path')->nullable();
                $table->text('caption')->nullable();
                $table->text('hashtags')->nullable();
                $table->string('audio_track', 100)->nullable();
                $table->json('render_settings')->nullable();
                $table->text('error_message')->nullable();
                $table->integer('approved_by_admin_user_id')->nullable();
                $table->foreign('approved_by_admin_user_id')->references('id')->on('admin_users')->nullOnDelete();
                $table->integer('created_by_admin_user_id')->nullable();
                $table->foreign('created_by_admin_user_id')->references('id')->on('admin_users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 8. Social Reel Items
        if (!Schema::hasTable('social_reel_items')) {
            Schema::create('social_reel_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('social_reel_id')->constrained('social_reels')->cascadeOnDelete();
                $table->foreignId('social_product_id')->nullable()->constrained('social_products')->nullOnDelete();
                $table->foreignId('media_asset_id')->constrained('social_media_assets')->cascadeOnDelete();
                $table->unsignedInteger('position')->default(0);
                $table->string('headline', 150)->nullable();
                $table->string('price_text', 50)->nullable();
                $table->decimal('display_seconds', 4, 2)->default(2.50);
                $table->timestamps();

                $table->index(['social_reel_id', 'position']);
            });
        }

        // 9. Social Publishing Accounts (Meta OAuth Connections)
        if (!Schema::hasTable('social_publishing_accounts')) {
            Schema::create('social_publishing_accounts', function (Blueprint $table) {
                $table->id();
                $table->enum('platform', ['INSTAGRAM', 'FACEBOOK'])->index();
                $table->string('account_id', 100)->nullable();
                $table->string('account_name', 150);
                $table->string('username', 100)->nullable();
                $table->string('profile_picture_url')->nullable();
                $table->text('access_token')->nullable(); // Encrypted via Eloquent cast
                $table->dateTime('token_expires_at')->nullable();
                $table->boolean('is_connected')->default(false)->index();
                $table->json('settings')->nullable();
                $table->timestamps();
            });
        }

        // 10. Social Publishing Queue
        if (!Schema::hasTable('social_publishing_queue')) {
            Schema::create('social_publishing_queue', function (Blueprint $table) {
                $table->id();
                $table->foreignId('social_reel_id')->nullable()->constrained('social_reels')->nullOnDelete();
                $table->foreignId('media_asset_id')->nullable()->constrained('social_media_assets')->nullOnDelete();
                $table->string('title', 200);
                $table->enum('content_type', ['REEL', 'STORY', 'POST'])->default('REEL');
                $table->text('caption_instagram')->nullable();
                $table->text('caption_facebook')->nullable();
                $table->json('platforms'); // ['INSTAGRAM', 'FACEBOOK']
                $table->enum('status', [
                    'DRAFT',
                    'AWAITING_APPROVAL',
                    'APPROVED',
                    'SCHEDULED',
                    'PROCESSING',
                    'PUBLISHED',
                    'PARTIALLY_PUBLISHED',
                    'FAILED'
                ])->default('DRAFT')->index();
                $table->dateTime('scheduled_at')->nullable()->index();
                $table->dateTime('published_at')->nullable();
                $table->text('error_message')->nullable();
                $table->integer('created_by_admin_user_id')->nullable();
                $table->foreign('created_by_admin_user_id')->references('id')->on('admin_users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // 11. Social Publishing Logs (Individual platform publish outcomes)
        if (!Schema::hasTable('social_publishing_logs')) {
            Schema::create('social_publishing_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('queue_item_id')->constrained('social_publishing_queue')->cascadeOnDelete();
                $table->enum('platform', ['INSTAGRAM', 'FACEBOOK'])->index();
                $table->enum('status', ['PENDING', 'SUCCESS', 'FAILED'])->default('PENDING');
                $table->string('post_id', 150)->nullable();
                $table->string('post_url', 500)->nullable();
                $table->json('response_payload')->nullable();
                $table->text('error_message')->nullable();
                $table->dateTime('published_at')->nullable();
                $table->timestamps();
            });
        }

        // 12. Social Content Captions
        if (!Schema::hasTable('social_content_captions')) {
            Schema::create('social_content_captions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('social_product_id')->nullable()->constrained('social_products')->nullOnDelete();
                $table->string('tone', 50)->default('festive');
                $table->enum('platform', ['ALL', 'INSTAGRAM', 'FACEBOOK', 'WHATSAPP'])->default('ALL');
                $table->text('caption_text');
                $table->text('hashtags')->nullable();
                $table->timestamps();
            });
        }

        // 13. Social Settings
        if (!Schema::hasTable('social_settings')) {
            Schema::create('social_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key', 100)->unique();
                $table->longText('value')->nullable();
                $table->timestamps();
            });

            // Seed default settings
            $defaults = [
                'brand_name' => 'Dhansree',
                'tagline' => 'STYLE. TECH. EVERYDAY.',
                'primary_color' => '#580000',
                'gold_color' => '#B08858',
                'cream_color' => '#FBFAF8',
                'whatsapp_number' => '+919887776777',
                'whatsapp_order_cta' => 'DM or WhatsApp us to order',
                'default_hashtags' => '#Dhansree #WomenFashion #LatestSuits #EthnicWear #FashionCollection #IndianSuits #HandcraftedFashion',
                'meta_app_id' => '',
                'meta_app_secret' => '',
                'meta_api_version' => 'v20.0',
            ];

            foreach ($defaults as $k => $v) {
                DB::table('social_settings')->insert([
                    'key' => $k,
                    'value' => $v,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_settings');
        Schema::dropIfExists('social_content_captions');
        Schema::dropIfExists('social_publishing_logs');
        Schema::dropIfExists('social_publishing_queue');
        Schema::dropIfExists('social_publishing_accounts');
        Schema::dropIfExists('social_reel_items');
        Schema::dropIfExists('social_reels');
        Schema::dropIfExists('social_reel_templates');
        Schema::dropIfExists('social_product_variants');
        Schema::dropIfExists('social_product_media');
        Schema::dropIfExists('social_products');
        Schema::dropIfExists('social_media_assets');
        Schema::dropIfExists('social_suppliers');
    }
};
