<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\VideoMergeService;
use Illuminate\Support\Facades\Log;

class TestVideoMerge extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:video-merge {--check-ffmpeg : Only check FFmpeg installation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test video merge functionality and FFmpeg integration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🎥 Testing Video Merge Service...');
        
        // Check FFmpeg installation
        $this->checkFFmpeg();
        
        if ($this->option('check-ffmpeg')) {
            return 0;
        }
        
        // Test VideoMergeService instantiation
        try {
            $videoMergeService = app(VideoMergeService::class);
            $this->info('✅ VideoMergeService instantiated successfully');
        } catch (\Exception $e) {
            $this->error('❌ Failed to instantiate VideoMergeService: ' . $e->getMessage());
            return 1;
        }
        
        $this->info('🎉 Video merge service is ready for use!');
        $this->line('');
        $this->info('Next steps:');
        $this->line('1. Record a clip with both camera and screen sharing');
        $this->line('2. The system will automatically merge the streams on the backend');
        $this->line('3. Check the logs for merge operation details');
        
        return 0;
    }
    
    private function checkFFmpeg()
    {
        $ffmpegBinary = env('FFMPEG_BINARIES', 'ffmpeg');
        $ffprobeBinary = env('FFPROBE_BINARIES', 'ffprobe');
        
        $this->info('🔍 Checking FFmpeg installation...');
        $this->line("FFmpeg binary: {$ffmpegBinary}");
        $this->line("FFprobe binary: {$ffprobeBinary}");
        
        // Test FFmpeg
        $output = [];
        $returnVar = 0;
        exec("{$ffmpegBinary} -version 2>&1", $output, $returnVar);
        
        if ($returnVar === 0) {
            $version = isset($output[0]) ? $output[0] : 'Unknown version';
            $this->info("✅ FFmpeg found: {$version}");
        } else {
            $this->error("❌ FFmpeg not found or not working");
            $this->line("Please install FFmpeg and set FFMPEG_BINARIES in your .env file");
            return;
        }
        
        // Test FFprobe
        $output = [];
        $returnVar = 0;
        exec("{$ffprobeBinary} -version 2>&1", $output, $returnVar);
        
        if ($returnVar === 0) {
            $version = isset($output[0]) ? $output[0] : 'Unknown version';
            $this->info("✅ FFprobe found: {$version}");
        } else {
            $this->error("❌ FFprobe not found or not working");
            $this->line("Please install FFmpeg (includes FFprobe) and set FFPROBE_BINARIES in your .env file");
            return;
        }
        
        // Check if temp directory exists
        $tempDir = storage_path('app/temp');
        if (!file_exists($tempDir)) {
            $this->info("📁 Creating temp directory: {$tempDir}");
            mkdir($tempDir, 0755, true);
        } else {
            $this->info("✅ Temp directory exists: {$tempDir}");
        }
    }
}
