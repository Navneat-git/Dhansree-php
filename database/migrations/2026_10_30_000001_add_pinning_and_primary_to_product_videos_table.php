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
        if (Schema::hasTable('product_videos')) {
            Schema::table('product_videos', function (Blueprint $table) {
                if (!Schema::hasColumn('product_videos', 'is_primary')) {
                    $table->boolean('is_primary')->default(false)->after('file_size_bytes');
                    $table->index('is_primary');
                }
                if (!Schema::hasColumn('product_videos', 'position')) {
                    $table->integer('position')->default(0)->after('is_primary');
                }
                if (!Schema::hasColumn('product_videos', 'pinned_option_value_ids')) {
                    $table->json('pinned_option_value_ids')->nullable()->after('position');
                }
                if (!Schema::hasColumn('product_videos', 'pinned_variant_ids')) {
                    $table->json('pinned_variant_ids')->nullable()->after('pinned_option_value_ids');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('product_videos')) {
            Schema::table('product_videos', function (Blueprint $table) {
                $cols = [];
                if (Schema::hasColumn('product_videos', 'is_primary')) {
                    $cols[] = 'is_primary';
                }
                if (Schema::hasColumn('product_videos', 'position')) {
                    $cols[] = 'position';
                }
                if (Schema::hasColumn('product_videos', 'pinned_option_value_ids')) {
                    $cols[] = 'pinned_option_value_ids';
                }
                if (Schema::hasColumn('product_videos', 'pinned_variant_ids')) {
                    $cols[] = 'pinned_variant_ids';
                }
                if (!empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }
    }
};
