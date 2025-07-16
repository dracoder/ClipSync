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
        Schema::table('videos', function (Blueprint $table) {
            // Add columns only if they don't already exist
            if (!Schema::hasColumn('videos', 'stream_type')) {
                $table->string('stream_type')->default('single')->after('file');
            }
            if (!Schema::hasColumn('videos', 'processing_status')) {
                $table->string('processing_status')->default('pending')->after('stream_type');
            }
            if (!Schema::hasColumn('videos', 'webcam_stream_path')) {
                $table->string('webcam_stream_path')->nullable()->after('processing_status');
            }
            if (!Schema::hasColumn('videos', 'screen_stream_path')) {
                $table->string('screen_stream_path')->nullable()->after('webcam_stream_path');
            }
            if (!Schema::hasColumn('videos', 'stream_metadata')) {
                $table->json('stream_metadata')->nullable()->after('screen_stream_path');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropColumn([
                'stream_type',
                'processing_status', 
                'webcam_stream_path',
                'screen_stream_path',
                'stream_metadata'
            ]);
        });
    }
};
