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
        if (!Schema::hasTable('visitor_sessions')) {
            Schema::create('visitor_sessions', function (Blueprint $table) {
                $table->id();
                $table->string('session_id', 64)->unique();
                $table->string('visitor_uuid', 36)->index();
                $table->string('platform', 32)->default('Direct')->index();
                $table->text('referrer')->nullable();
                $table->string('referrer_domain', 120)->nullable()->index();
                $table->string('landing_page', 500);
                $table->string('landing_path', 255)->index();
                $table->string('utm_source', 80)->nullable()->index();
                $table->string('utm_medium', 80)->nullable();
                $table->string('utm_campaign', 120)->nullable()->index();
                $table->string('utm_term', 120)->nullable();
                $table->string('utm_content', 120)->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent', 500)->nullable();
                $table->string('device_type', 20)->default('desktop')->index();
                $table->string('browser', 60)->nullable();
                $table->string('os', 60)->nullable();
                $table->unsignedInteger('page_views_count')->default(1);
                $table->dateTime('started_at')->index();
                $table->dateTime('last_activity_at')->index();
                $table->timestamps();

                $table->index(['platform', 'started_at']);
            });
        }

        if (!Schema::hasTable('visitor_page_views')) {
            Schema::create('visitor_page_views', function (Blueprint $table) {
                $table->id();
                $table->string('session_id', 64)->index();
                $table->string('visitor_uuid', 36)->index();
                $table->unsignedInteger('step_number')->default(1);
                $table->string('url', 500);
                $table->string('path', 255)->index();
                $table->string('page_title', 200)->nullable();
                $table->unsignedInteger('duration_seconds')->nullable();
                $table->dateTime('created_at')->index();

                $table->foreign('session_id')->references('session_id')->on('visitor_sessions')->onDelete('cascade');
                $table->index(['session_id', 'step_number']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visitor_page_views');
        Schema::dropIfExists('visitor_sessions');
    }
};
