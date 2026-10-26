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
            if (!Schema::hasColumn('store_settings', 'facebook_domain_verification')) {
                $table->string('facebook_domain_verification', 100)->nullable()->default('jaur6cwxsewazavl4qh5goy7ylgewz');
            }
        });

        // Set default value for existing record if null
        try {
            DB::table('store_settings')
                ->whereNull('facebook_domain_verification')
                ->orWhere('facebook_domain_verification', '')
                ->update(['facebook_domain_verification' => 'jaur6cwxsewazavl4qh5goy7ylgewz']);
        } catch (\Throwable $e) {
            // Ignore if table has no rows
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            if (Schema::hasColumn('store_settings', 'facebook_domain_verification')) {
                $table->dropColumn('facebook_domain_verification');
            }
        });
    }
};
