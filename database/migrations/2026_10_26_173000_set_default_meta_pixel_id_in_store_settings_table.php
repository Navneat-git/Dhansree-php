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
        Schema::table('store_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('store_settings', 'ads_meta_pixel_id')) {
                $table->string('ads_meta_pixel_id', 64)->nullable()->default('1783488586189258');
            }
        });

        // Set default value for existing record if null
        try {
            DB::table('store_settings')
                ->whereNull('ads_meta_pixel_id')
                ->orWhere('ads_meta_pixel_id', '')
                ->update(['ads_meta_pixel_id' => '1783488586189258']);
        } catch (\Throwable $e) {
            // Ignore if table has no rows
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op to preserve customer settings
    }
};
