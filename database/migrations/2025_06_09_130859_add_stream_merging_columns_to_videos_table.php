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
            $table->enum('stream_type', ['single', 'webcam', 'screen', 'merged'])->default('single')->after('file');
            $table->enum('processing_status', ['pending', 'processing', 'completed', 'failed'])->default('completed')->after('stream_type');
            $table->string('webcam_stream_path')->nullable()->after('processing_status');
            $table->string('screen_stream_path')->nullable()->after('webcam_stream_path');
            $table->json('stream_metadata')->nullable()->after('screen_stream_path');
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
