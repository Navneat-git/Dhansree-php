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
            if (!Schema::hasColumn('store_settings', 'analytics_google_measurement_id')) {
                $table->string('analytics_google_measurement_id', 40)->nullable();
            }
            if (!Schema::hasColumn('store_settings', 'gtm_container_id')) {
                $table->string('gtm_container_id', 64)->nullable();
            }
            if (!Schema::hasColumn('store_settings', 'ads_meta_pixel_id')) {
                $table->string('ads_meta_pixel_id', 64)->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->dropColumn(['gtm_container_id', 'ads_meta_pixel_id']);
        });
    }
};
