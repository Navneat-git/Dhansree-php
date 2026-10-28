<?php

use App\Enums\HomeBlockType;
use App\Models\HomeBlock;
use App\Models\InstagramPost;
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
        // 1. Expand ENUM column 'type' in home_blocks if table exists
        if (Schema::hasTable('home_blocks')) {
            try {
                DB::statement("ALTER TABLE home_blocks MODIFY COLUMN type ENUM('PROMO_CAROUSEL','HERO','TILE_GRID','PRODUCT_RAIL','PROMO_BANNER','FEATURES','INSTAGRAM') NOT NULL");
            } catch (\Throwable $e) {
                // Ignore if already modified or alternative driver
            }

            // 2. Insert Instagram block if it does not exist yet
            $exists = HomeBlock::where('type', HomeBlockType::INSTAGRAM)->exists();
            if (!$exists) {
                $maxPos = (int) (HomeBlock::max('position') ?? 6);
                HomeBlock::create([
                    'type' => HomeBlockType::INSTAGRAM,
                    'position' => $maxPos + 1,
                    'is_visible' => true,
                    'heading' => 'Shop Our Instagram Reels & Styles',
                    'eyebrow' => 'As Seen On Instagram • @dhansreetrends',
                    'body' => 'Tap any reel to explore trending Jaipur fits, festive styling & shop the exact looks.',
                    'primary_label' => 'View All Reels',
                    'primary_path' => '/instragram',
                ]);
            }
        }

        // 3. Auto-seed Instagram posts if table exists and is empty
        if (Schema::hasTable('instagram_posts')) {
            if (InstagramPost::count() === 0) {
                \Illuminate\Support\Facades\Artisan::call('db:seed', [
                    '--class' => 'Database\\Seeders\\InstagramFeedSeeder',
                    '--force' => true,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('home_blocks')) {
            HomeBlock::where('type', HomeBlockType::INSTAGRAM)->delete();
            try {
                DB::statement("ALTER TABLE home_blocks MODIFY COLUMN type ENUM('PROMO_CAROUSEL','HERO','TILE_GRID','PRODUCT_RAIL','PROMO_BANNER','FEATURES') NOT NULL");
            } catch (\Throwable $e) {
            }
        }
    }
};
