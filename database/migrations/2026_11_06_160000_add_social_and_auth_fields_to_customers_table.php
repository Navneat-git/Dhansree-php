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
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'password')) {
                $table->string('password')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('customers', 'google_id')) {
                $table->string('google_id')->nullable()->after('password');
            }
            if (!Schema::hasColumn('customers', 'facebook_id')) {
                $table->string('facebook_id')->nullable()->after('google_id');
            }
            if (!Schema::hasColumn('customers', 'avatar')) {
                $table->string('avatar')->nullable()->after('facebook_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $dropCols = [];
            foreach (['password', 'google_id', 'facebook_id', 'avatar'] as $col) {
                if (Schema::hasColumn('customers', $col)) {
                    $dropCols[] = $col;
                }
            }
            if (!empty($dropCols)) {
                $table->dropColumn($dropCols);
            }
        });
    }
};
