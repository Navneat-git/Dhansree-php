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
            if (!Schema::hasColumn('store_settings', 'seo_google_verification')) {
                $table->string('seo_google_verification', 100)->nullable();
            }
            if (!Schema::hasColumn('store_settings', 'analytics_google_measurement_id')) {
                $table->string('analytics_google_measurement_id', 40)->nullable();
            }
            if (!Schema::hasColumn('store_settings', 'gtm_container_id')) {
                $table->string('gtm_container_id', 64)->nullable();
            }
            if (!Schema::hasColumn('store_settings', 'ads_meta_pixel_id')) {
                $table->string('ads_meta_pixel_id', 64)->nullable();
            }
            if (!Schema::hasColumn('store_settings', 'featured_coupon_code')) {
                $table->string('featured_coupon_code', 64)->nullable()->default('FESTIVE10');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            // Safe rollback if needed
            $columnsToDrop = [];
            foreach (['gtm_container_id', 'ads_meta_pixel_id', 'analytics_google_measurement_id', 'seo_google_verification'] as $col) {
                if (Schema::hasColumn('store_settings', $col)) {
                    $columnsToDrop[] = $col;
                }
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
