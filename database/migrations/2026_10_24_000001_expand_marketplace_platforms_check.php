<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `marketplace_templates` DROP CHECK `marketplace_templates_platform_check`");
            DB::statement("ALTER TABLE `marketplace_templates` ADD CONSTRAINT `marketplace_templates_platform_check` CHECK (`platform` IN ('MEESHO', 'FLIPKART', 'AMAZON', 'MYNTRA', 'AJIO'))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `marketplace_templates` DROP CHECK `marketplace_templates_platform_check`");
            DB::statement("ALTER TABLE `marketplace_templates` ADD CONSTRAINT `marketplace_templates_platform_check` CHECK (`platform` IN ('MEESHO', 'FLIPKART'))");
        }
    }
};
