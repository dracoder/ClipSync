<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use App\Repositories\Clip\ClipRepository;
use App\Services\VideoMergeService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class DebugDualStreamIssue extends Command
{
    protected $signature = 'debug:dual-stream-issue';
    protected $description = 'Debug the dual stream issue to trace exactly what is happening';
    
    private $tempDirectory;
    private $testFiles;

    public function handle()
    {
        $this->info('🐛 DEBUGGING DUAL STREAM ISSUE');
        $this->info('==============================');
        
        try {
            $this->setupTest();
            $this->debugRepositoryUpload();
            
        } catch (\Exception $e) {
            $this->error('❌ Debug failed: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return 1;
        }
    }

    private function setupTest()
    {
        $this->info('📁 Setting up debug test...');
        
        $this->tempDirectory = storage_path('app/temp/debug_dual_stream_' . time());
        if (!file_exists($this->tempDirectory)) {
            mkdir($this->tempDirectory, 0755, true);
        }
        
        // Create test videos
        $webcamPath = $this->tempDirectory . '/debug_webcam.mp4';
        $screenPath = $this->tempDirectory . '/debug_screen.mp4';
        
        $this->createTestVideo($webcamPath, '640x480', 5, 'DEBUG WEBCAM');
        $this->createTestVideo($screenPath, '1920x1080', 5, 'DEBUG SCREEN');
        
        $this->testFiles = [
            'webcam' => $webcamPath,
            'screen' => $screenPath
        ];
        
        $this->info('✅ Test files created');
        $this->info('   📹 Webcam: ' . filesize($webcamPath) . ' bytes');
        $this->info('   🖥️  Screen: ' . filesize($screenPath) . ' bytes');
    }

    private function createTestVideo($outputPath, $resolution, $duration, $text)
    {
        $ffmpegBinary = config('app.ffmpeg_path', 'ffmpeg');
        
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

    private function debugRepositoryUpload()
    {
        $this->info('🔍 DEBUGGING REPOSITORY UPLOAD PROCESS');
        
        // Create a test clip
        $clipRepository = app(ClipRepository::class);
        $clip = $clipRepository->store([
            'title' => 'DEBUG Dual Stream Test',
            'content' => 'Debugging dual stream processing',
            'user_id' => 1,
            'disable_comments' => false,
            'private_comments' => false
        ]);
        
        $this->info('✅ Created test clip with ID: ' . $clip->id);
        
        // Create UploadedFile instances
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
        
        // Create comprehensive metadata
        $metadata = [
            'session_id' => 'debug-session-' . time(),
            'recording_duration' => 5000,
            'screen_sharing_used_during_recording' => true, // CRITICAL: This should trigger dual stream
            'screen_sharing_sessions' => [[
                'startTime' => time() * 1000,
                'sessionStart' => time() * 1000,
                'activatedDuringRecording' => true
            ]],
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
        
        $this->info('📊 DEBUG METADATA:');
        $this->info('   - Screen sharing used: ' . ($metadata['screen_sharing_used_during_recording'] ? 'YES' : 'NO'));
        $this->info('   - Screen sharing sessions: ' . count($metadata['screen_sharing_sessions']));
        $this->info('   - Webcam file size: ' . filesize($this->testFiles['webcam']));
        $this->info('   - Screen file size: ' . filesize($this->testFiles['screen']));
        
        // DEBUG: Check files before upload
        $this->info('🔍 PRE-UPLOAD CHECKS:');
        $this->info('   - Webcam file exists: ' . (file_exists($this->testFiles['webcam']) ? 'YES' : 'NO'));
        $this->info('   - Screen file exists: ' . (file_exists($this->testFiles['screen']) ? 'YES' : 'NO'));
        $this->info('   - Webcam file is valid: ' . ($webcamFile->isValid() ? 'YES' : 'NO'));
        $this->info('   - Screen file is valid: ' . ($screenFile->isValid() ? 'YES' : 'NO'));
        
        // Monitor exactly what happens in the repository method
        $this->info('📤 CALLING uploadDualStreams...');
        
        try {
            // Enable debug logging
            Log::info('🐛 DEBUG: Starting dual stream upload', [
                'webcam_file' => $webcamFile->getClientOriginalName(),
                'screen_file' => $screenFile->getClientOriginalName(),
                'metadata' => $metadata
            ]);
            
            $clipRepository->uploadDualStreams($webcamFile, $screenFile, $clip->id, $metadata);
            
            $this->info('✅ Upload completed successfully');
            
                    // Verify the result
        $this->verifyResult($clip->id);
            
        } catch (\Exception $e) {
            $this->error('❌ Upload failed: ' . $e->getMessage());
            throw $e;
        }
    }

    private function verifyResult($clipId)
    {
        $this->info('🔍 VERIFYING RESULT...');
        
        $basePath = 'public/clip-videos/' . $clipId . '/';
        $files = Storage::files($basePath);
        
        $this->info('📁 Files in clip directory:');
        foreach ($files as $file) {
            $size = Storage::size($file);
            $this->info('   - ' . basename($file) . ' (' . $size . ' bytes)');
        }
        
        // Check video record in database (fix: use correct foreign key)
        $video = \App\Models\Clip\Video::where('videoable_type', 'clip')
                                       ->where('videoable_id', $clipId)
                                       ->first();
        if ($video) {
            $this->info('📊 Video record in database:');
            $this->info('   - File: ' . $video->file);
            $this->info('   - Stream type: ' . ($video->stream_type ?? 'NULL'));
            $this->info('   - Webcam stream: ' . ($video->webcam_stream_path ?? 'NULL'));
            $this->info('   - Screen stream: ' . ($video->screen_stream_path ?? 'NULL'));
            
            // Check the actual merged video
            $videoPath = storage_path('app/public/clip-videos/' . $clipId . '/' . $video->file);
            if (file_exists($videoPath)) {
                $this->analyzeVideo($videoPath);
            } else {
                $this->error('❌ Final video file not found: ' . $videoPath);
            }
        } else {
            $this->error('❌ No video record found in database');
        }
    }

    private function analyzeVideo($videoPath)
    {
        $this->info('🎬 ANALYZING FINAL VIDEO: ' . basename($videoPath));
        
        $command = 'ffprobe -v quiet -print_format json -show_format -show_streams "' . $videoPath . '"';
        $output = shell_exec($command);
        
        if ($output) {
            $videoInfo = json_decode($output, true);
            
            if (isset($videoInfo['streams'])) {
                foreach ($videoInfo['streams'] as $stream) {
                    if ($stream['codec_type'] === 'video') {
                        $width = $stream['width'];
                        $height = $stream['height'];
                        $duration = floatval($videoInfo['format']['duration']);
                        
                        $this->info('📊 Video properties:');
                        $this->info('   - Resolution: ' . $width . 'x' . $height);
                        $this->info('   - Duration: ' . round($duration, 2) . 's');
                        $this->info('   - Codec: ' . $stream['codec_name']);
                        
                        // Determine what we actually got
                        if ($width === 640 && $height === 480) {
                            $this->error('❌ RESULT: Got webcam-only video (640x480)');
                            $this->error('   This means dual stream merging failed!');
                        } elseif ($width === 1920 && $height === 1080) {
                            $this->info('✅ RESULT: Got screen resolution video (1920x1080)');
                            $this->info('   This suggests dual stream or screen-only processing worked');
                        } else {
                            $this->warn('⚠️  RESULT: Got unexpected resolution: ' . $width . 'x' . $height);
                        }
                        
                        break;
                    }
                }
            }
        } else {
            $this->error('❌ Failed to analyze video');
        }
    }

    public function __destruct()
    {
        // Cleanup
        if (isset($this->tempDirectory) && file_exists($this->tempDirectory)) {
            $files = glob($this->tempDirectory . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->tempDirectory);
        }
    }
} 