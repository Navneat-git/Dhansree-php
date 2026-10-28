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
        if (Schema::hasTable('store_settings')) {
            Schema::table('store_settings', function (Blueprint $table) {
                if (!Schema::hasColumn('store_settings', 'google_ads_id')) {
                    $table->string('google_ads_id', 64)->nullable()->default('AW-18539949937');
                }
            });

            try {
                DB::table('store_settings')
                    ->whereNull('google_ads_id')
                    ->orWhere('google_ads_id', '')
                    ->update(['google_ads_id' => 'AW-18539949937']);
            } catch (\Throwable $e) {
                // Table might be empty
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Safe no-op
    }
};
