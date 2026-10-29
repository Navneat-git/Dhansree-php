<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_videos')) {
            Schema::table('product_videos', function (Blueprint $table) {
                if (!Schema::hasColumn('product_videos', 'completed_at')) {
                    $table->timestamp('completed_at')->nullable()->after('updated_at');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('product_videos')) {
            Schema::table('product_videos', function (Blueprint $table) {
                if (Schema::hasColumn('product_videos', 'completed_at')) {
                    $table->dropColumn('completed_at');
                }
            });
        }
    }
};
