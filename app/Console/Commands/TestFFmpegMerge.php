<?php

namespace App\Console\Commands;

use App\Services\VideoMergeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TestFFmpegMerge extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:ffmpeg-merge {--create-samples} {--debug}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test FFmpeg video merging functionality with dual streams';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🎬 Testing FFmpeg Video Merge Functionality');
        $this->info('==========================================');

        try {
            // Test FFmpeg availability
            $this->testFFmpegAvailability();
            
            // Test VideoMergeService instantiation
            $this->testServiceInstantiation();
            
            // If create-samples flag is set, create test samples
            if ($this->option('create-samples')) {
                $this->createTestSamples();
            }
            
            // Test basic video analysis
            $this->testVideoAnalysis();
            
            $this->info('✅ All tests completed successfully!');
            
        } catch (\Exception $e) {
            $this->error('❌ Test failed: ' . $e->getMessage());
            
            if ($this->option('debug')) {
                $this->error('Stack trace:');
                $this->error($e->getTraceAsString());
            }
            
            return 1;
        }
        
        return 0;
    }

    private function testFFmpegAvailability()
    {
        $this->info('Testing FFmpeg availability...');
        
        $ffmpegPath = config('ffmpeg.ffmpeg.binaries', 'ffmpeg');
        $ffprobePath = config('ffmpeg.ffprobe.binaries', 'ffprobe');
        
        // Test FFmpeg
        $ffmpegOutput = shell_exec("\"{$ffmpegPath}\" -version 2>&1");
        if (strpos($ffmpegOutput, 'ffmpeg version') === false) {
            throw new \Exception("FFmpeg not found or not working. Path: {$ffmpegPath}");
        }
        
        // Test FFprobe
        $ffprobeOutput = shell_exec("\"{$ffprobePath}\" -version 2>&1");
        if (strpos($ffprobeOutput, 'ffprobe version') === false) {
            throw new \Exception("FFprobe not found or not working. Path: {$ffprobePath}");
        }
        
        // Extract version info
        preg_match('/ffmpeg version ([^\s]+)/', $ffmpegOutput, $matches);
        $version = $matches[1] ?? 'unknown';
        
        $this->info("✅ FFmpeg version: {$version}");
        $this->info("✅ FFprobe available");
    }

    private function testServiceInstantiation()
    {
        $this->info('Testing VideoMergeService instantiation...');
        
        $service = app(VideoMergeService::class);
        
        if (!$service instanceof VideoMergeService) {
            throw new \Exception('Failed to instantiate VideoMergeService');
        }
        
        $this->info('✅ VideoMergeService instantiated successfully');
    }

    private function createTestSamples()
    {
        $this->info('Creating test video samples...');
        
        $ffmpegPath = config('ffmpeg.ffmpeg.binaries', 'ffmpeg');
        $testDir = storage_path('app/test-videos');
        
        if (!is_dir($testDir)) {
            mkdir($testDir, 0755, true);
        }
        
        // Create webcam test video (640x480, 5 seconds, with audio tone)
        $webcamPath = $testDir . '/test_webcam.mp4';
        $webcamCommand = "\"{$ffmpegPath}\" -y -f lavfi -i \"testsrc2=duration=5:size=640x480:rate=30\" " .
                        "-f lavfi -i \"sine=frequency=440:duration=5\" " .
                        "-c:v libx264 -preset ultrafast -c:a aac " .
                        "\"{$webcamPath}\" 2>&1";
        
        $this->info('Creating webcam test video...');
        $output = shell_exec($webcamCommand);
        
        if (!file_exists($webcamPath)) {
            throw new \Exception("Failed to create webcam test video. Output: " . $output);
        }
        
        // Create screen test video (1920x1080, 5 seconds, with different audio tone)
        $screenPath = $testDir . '/test_screen.mp4';
        $screenCommand = "\"{$ffmpegPath}\" -y -f lavfi -i \"testsrc2=duration=5:size=1920x1080:rate=30\" " .
                        "-f lavfi -i \"sine=frequency=880:duration=5\" " .
                        "-c:v libx264 -preset ultrafast -c:a aac " .
                        "\"{$screenPath}\" 2>&1";
        
        $this->info('Creating screen test video...');
        $output = shell_exec($screenCommand);
        
        if (!file_exists($screenPath)) {
            throw new \Exception("Failed to create screen test video. Output: " . $output);
        }
        
        $this->info('✅ Test videos created successfully');
        $this->info("   - Webcam: {$webcamPath}");
        $this->info("   - Screen: {$screenPath}");
    }

    private function testVideoAnalysis()
    {
        $this->info('Testing video stream analysis...');
        
        $testDir = storage_path('app/test-videos');
        $webcamPath = $testDir . '/test_webcam.mp4';
        $screenPath = $testDir . '/test_screen.mp4';
        
        // Check if test videos exist
        if (!file_exists($webcamPath) || !file_exists($screenPath)) {
            $this->warn('Test videos not found. Run with --create-samples to create them first.');
            return;
        }
        
        $service = app(VideoMergeService::class);
        
        // Test stream analysis via reflection (since the method is private)
        $reflection = new \ReflectionClass($service);
        $analyzeMethod = $reflection->getMethod('analyzeVideoStream');
        $analyzeMethod->setAccessible(true);
        
        // Analyze webcam video
        $webcamInfo = $analyzeMethod->invoke($service, $webcamPath);
        $this->info('Webcam analysis: ' . json_encode($webcamInfo, JSON_PRETTY_PRINT));
        
        // Analyze screen video
        $screenInfo = $analyzeMethod->invoke($service, $screenPath);
        $this->info('Screen analysis: ' . json_encode($screenInfo, JSON_PRETTY_PRINT));
        
        // Test actual merge
        $this->info('Testing actual video merge...');
        
        $metadata = [
            'overlay_settings' => [
                'position' => 'bottom-right',
                'size' => 20,
                'custom_x' => null,
                'custom_y' => null,
                'visible' => true
            ],
            'sync_timestamps' => [
                'webcam_start' => time() * 1000,
                'screen_start' => time() * 1000,
            ]
        ];
        
        try {
            $mergedPath = $service->mergeStreams($webcamPath, $screenPath, $metadata);
            
            if (file_exists($mergedPath)) {
                $fileSize = filesize($mergedPath);
                $this->info("✅ Video merge successful! Output: {$mergedPath} (Size: {$fileSize} bytes)");
                
                // Test with ffprobe to verify the merged video
                $ffprobePath = config('ffmpeg.ffprobe.binaries', 'ffprobe');
                $probeCommand = "\"{$ffprobePath}\" -v quiet -print_format json -show_format -show_streams \"{$mergedPath}\"";
                $probeOutput = shell_exec($probeCommand);
                $probeData = json_decode($probeOutput, true);
                
                if ($probeData) {
                    $videoStreams = array_filter($probeData['streams'], fn($s) => $s['codec_type'] === 'video');
                    $audioStreams = array_filter($probeData['streams'], fn($s) => $s['codec_type'] === 'audio');
                    
                    $this->info("   Video streams: " . count($videoStreams));
                    $this->info("   Audio streams: " . count($audioStreams));
                    
                    if (count($videoStreams) === 1 && count($audioStreams) === 1) {
                        $this->info("✅ Merged video has correct stream structure");
                    } else {
                        $this->warn("⚠️  Merged video has unexpected stream structure");
                    }
                } else {
                    $this->warn("⚠️  Could not analyze merged video with ffprobe");
                }
                
                // Test the full repository integration
                $this->testRepositoryIntegration($webcamPath, $screenPath, $metadata);
                
            } else {
                throw new \Exception("Merged video file was not created");
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Video merge failed: " . $e->getMessage());
            
            if ($this->option('debug')) {
                // Let's try to debug the FFmpeg command
                Log::info('FFmpeg merge error details', ['exception' => $e]);
            }
            
            throw $e;
        }
    }

    private function testRepositoryIntegration($webcamPath, $screenPath, $metadata)
    {
        $this->info('Testing repository integration...');
        
        try {
            // Create test uploaded files
            $webcamFile = new \Illuminate\Http\Testing\File('webcam.mp4', fopen($webcamPath, 'r'));
            $screenFile = new \Illuminate\Http\Testing\File('screen.mp4', fopen($screenPath, 'r'));
            
            // Test data
            $testData = [
                'title' => 'Test Dual Stream Clip',
                'content' => 'Testing the dual stream merging system',
                'user_id' => 1, // Assuming a test user exists
                'disable_comments' => false,
                'private_comments' => false
            ];
            
            // Test the repository
            $clipRepository = app(\App\Repositories\Clip\ClipRepository::class);
            
            // Create a temporary user if needed
            $user = \App\Models\User::first() ?? \App\Models\User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => bcrypt('password')
            ]);
            $testData['user_id'] = $user->id;
            
            $this->info('Testing ClipRepository::storeWithDualStreams...');
            $clip = $clipRepository->storeWithDualStreams($testData, $webcamFile, $screenFile, $metadata);
            
            if ($clip && $clip->id) {
                $this->info("✅ Clip created successfully with ID: {$clip->id}");
                
                // Check the video record
                $video = $clip->video;
                if ($video) {
                    $this->info("✅ Video record created: {$video->file}");
                    $this->info("   Stream type: {$video->stream_type}");
                    $this->info("   Processing status: {$video->processing_status}");
                    
                    // Check if the final video file exists
                    $finalVideoPath = storage_path('app/public/' . \App\Models\Clip\Clip::VIDEO_PATH . $clip->id . '/' . $video->file);
                    if (file_exists($finalVideoPath)) {
                        $finalSize = filesize($finalVideoPath);
                        $this->info("✅ Final video file exists (Size: {$finalSize} bytes)");
                    } else {
                        $this->warn("⚠️  Final video file not found: {$finalVideoPath}");
                    }
                } else {
                    $this->warn("⚠️  Video record not created for clip");
                }
                
                // Clean up test clip
                $clip->delete();
                $this->info("✅ Test clip cleaned up");
                
            } else {
                throw new \Exception("Failed to create clip with dual streams");
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Repository integration test failed: " . $e->getMessage());
            
            if ($this->option('debug')) {
                $this->error('Stack trace:');
                $this->error($e->getTraceAsString());
            }
            
            throw $e;
        }
    }
}
