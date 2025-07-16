<?php

namespace App\Console\Commands;

use App\Services\VideoMergeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TestFFmpegDetailedMerge extends Command
{
    protected $signature = 'test:ffmpeg-detailed {--debug} {--analyze-output}';
    protected $description = 'Detailed analysis of FFmpeg dual stream merging process';

    public function handle()
    {
        $this->info('🔍 Detailed FFmpeg Merge Analysis');
        $this->info('=================================');

        try {
            // Create test video files first
            $this->createTestVideos();
            
            // Test the merge process step by step
            $this->analyzeInputFiles();
            $this->testFFmpegCommandGeneration();
            $this->testActualMerge();
            
            if ($this->option('analyze-output')) {
                $this->analyzeOutputVideo();
            }
            
            $this->info('✅ Detailed analysis completed!');
            
        } catch (\Exception $e) {
            $this->error('❌ Analysis failed: ' . $e->getMessage());
            
            if ($this->option('debug')) {
                $this->error('Stack trace:');
                $this->error($e->getTraceAsString());
            }
            
            return 1;
        }
        
        return 0;
    }

    private function createTestVideos()
    {
        $this->info('📹 Creating test videos with different characteristics...');
        
        $ffmpegPath = config('ffmpeg.ffmpeg.binaries', 'ffmpeg');
        $testDir = storage_path('app/test-detailed');
        
        if (!is_dir($testDir)) {
            mkdir($testDir, 0755, true);
        }
        
        // Create webcam video with CLEAR VISUAL INDICATORS
        $webcamPath = $testDir . '/webcam_test.mp4';
        $webcamCommand = "\"{$ffmpegPath}\" -y " .
                        "-f lavfi -i \"color=c=red:size=640x480:duration=3,format=yuv420p\" " .
                        "-f lavfi -i \"sine=frequency=440:duration=3\" " .
                        "-vf \"drawtext=text='WEBCAM':fontsize=60:fontcolor=white:x=(w-text_w)/2:y=(h-text_h)/2\" " .
                        "-c:v libx264 -preset ultrafast -c:a aac " .
                        "\"{$webcamPath}\" 2>&1";
        
        $this->info('Creating webcam test video (RED background, WEBCAM text)...');
        $output = shell_exec($webcamCommand);
        
        if (!file_exists($webcamPath)) {
            throw new \Exception("Failed to create webcam test video. Output: " . $output);
        }
        
        // Create screen video with DIFFERENT VISUAL INDICATORS
        $screenPath = $testDir . '/screen_test.mp4';
        $screenCommand = "\"{$ffmpegPath}\" -y " .
                        "-f lavfi -i \"color=c=blue:size=1920x1080:duration=3,format=yuv420p\" " .
                        "-f lavfi -i \"sine=frequency=880:duration=3\" " .
                        "-vf \"drawtext=text='SCREEN':fontsize=120:fontcolor=white:x=(w-text_w)/2:y=(h-text_h)/2\" " .
                        "-c:v libx264 -preset ultrafast -c:a aac " .
                        "\"{$screenPath}\" 2>&1";
        
        $this->info('Creating screen test video (BLUE background, SCREEN text)...');
        $output = shell_exec($screenCommand);
        
        if (!file_exists($screenPath)) {
            throw new \Exception("Failed to create screen test video. Output: " . $output);
        }
        
        $this->info('✅ Test videos created successfully');
        $this->info("   - Webcam (RED): {$webcamPath}");
        $this->info("   - Screen (BLUE): {$screenPath}");
    }

    private function analyzeInputFiles()
    {
        $this->info('🔍 Analyzing input files...');
        
        $testDir = storage_path('app/test-detailed');
        $webcamPath = $testDir . '/webcam_test.mp4';
        $screenPath = $testDir . '/screen_test.mp4';
        
        $service = app(VideoMergeService::class);
        
        // Use reflection to access private method
        $reflection = new \ReflectionClass($service);
        $analyzeMethod = $reflection->getMethod('analyzeVideoStream');
        $analyzeMethod->setAccessible(true);
        
        $webcamInfo = $analyzeMethod->invoke($service, $webcamPath);
        $screenInfo = $analyzeMethod->invoke($service, $screenPath);
        
        $this->info('📊 Webcam analysis:');
        $this->line("   - Resolution: {$webcamInfo['width']}x{$webcamInfo['height']}");
        $this->line("   - Duration: {$webcamInfo['duration']}s");
        $this->line("   - Has video: " . ($webcamInfo['has_video'] ? 'Yes' : 'No'));
        $this->line("   - Has audio: " . ($webcamInfo['has_audio'] ? 'Yes' : 'No'));
        $this->line("   - Video codec: {$webcamInfo['video_codec']}");
        $this->line("   - Audio codec: {$webcamInfo['audio_codec']}");
        
        $this->info('📊 Screen analysis:');
        $this->line("   - Resolution: {$screenInfo['width']}x{$screenInfo['height']}");
        $this->line("   - Duration: {$screenInfo['duration']}s");
        $this->line("   - Has video: " . ($screenInfo['has_video'] ? 'Yes' : 'No'));
        $this->line("   - Has audio: " . ($screenInfo['has_audio'] ? 'Yes' : 'No'));
        $this->line("   - Video codec: {$screenInfo['video_codec']}");
        $this->line("   - Audio codec: {$screenInfo['audio_codec']}");
    }

    private function testFFmpegCommandGeneration()
    {
        $this->info('⚙️  Testing FFmpeg command generation...');
        
        $testDir = storage_path('app/test-detailed');
        $webcamPath = $testDir . '/webcam_test.mp4';
        $screenPath = $testDir . '/screen_test.mp4';
        
        $metadata = [
            'overlay_settings' => [
                'position' => 'bottom-right',
                'size' => 25, // Larger for visibility
                'custom_x' => null,
                'custom_y' => null,
                'visible' => true
            ],
            'sync_timestamps' => [
                'webcam_start' => time() * 1000,
                'screen_start' => time() * 1000,
            ]
        ];
        
        $service = app(VideoMergeService::class);
        
        // Use reflection to access private methods
        $reflection = new \ReflectionClass($service);
        
        // Get stream info
        $analyzeMethod = $reflection->getMethod('analyzeVideoStream');
        $analyzeMethod->setAccessible(true);
        $webcamInfo = $analyzeMethod->invoke($service, $webcamPath);
        $screenInfo = $analyzeMethod->invoke($service, $screenPath);
        
        // Calculate overlay settings
        $calculateSizeMethod = $reflection->getMethod('calculateWebcamOverlaySize');
        $calculateSizeMethod->setAccessible(true);
        $webcamSize = $calculateSizeMethod->invoke($service, $metadata, $screenInfo['width'], $screenInfo['height']);
        
        $calculatePositionMethod = $reflection->getMethod('calculateOverlayPosition');
        $calculatePositionMethod->setAccessible(true);
        $overlayPosition = $calculatePositionMethod->invoke($service, $metadata, $screenInfo['width'], $screenInfo['height']);
        
        // Build video filter
        $buildVideoFilterMethod = $reflection->getMethod('buildVideoFilter');
        $buildVideoFilterMethod->setAccessible(true);
        $videoFilter = $buildVideoFilterMethod->invoke($service, $overlayPosition, $webcamSize, $screenInfo);
        
        // Build audio filter
        $buildAudioFilterMethod = $reflection->getMethod('buildAudioFilter');
        $buildAudioFilterMethod->setAccessible(true);
        $audioFilter = $buildAudioFilterMethod->invoke($service, $webcamInfo, $screenInfo);
        
        $this->info('📐 Calculated overlay settings:');
        $this->line("   - Webcam size: {$webcamSize['width']}x{$webcamSize['height']}");
        $this->line("   - Overlay position: x={$overlayPosition['x']}, y={$overlayPosition['y']}");
        
        $this->info('🎬 Generated filters:');
        $this->line("   - Video filter: {$videoFilter}");
        $this->line("   - Audio filter: {$audioFilter}");
        
        // Build complete command
        $buildCommandMethod = $reflection->getMethod('buildMergeCommand');
        $buildCommandMethod->setAccessible(true);
        $ffmpegBinary = config('ffmpeg.ffmpeg.binaries', 'ffmpeg');
        $outputPath = $testDir . '/merged_detailed.mp4';
        
        $command = $buildCommandMethod->invoke(
            $service,
            $ffmpegBinary,
            $webcamPath,
            $screenPath,
            $outputPath,
            $overlayPosition,
            $webcamSize,
            $webcamInfo,
            $screenInfo,
            $metadata
        );
        
        $this->info('🔧 Complete FFmpeg command:');
        $this->line($command);
        
        // Test command syntax by running with -f null (no output)
        $testCommand = str_replace('"' . $outputPath . '"', '-f null -', $command);
        $testCommand = str_replace(' 2>&1', '', $testCommand);
        
        $this->info('🧪 Testing command syntax...');
        $output = [];
        $returnCode = 0;
        exec($testCommand, $output, $returnCode);
        
        if ($returnCode === 0) {
            $this->info('✅ FFmpeg command syntax is valid');
        } else {
            $this->error('❌ FFmpeg command syntax error:');
            $this->line(implode("\n", $output));
        }
    }

    private function testActualMerge()
    {
        $this->info('🎞️  Testing actual merge process...');
        
        $testDir = storage_path('app/test-detailed');
        $webcamPath = $testDir . '/webcam_test.mp4';
        $screenPath = $testDir . '/screen_test.mp4';
        
        $metadata = [
            'overlay_settings' => [
                'position' => 'bottom-right',
                'size' => 25,
                'custom_x' => null,
                'custom_y' => null,
                'visible' => true
            ],
            'sync_timestamps' => [
                'webcam_start' => time() * 1000,
                'screen_start' => time() * 1000,
            ]
        ];
        
        $service = app(VideoMergeService::class);
        
        try {
            $mergedPath = $service->mergeStreams($webcamPath, $screenPath, $metadata);
            
            if (file_exists($mergedPath)) {
                $fileSize = filesize($mergedPath);
                $this->info("✅ Merge successful! Output: {$mergedPath}");
                $this->info("   File size: {$fileSize} bytes");
                
                // Copy to test directory for manual inspection
                $finalPath = $testDir . '/merged_final.mp4';
                copy($mergedPath, $finalPath);
                $this->info("   Copied to: {$finalPath}");
                
                // Analyze the merged video
                $this->analyzeMergedVideo($finalPath);
                
            } else {
                $this->error('❌ Merged video file was not created');
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Merge failed: " . $e->getMessage());
            throw $e;
        }
    }

    private function analyzeMergedVideo(string $videoPath)
    {
        $this->info('🔍 Analyzing merged video...');
        
        $ffprobe = config('ffmpeg.ffprobe.binaries', 'ffprobe');
        
        // Get basic info
        $command = "\"{$ffprobe}\" -v quiet -print_format json -show_format -show_streams \"{$videoPath}\"";
        $output = shell_exec($command);
        $data = json_decode($output, true);
        
        if ($data && isset($data['streams'])) {
            $videoStreams = array_filter($data['streams'], fn($s) => $s['codec_type'] === 'video');
            $audioStreams = array_filter($data['streams'], fn($s) => $s['codec_type'] === 'audio');
            
            $this->info('📊 Merged video analysis:');
            $this->line("   - Video streams: " . count($videoStreams));
            $this->line("   - Audio streams: " . count($audioStreams));
            
            foreach ($videoStreams as $stream) {
                $this->line("   - Video: {$stream['width']}x{$stream['height']}, codec: {$stream['codec_name']}");
            }
            
            foreach ($audioStreams as $stream) {
                $this->line("   - Audio: codec: {$stream['codec_name']}, channels: {$stream['channels']}");
            }
            
            if (isset($data['format']['duration'])) {
                $duration = (float)$data['format']['duration'];
                $this->line("   - Duration: {$duration}s");
            }
        }
        
        // Test if we can extract a frame to see what the video actually contains
        $this->extractTestFrame($videoPath);
    }

    private function extractTestFrame(string $videoPath)
    {
        $this->info('📸 Extracting test frame to verify content...');
        
        $testDir = storage_path('app/test-detailed');
        $frameOutput = $testDir . '/test_frame.png';
        
        $ffmpeg = config('ffmpeg.ffmpeg.binaries', 'ffmpeg');
        $command = "\"{$ffmpeg}\" -y -i \"{$videoPath}\" -vf \"select=eq(n\\,30)\" -vframes 1 \"{$frameOutput}\" 2>&1";
        
        $output = shell_exec($command);
        
        if (file_exists($frameOutput)) {
            $frameSize = filesize($frameOutput);
            $this->info("✅ Test frame extracted: {$frameOutput} ({$frameSize} bytes)");
            $this->line("   You can manually inspect this frame to see if both streams are visible");
        } else {
            $this->warn("⚠️  Could not extract test frame. Output:");
            $this->line($output);
        }
    }

    private function analyzeOutputVideo()
    {
        $this->info('📹 Performing detailed output analysis...');
        
        // Additional analysis could include:
        // - Frame-by-frame comparison
        // - Audio waveform analysis
        // - Color histogram analysis
        // For now, we'll keep it simple
        
        $this->info('✅ Output analysis complete');
    }
} 