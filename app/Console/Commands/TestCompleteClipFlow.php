<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use App\Repositories\Clip\ClipRepository;
use App\Services\VideoMergeService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class TestCompleteClipFlow extends Command
{
    protected $signature = 'test:complete-clip-flow';
    protected $description = 'Test complete clip creation flow with dual streams';

    private $testFiles = [];
    private $tempDirectory;

    public function handle()
    {
        $this->info('🧪 Testing Complete Clip Creation Flow - Frontend to Backend');
        
        try {
            $this->setupTestEnvironment();
            $this->createTestVideos();
            $this->testDualStreamUpload();
            $this->testSingleStreamFallbacks();
            $this->cleanup();
            
            $this->info('✅ All complete flow tests passed!');
            
        } catch (\Exception $e) {
            $this->error('❌ Test failed: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            $this->cleanup();
            return 1;
        }
    }

    private function setupTestEnvironment()
    {
        $this->info('📁 Setting up test environment...');
        
        $this->tempDirectory = storage_path('app/temp/test_clip_flow_' . time());
        if (!file_exists($this->tempDirectory)) {
            mkdir($this->tempDirectory, 0755, true);
        }
        
        Log::info('Test environment ready', ['temp_dir' => $this->tempDirectory]);
    }

    private function createTestVideos()
    {
        $this->info('🎬 Creating test video files...');
        
        // Create test webcam video (simulating frontend webcam recording)
        $webcamPath = $this->tempDirectory . '/test_webcam.mp4';
        $this->createTestVideo($webcamPath, '640x480', 5, 'Webcam Test Video');
        $this->testFiles['webcam'] = $webcamPath;
        
        // Create test screen video (simulating frontend screen recording)
        $screenPath = $this->tempDirectory . '/test_screen.mp4';
        $this->createTestVideo($screenPath, '1920x1080', 5, 'Screen Share Test Video');
        $this->testFiles['screen'] = $screenPath;
        
        $this->info('✅ Test videos created successfully');
        $this->info('   📹 Webcam: ' . filesize($webcamPath) . ' bytes');
        $this->info('   🖥️  Screen: ' . filesize($screenPath) . ' bytes');
    }

    private function createTestVideo($outputPath, $resolution, $duration, $text)
    {
        $ffmpegBinary = config('app.ffmpeg_path', 'ffmpeg');
        
        // Create a test video with color and text overlay
        $command = sprintf(
            '%s -f lavfi -i "testsrc2=size=%s:duration=%d:rate=30" -f lavfi -i "sine=frequency=1000:duration=%d" -vf "drawtext=text=\'%s\':fontsize=30:fontcolor=white:x=(w-text_w)/2:y=(h-text_h)/2" -c:v libx264 -c:a aac -shortest "%s"',
            $ffmpegBinary,
            $resolution,
            $duration,
            $duration,
            $text,
            $outputPath
        );
        
        $output = [];
        $returnCode = 0;
        exec($command . ' 2>&1', $output, $returnCode);
        
        if ($returnCode !== 0 || !file_exists($outputPath)) {
            throw new \Exception("Failed to create test video: " . implode("\n", $output));
        }
    }

    private function testDualStreamUpload()
    {
        $this->info('🎬 Testing dual stream upload (screen + webcam)...');
        
        // Create clip with dual streams (simulating frontend upload)
        $clip = $this->createTestClip('Dual Stream Test');
        
        // Test the actual dual stream processing
        $success = $this->processDualStreams($clip->id);
        
        if (!$success) {
            throw new \Exception('Dual stream processing failed');
        }
        
        // Verify the result
        $this->verifyMergedVideo($clip->id, 'merged');
        
        $this->info('✅ Dual stream upload test passed');
    }

    private function testSingleStreamFallbacks()
    {
        $this->info('🧪 Testing single stream fallbacks...');
        
        // Test webcam-only scenario
        $webcamClip = $this->createTestClip('Webcam Only Test');
        $this->processSingleStream($webcamClip->id, 'webcam');
        $this->verifyMergedVideo($webcamClip->id, 'webcam');
        
        // Test screen-only scenario  
        $screenClip = $this->createTestClip('Screen Only Test');
        $this->processSingleStream($screenClip->id, 'screen');
        $this->verifyMergedVideo($screenClip->id, 'screen');
        
        $this->info('✅ Single stream fallback tests passed');
    }

    private function createTestClip($title)
    {
        $clipRepository = app(ClipRepository::class);
        
        $clipData = [
            'title' => $title,
            'content' => 'Test clip content for ' . $title,
            'user_id' => 1, // Assuming user ID 1 exists
            'disable_comments' => false,
            'private_comments' => false
        ];
        
        return $clipRepository->store($clipData);
    }

    private function processDualStreams($clipId)
    {
        $this->info('   📤 Processing dual streams for clip ID: ' . $clipId);
        
        try {
            $clipRepository = app(ClipRepository::class);
            
            // Create UploadedFile instances (simulating HTTP request)
        $webcamFile = new UploadedFile(
                $this->testFiles['webcam'],
            'webcam.mp4',
            'video/mp4',
            null,
            true
        );
        
        $screenFile = new UploadedFile(
                $this->testFiles['screen'],
            'screen.mp4',
            'video/mp4',
            null,
            true
        );
        
            // Create metadata (simulating frontend metadata)
        $metadata = [
            'session_id' => 'test-session-' . time(),
            'recording_duration' => 5000,
                'screen_sharing_used_during_recording' => true,
            'sync_timestamps' => [
                'sessionStart' => time() * 1000,
                'webcamStart' => time() * 1000,
                'screenStart' => time() * 1000,
                'webcamEnd' => (time() + 5) * 1000,
                'screenEnd' => (time() + 5) * 1000,
            ],
            'webcam_stream_info' => [
                'start_time' => time() * 1000,
                'end_time' => (time() + 5) * 1000,
                'chunk_count' => 20,
                    'total_size' => filesize($this->testFiles['webcam']),
                'mime_type' => 'video/mp4'
            ],
            'screen_stream_info' => [
                'start_time' => time() * 1000,
                'end_time' => (time() + 5) * 1000,
                'chunk_count' => 20,
                    'total_size' => filesize($this->testFiles['screen']),
                'mime_type' => 'video/mp4'
            ],
            'overlay_settings' => [
                'position' => 'bottom-right',
                'size' => 25,
                'custom_x' => null,
                'custom_y' => null,
                'visible' => true
                ]
            ];
            
            // Process the dual streams
            $clipRepository->uploadDualStreams($webcamFile, $screenFile, $clipId, $metadata);
            
            $this->info('   ✅ Dual stream processing completed');
            return true;
            
        } catch (\Exception $e) {
            $this->error('   ❌ Dual stream processing failed: ' . $e->getMessage());
            $this->error('   Stack trace: ' . $e->getTraceAsString());
            return false;
        }
    }

    private function processSingleStream($clipId, $streamType)
    {
        $this->info('   📤 Processing ' . $streamType . ' stream for clip ID: ' . $clipId);
        
        try {
            $clipRepository = app(ClipRepository::class);
            
            $filePath = $this->testFiles[$streamType];
            $file = new UploadedFile($filePath, $streamType . '.mp4', 'video/mp4', null, true);
            
            $metadata = [
                'session_id' => 'test-session-' . time(),
                'recording_duration' => 5000,
                'screen_sharing_used_during_recording' => false,
                'overlay_settings' => [
                    'position' => 'bottom-right',
                    'size' => 25
                ]
            ];
            
            if ($streamType === 'webcam') {
                $clipRepository->uploadDualStreams($file, null, $clipId, $metadata);
            } else {
                $clipRepository->uploadDualStreams(null, $file, $clipId, $metadata);
            }
            
            $this->info('   ✅ ' . ucfirst($streamType) . ' stream processing completed');
            return true;
            
        } catch (\Exception $e) {
            $this->error('   ❌ ' . ucfirst($streamType) . ' stream processing failed: ' . $e->getMessage());
            return false;
        }
    }

    private function verifyMergedVideo($clipId, $expectedType)
    {
        $this->info('   🔍 Verifying merged video for clip ID: ' . $clipId);
        
        $basePath = 'public/clip-videos/' . $clipId . '/';
        $files = Storage::files($basePath);
        
        $mergedVideoFound = false;
        $mergedVideoPath = null;
        
        foreach ($files as $file) {
            if (str_contains($file, '.mp4') && !str_contains($file, '_webcam') && !str_contains($file, '_screen')) {
                $mergedVideoFound = true;
                $mergedVideoPath = storage_path('app/' . $file);
                break;
            }
        }
        
        if (!$mergedVideoFound) {
            throw new \Exception('No merged video found for clip ' . $clipId);
        }
        
        // Verify video properties
        $this->verifyVideoProperties($mergedVideoPath, $expectedType);
        
        $this->info('   ✅ Merged video verification passed for ' . $expectedType);
    }

    private function verifyVideoProperties($videoPath, $expectedType)
    {
        $ffmpegBinary = config('app.ffmpeg_path', 'ffmpeg');
        
        // Get video info using ffprobe
        $command = 'ffprobe -v quiet -print_format json -show_format -show_streams "' . $videoPath . '"';
        $output = shell_exec($command);
        
        if (!$output) {
            throw new \Exception('Failed to analyze merged video');
        }
        
        $videoInfo = json_decode($output, true);
        
        if (!$videoInfo || !isset($videoInfo['streams'])) {
            throw new \Exception('Invalid video analysis result');
        }
        
        $videoStream = null;
        foreach ($videoInfo['streams'] as $stream) {
            if ($stream['codec_type'] === 'video') {
                $videoStream = $stream;
                break;
            }
        }
        
        if (!$videoStream) {
            throw new \Exception('No video stream found in merged video');
        }
        
        $width = $videoStream['width'];
        $height = $videoStream['height'];
        $duration = floatval($videoInfo['format']['duration']);
        
        $this->info('   📊 Video properties: ' . $width . 'x' . $height . ', duration: ' . round($duration, 2) . 's');
        
        // Verify expected dimensions based on type
        switch ($expectedType) {
            case 'merged':
                // Should be screen dimensions (1920x1080)
                if ($width !== 1920 || $height !== 1080) {
                    $this->warn('   ⚠️  Expected 1920x1080 for merged video, got ' . $width . 'x' . $height);
                }
                break;
            case 'webcam':
                // Should be webcam dimensions (640x480)
                if ($width !== 640 || $height !== 480) {
                    $this->warn('   ⚠️  Expected 640x480 for webcam video, got ' . $width . 'x' . $height);
                }
                break;
            case 'screen':
                // Should be screen dimensions (1920x1080)
                if ($width !== 1920 || $height !== 1080) {
                    $this->warn('   ⚠️  Expected 1920x1080 for screen video, got ' . $width . 'x' . $height);
                }
                break;
        }
        
        // Verify duration is reasonable (should be around 5 seconds)
        if ($duration < 4 || $duration > 6) {
            $this->warn('   ⚠️  Expected ~5 second duration, got ' . round($duration, 2) . 's');
        }
    }

    private function cleanup()
    {
        $this->info('🧹 Cleaning up test files...');
        
        if ($this->tempDirectory && file_exists($this->tempDirectory)) {
            $this->deleteDirectory($this->tempDirectory);
        }
        
        $this->info('✅ Cleanup completed');
    }

    private function deleteDirectory($dir)
    {
        if (!file_exists($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
            foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
} 