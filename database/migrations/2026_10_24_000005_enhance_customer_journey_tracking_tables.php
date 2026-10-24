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
        if (!Schema::hasTable('tracked_visitors')) {
            Schema::create('tracked_visitors', function (Blueprint $table) {
                $table->id();
                $table->string('customer_code', 30)->unique();
                $table->unsignedBigInteger('customer_id')->nullable()->index();
                $table->string('email', 150)->nullable()->index();
                $table->string('ip_address', 45)->nullable()->index();
                $table->string('visitor_uuid', 36)->nullable()->index();
                $table->string('city', 100)->nullable();
                $table->string('region', 100)->nullable();
                $table->string('country', 100)->nullable();
                $table->string('country_code', 10)->nullable();
                $table->unsignedInteger('total_visits')->default(1);
                $table->dateTime('first_seen_at')->index();
                $table->dateTime('last_seen_at')->index();
                $table->timestamps();

                $table->index(['ip_address', 'email']);
            });
        }

        if (Schema::hasTable('visitor_sessions')) {
            Schema::table('visitor_sessions', function (Blueprint $table) {
                if (!Schema::hasColumn('visitor_sessions', 'customer_code')) {
                    $table->string('customer_code', 30)->nullable()->index()->after('visitor_uuid');
                }
                if (!Schema::hasColumn('visitor_sessions', 'customer_id')) {
                    $table->unsignedBigInteger('customer_id')->nullable()->index()->after('customer_code');
                }
                if (!Schema::hasColumn('visitor_sessions', 'customer_email')) {
                    $table->string('customer_email', 150)->nullable()->index()->after('customer_id');
                }
                if (!Schema::hasColumn('visitor_sessions', 'is_repeat_visitor')) {
                    $table->boolean('is_repeat_visitor')->default(false)->index()->after('customer_email');
                }
                if (!Schema::hasColumn('visitor_sessions', 'visit_number')) {
                    $table->unsignedInteger('visit_number')->default(1)->after('is_repeat_visitor');
                }
                if (!Schema::hasColumn('visitor_sessions', 'city')) {
                    $table->string('city', 100)->nullable()->after('ip_address');
                }
                if (!Schema::hasColumn('visitor_sessions', 'region')) {
                    $table->string('region', 100)->nullable()->after('city');
                }
                if (!Schema::hasColumn('visitor_sessions', 'country')) {
                    $table->string('country', 100)->nullable()->after('region');
                }
                if (!Schema::hasColumn('visitor_sessions', 'country_code')) {
                    $table->string('country_code', 10)->nullable()->after('country');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('visitor_sessions')) {
            Schema::table('visitor_sessions', function (Blueprint $table) {
                $columns = [
                    'customer_code',
                    'customer_id',
                    'customer_email',
                    'is_repeat_visitor',
                    'visit_number',
                    'city',
                    'region',
                    'country',
                    'country_code',
                ];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('visitor_sessions', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        Schema::dropIfExists('tracked_visitors');
    }
};
