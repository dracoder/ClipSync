<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\VideoMergeService;
use Illuminate\Support\Facades\Log;

class TestDualStreamFix extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'test:dual-stream-fix';

    /**
     * The console command description.
     */
    protected $description = 'Test the dual stream fix to ensure screen overlay only shows when screen sharing was used';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Testing Enhanced Dual Stream Fix - Timeline-Based Video Merging');
        
        // Test 1: Webcam-only (screen sharing never used)
        $this->info('📹 TEST 1: Webcam-only scenario (screen sharing never used during recording)');
        $result1 = $this->testWebcamOnlyScenario();
        $this->displayResult('Webcam-only', $result1);
        
        // Test 2: Screen-only scenario  
        $this->info('🖥️ TEST 2: Screen-only scenario');
        $result2 = $this->testScreenOnlyScenario();
        $this->displayResult('Screen-only', $result2);
        
        // Test 3: Timeline switching (webcam → screen+webcam overlay → webcam)
        $this->info('🎬 TEST 3: Timeline switching scenario (the main fix)');
        $result3 = $this->testTimelineSwitchingScenario();
        $this->displayResult('Timeline switching', $result3);
        
        // Test 4: Simultaneous overlay (both start at same time)
        $this->info('🎥 TEST 4: Simultaneous overlay scenario');
        $result4 = $this->testSimultaneousOverlayScenario();
        $this->displayResult('Simultaneous overlay', $result4);
        
        // Summary
        $this->info('');
        $this->info('📊 ENHANCED TEST SUMMARY:');
        $this->info('✅ All tests validate the new timestamp-based merging logic');
        $this->info('🎯 Key improvement: Webcam overlay only shows when screen sharing was used');
        $this->info('⏱️ Timeline switching now works based on actual recording timestamps');
        
        if ($result1 && $result2 && $result3 && $result4) {
            $this->info('🎉 All enhanced tests passed! The timeline-based fix is working correctly.');
            return 0;
        } else {
            $this->error('❌ Some tests failed. Check the logs for details.');
            return 1;
        }
    }
    
    private function testWebcamOnlyScenario()
    {
        $this->info('');
        $this->info('🎯 TEST 1: Webcam Only Scenario');
        $this->info('Expected: Should process only webcam stream, no overlay');
        
        $metadata = [
            'session_id' => 'test-webcam-only-' . time(),
            'recording_duration' => 5000,
            'screen_sharing_used_during_recording' => false, // CRITICAL: No screen sharing used
            'screen_sharing_sessions' => [],
            'sync_timestamps' => [
                'sessionStart' => time() * 1000,
                'webcamStart' => time() * 1000,
                'screenStart' => null, // No screen recording
                'webcamEnd' => (time() + 5) * 1000,
                'screenEnd' => null,
            ],
            'webcam_stream_info' => [
                'start_time' => time() * 1000,
                'end_time' => (time() + 5) * 1000,
                'chunk_count' => 20,
                'total_size' => 1024000,
                'mime_type' => 'video/mp4'
            ],
            'screen_stream_info' => null, // No screen stream
        ];
        
        $videoMergeService = app(VideoMergeService::class);
        
        // Simulate the scenario determination
        $webcamInfo = ['duration' => 5.0, 'width' => 1280, 'height' => 720];
        $screenInfo = ['duration' => 0, 'width' => 0, 'height' => 0]; // No screen stream
        
        $scenario = $this->callPrivateMethod($videoMergeService, 'determineRecordingScenarioFinal', [$metadata, $webcamInfo, $screenInfo]);
        
        $this->info("Detected scenario: {$scenario['type']} - {$scenario['description']}");
        
        if ($scenario['type'] === 'webcam_only') {
            $this->info('✅ PASS: Correctly identified as webcam-only scenario');
            return true;
        } else {
            $this->error('❌ FAIL: Should have been identified as webcam-only scenario');
            return false;
        }
    }
    
    private function testScreenOnlyScenario()
    {
        $this->info('');
        $this->info('🎯 TEST 2: Screen-only scenario');
        $this->info('Expected: Should process only screen stream, no overlay');
        
        $metadata = [
            'session_id' => 'test-screen-only-' . time(),
            'recording_duration' => 5000,
            'screen_sharing_used_during_recording' => true, // Screen sharing was used
            'screen_sharing_sessions' => [[
                'startTime' => time() * 1000,
                'sessionStart' => time() * 1000,
                'activatedDuringRecording' => true
            ]],
            'sync_timestamps' => [
                'sessionStart' => time() * 1000,
                'webcamStart' => null,   // CRITICAL: No webcam stream
                'screenStart' => time() * 1000,
                'webcamEnd' => null,
                'screenEnd' => (time() + 5) * 1000,
            ],
            'webcam_stream_info' => null, // No webcam stream
            'screen_stream_info' => [
                'start_time' => time() * 1000,
                'end_time' => (time() + 5) * 1000,
                'chunk_count' => 20,
                'total_size' => 2048000,
                'mime_type' => 'video/mp4'
            ],
        ];
        
        $videoMergeService = app(VideoMergeService::class);
        
        // Simulate the scenario determination
        $webcamInfo = ['duration' => 0, 'width' => 0, 'height' => 0]; // CRITICAL: No webcam stream
        $screenInfo = ['duration' => 5.0, 'width' => 1920, 'height' => 1080];
        
        $scenario = $this->callPrivateMethod($videoMergeService, 'determineRecordingScenarioFinal', [$metadata, $webcamInfo, $screenInfo]);
        
        $this->info("Detected scenario: {$scenario['type']} - {$scenario['description']}");
        
        if ($scenario['type'] === 'screen_only') {
            $this->info('✅ PASS: Correctly identified as screen-only scenario');
            return true;
        } else {
            $this->error('❌ FAIL: Should have been identified as screen-only scenario');
            return false;
        }
    }
    
    private function testTimelineSwitchingScenario(): bool
    {
        try {
            $this->info('   Testing timeline switching: webcam starts, then screen sharing activated, then stopped');
            
            // Simulate metadata for timeline switching scenario
            $metadata = [
                'session_id' => time(),
                'recording_duration' => 30000, // 30 seconds
                'screen_sharing_used_during_recording' => true,
                'screen_sharing_sessions' => [[
                    'startTime' => 1000 + 5000, // 5 seconds after session start
                    'sessionStart' => 1000,
                    'activatedDuringRecording' => true
                ]],
                'sync_timestamps' => [
                    'sessionStart' => 1000,
                    'webcamStart' => 1000,
                    'screenStart' => 1000 + 5000, // 5 seconds later
                    'webcamEnd' => 1000 + 30000,
                    'screenEnd' => 1000 + 20000   // Screen stops at 20 seconds
                ],
                'overlay_settings' => [
                    'position' => 'bottom-right',
                    'size' => 20,
                    'visible' => true
                ]
            ];
            
            // Mock webcam info (30 seconds)
            $webcamInfo = [
                'duration' => 30.0,
                'width' => 1280,
                'height' => 720,
                'has_audio' => true
            ];
            
            // Mock screen info (15 seconds, from 5s to 20s)
            $screenInfo = [
                'duration' => 15.0, 
                'width' => 1920,
                'height' => 1080,
                'has_audio' => true
            ];
            
            $videoMergeService = new VideoMergeService();
            $reflection = new \ReflectionClass($videoMergeService);
            
            // Test scenario determination
            $method = $reflection->getMethod('determineRecordingScenarioFinal');
            $method->setAccessible(true);
            $scenario = $method->invoke($videoMergeService, $metadata, $webcamInfo, $screenInfo);
            
            $this->info('   📊 Detected scenario: ' . $scenario['type']);
            $this->info('   📝 Description: ' . $scenario['description']);
            $this->info('   🔢 Segments count: ' . count($scenario['segments']));
            
            // Validate timeline switching scenario
            if ($scenario['type'] !== 'timeline_switching') {
                $this->error('   ❌ Expected timeline_switching, got: ' . $scenario['type']);
                return false;
            }
            
            // Validate segments
            $segments = $scenario['segments'];
            if (count($segments) !== 3) {
                $this->error('   ❌ Expected 3 segments, got: ' . count($segments));
                return false;
            }
            
            // Expected timeline: webcam(0-5s) → overlay(5-20s) → webcam(20-30s)
            $expectedSegments = [
                ['type' => 'webcam_only', 'start_time' => 0, 'duration' => 5.0],
                ['type' => 'screen_overlay', 'start_time' => 5.0, 'duration' => 15.0], 
                ['type' => 'webcam_only', 'start_time' => 20.0, 'duration' => 10.0]
            ];
            
            foreach ($expectedSegments as $index => $expected) {
                $actual = $segments[$index];
                if ($actual['type'] !== $expected['type'] || 
                    abs($actual['start_time'] - $expected['start_time']) > 0.1 ||
                    abs($actual['duration'] - $expected['duration']) > 0.1) {
                    
                    $this->error("   ❌ Segment {$index} mismatch:");
                    $this->error("      Expected: {$expected['type']} at {$expected['start_time']}s for {$expected['duration']}s");
                    $this->error("      Actual: {$actual['type']} at {$actual['start_time']}s for {$actual['duration']}s");
                    return false;
                }
            }
            
            $this->info('   ✅ Timeline switching scenario correctly detected and segmented');
            $this->info('   📹 Segment 1: Webcam only (0-5s)');
            $this->info('   🎬 Segment 2: Screen with webcam overlay (5-20s)');
            $this->info('   📹 Segment 3: Webcam only (20-30s)');
            
            return true;
            
        } catch (\Exception $e) {
            $this->error('   ❌ Timeline switching test failed: ' . $e->getMessage());
            return false;
        }
    }
    
    private function testSimultaneousOverlayScenario()
    {
        $this->info('');
        $this->info('🎯 TEST 4: Simultaneous overlay scenario');
        $this->info('Expected: Should process simultaneous streams with overlay');
        
        $metadata = [
            'session_id' => 'test-simultaneous-overlay-' . time(),
            'recording_duration' => 5000,
            'screen_sharing_used_during_recording' => true, // CRITICAL: Screen sharing WAS used
            'screen_sharing_sessions' => [[
                'startTime' => time() * 1000,      // Started immediately
                'sessionStart' => time() * 1000,
                'activatedDuringRecording' => true
            ]],
            'sync_timestamps' => [
                'sessionStart' => time() * 1000,
                'webcamStart' => time() * 1000,
                'screenStart' => time() * 1000,    // Started at the same time as webcam
                'webcamEnd' => (time() + 5) * 1000,
                'screenEnd' => (time() + 5) * 1000,
            ],
            'webcam_stream_info' => [
                'start_time' => time() * 1000,
                'end_time' => (time() + 5) * 1000,
                'chunk_count' => 20,
                'total_size' => 1024000,
                'mime_type' => 'video/mp4'
            ],
            'screen_stream_info' => [
                'start_time' => time() * 1000,
                'end_time' => (time() + 5) * 1000,
                'chunk_count' => 18,
                'total_size' => 2048000,
                'mime_type' => 'video/mp4'
            ],
        ];
        
        $videoMergeService = app(VideoMergeService::class);
        
        // Simulate both streams existing and starting simultaneously
        $webcamInfo = ['duration' => 5.0, 'width' => 1280, 'height' => 720];
        $screenInfo = ['duration' => 5.0, 'width' => 1920, 'height' => 1080];
        
        $scenario = $this->callPrivateMethod($videoMergeService, 'determineRecordingScenarioFinal', [$metadata, $webcamInfo, $screenInfo]);
        
        $this->info("Detected scenario: {$scenario['type']} - {$scenario['description']}");
        
        // With our logic, simultaneous start should be detected as simultaneous_overlay or single segment timeline
        if ($scenario['type'] === 'simultaneous_overlay' || 
            ($scenario['type'] === 'timeline_switching' && count($scenario['segments']) === 1)) {
            $this->info('✅ PASS: Correctly identified as simultaneous overlay scenario');
            return true;
        } else {
            $this->error('❌ FAIL: Should have been identified as simultaneous overlay scenario');
            $this->error("   Got: {$scenario['type']} with " . count($scenario['segments']) . " segments");
            return false;
        }
    }
    
    private function displayResult(string $testName, bool $result): void
    {
        if ($result) {
            $this->info("   ✅ {$testName} test PASSED");
        } else {
            $this->error("   ❌ {$testName} test FAILED");
        }
        $this->info('');
    }
    
    private function callPrivateMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }
}

