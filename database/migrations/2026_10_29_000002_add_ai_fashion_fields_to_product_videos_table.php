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
                if (!Schema::hasColumn('product_videos', 'ai_provider')) {
                    $table->string('ai_provider', 50)->nullable()->after('video_type');
                }
                if (!Schema::hasColumn('product_videos', 'ai_model')) {
                    $table->string('ai_model', 100)->nullable()->after('ai_provider');
                }
                if (!Schema::hasColumn('product_videos', 'ai_operation_id')) {
                    $table->string('ai_operation_id', 255)->nullable()->after('ai_model');
                }
                if (!Schema::hasColumn('product_videos', 'ai_master_video_path')) {
                    $table->string('ai_master_video_path', 500)->nullable()->after('video_path');
                }
                if (!Schema::hasColumn('product_videos', 'prompt')) {
                    $table->text('prompt')->nullable()->after('render_settings');
                }
                if (!Schema::hasColumn('product_videos', 'negative_prompt')) {
                    $table->text('negative_prompt')->nullable()->after('prompt');
                }
                if (!Schema::hasColumn('product_videos', 'prompt_template')) {
                    $table->string('prompt_template', 50)->nullable()->after('template');
                }
                if (!Schema::hasColumn('product_videos', 'primary_image_id')) {
                    $table->integer('primary_image_id')->nullable()->after('selected_image_ids');
                }
                if (!Schema::hasColumn('product_videos', 'approval_status')) {
                    $table->string('approval_status', 30)->default('pending')->after('status'); // 'pending', 'approved', 'rejected'
                }
                if (!Schema::hasColumn('product_videos', 'rejection_reason')) {
                    $table->text('rejection_reason')->nullable()->after('approval_status');
                }
                if (!Schema::hasColumn('product_videos', 'quality_checklist')) {
                    $table->json('quality_checklist')->nullable()->after('rejection_reason');
                }
                if (!Schema::hasColumn('product_videos', 'estimated_cost')) {
                    $table->decimal('estimated_cost', 8, 4)->nullable()->after('file_size_bytes');
                }
                if (!Schema::hasColumn('product_videos', 'actual_cost')) {
                    $table->decimal('actual_cost', 8, 4)->nullable()->after('estimated_cost');
                }
                if (!Schema::hasColumn('product_videos', 'completed_at')) {
                    $table->timestamp('completed_at')->nullable()->after('updated_at');
                }
                if (!Schema::hasColumn('product_videos', 'reviewed_at')) {
                    $table->timestamp('reviewed_at')->nullable()->after('completed_at');
                }
                if (!Schema::hasColumn('product_videos', 'reviewed_by')) {
                    $table->integer('reviewed_by')->nullable()->after('reviewed_at');
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
                $columns = [
                    'ai_provider',
                    'ai_model',
                    'ai_operation_id',
                    'ai_master_video_path',
                    'prompt',
                    'negative_prompt',
                    'prompt_template',
                    'primary_image_id',
                    'approval_status',
                    'rejection_reason',
                    'quality_checklist',
                    'estimated_cost',
                    'actual_cost',
                    'completed_at',
                    'reviewed_at',
                    'reviewed_by',
                ];

                foreach ($columns as $column) {
                    if (Schema::hasColumn('product_videos', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
