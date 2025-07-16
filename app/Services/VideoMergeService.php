<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Support\Facades\Storage;

class VideoMergeService
{
    /**
     * Enhanced merge streams with FIXED timeline-aware conditional merging
     * 
     * This method handles conditional merging based on actual recording timeline:
     * - Show ONLY webcam when screen sharing was never active
     * - Show screen with webcam overlay when screen sharing was active
     * - Use smart detection to determine the actual recording scenario
     * 
     * @param string $webcamPath
     * @param string $screenPath  
     * @param array $metadata
     * @return string
     * @throws Exception
     */
    public function mergeStreams(string $webcamPath, string $screenPath, array $metadata = []): string
    {
        Log::info('Starting CORRECTED timeline-aware video merge', [
            'webcam_path' => basename($webcamPath),
            'screen_path' => basename($screenPath),
            'metadata_keys' => array_keys($metadata),
            'has_sync_timestamps' => isset($metadata['sync_timestamps'])
        ]);

        $ffmpegBinary = config('ffmpeg.ffmpeg.binaries', 'ffmpeg');
        $outputPath = $this->generateOutputPath();

        // Validate inputs
        $this->validateInputFiles($webcamPath, $screenPath);

        // Analyze both video streams for comprehensive information
        $webcamInfo = $this->analyzeVideoStream($webcamPath);
        $screenInfo = $this->analyzeVideoStream($screenPath);

        Log::info('Video stream analysis completed', [
            'webcam_info' => $webcamInfo,
            'screen_info' => $screenInfo
        ]);

        // CRITICAL: Validate streams for potential freezing issues
        $this->validateStreamsForFreezingIssues($webcamInfo, $screenInfo);

        // CORRECTED: Determine the actual recording scenario with proper logic
        $recordingScenario = $this->determineRecordingScenarioFinal($metadata, $webcamInfo, $screenInfo);
        
        Log::info('Recording scenario determined', $recordingScenario);

        // Handle merging based on the actual scenario with CORRECTED logic
        switch ($recordingScenario['type']) {
            case 'webcam_only':
                return $this->processWebcamOnlyScenario($ffmpegBinary, $webcamPath, $outputPath, $webcamInfo);
                
            case 'screen_only':
                return $this->processScreenOnlyScenario($ffmpegBinary, $screenPath, $outputPath, $screenInfo);
                
            case 'timeline_switching':
                return $this->processTimelineSwitchingScenario(
                    $ffmpegBinary, 
                    $webcamPath, 
                    $screenPath, 
                    $outputPath, 
                    $webcamInfo, 
                    $screenInfo, 
                    $recordingScenario,
                    $metadata
                );
                
            case 'simultaneous_overlay':
            default:
                return $this->processSimultaneousOverlayScenarioFinal(
                    $ffmpegBinary, 
                    $webcamPath, 
                    $screenPath, 
                    $outputPath, 
                    $webcamInfo, 
                    $screenInfo, 
                    $metadata
                );
        }
    }

    /**
     * FINAL CORRECTED: Determine the actual recording scenario based on metadata and stream analysis
     * 
     * @param array $metadata
     * @param array $webcamInfo
     * @param array $screenInfo
     * @return array
     */
    private function determineRecordingScenarioFinal(array $metadata, array $webcamInfo, array $screenInfo): array
    {
        $scenario = [
            'type' => 'webcam_only', // Default to webcam only 
            'description' => 'Webcam only recording',
            'segments' => [],
            'total_duration' => max($webcamInfo['duration'] ?? 0, $screenInfo['duration'] ?? 0)
        ];

        // Check if we have basic stream info
        $hasWebcam = !empty($webcamInfo['duration']) && $webcamInfo['duration'] > 0;
        $hasScreen = !empty($screenInfo['duration']) && $screenInfo['duration'] > 0;
        
        Log::info('🔍 ENHANCED SCENARIO DETECTION:', [
            'has_webcam' => $hasWebcam,
            'has_screen' => $hasScreen,
            'webcam_duration' => $webcamInfo['duration'] ?? 0,
            'screen_duration' => $screenInfo['duration'] ?? 0,
            'screen_sharing_used_metadata' => $metadata['screen_sharing_used_during_recording'] ?? false,
            'screen_sharing_sessions' => count($metadata['screen_sharing_sessions'] ?? [])
        ]);

        // CRITICAL FIX: Check if screen sharing was actually used during recording
        $screenSharingUsed = $metadata['screen_sharing_used_during_recording'] ?? false;
        $screenSharingSessions = $metadata['screen_sharing_sessions'] ?? [];
        
        if (!$hasWebcam && !$hasScreen) {
            Log::warning('No valid streams found for scenario detection');
            throw new Exception('No valid video streams found');
        }
        
        if (!$hasWebcam && $hasScreen && $screenSharingUsed) {
            // Screen only scenario - no webcam stream but screen was recorded
            Log::info('🖥️ DETECTED: Screen-only scenario');
            $scenario = [
                'type' => 'screen_only',
                'description' => 'Screen recording only',
                'segments' => [[
                    'type' => 'screen_only',
                    'start_time' => 0,
                    'duration' => $screenInfo['duration'],
                    'description' => 'Full screen recording'
                ]],
                'total_duration' => $screenInfo['duration']
            ];
        } elseif (!$hasScreen) {
            // Webcam only scenario - no screen stream available
            Log::info('📹 DETECTED: Webcam-only scenario (no screen stream available)');
            $scenario = [
                'type' => 'webcam_only',
                'description' => 'Webcam only recording (no screen stream)',
                'segments' => [[
                    'type' => 'webcam_only',
                    'start_time' => 0,
                    'duration' => $webcamInfo['duration'],
                    'description' => 'Full webcam recording'
                ]],
                'total_duration' => $webcamInfo['duration']
            ];
        } elseif (!$screenSharingUsed) {
            // Special case: Both streams exist but screen sharing was never used during recording
            // This means user uploaded screen content but never actually used it during recording
            Log::info('📹 DETECTED: Webcam-only scenario (screen sharing not used during recording)');
            $scenario = [
                'type' => 'webcam_only',
                'description' => 'Webcam only recording (screen sharing not used)',
                'segments' => [[
                    'type' => 'webcam_only',
                    'start_time' => 0,
                    'duration' => $webcamInfo['duration'],
                    'description' => 'Full webcam recording'
                ]],
                'total_duration' => $webcamInfo['duration']
            ];
        } else {
            // TIMESTAMP-BASED SCENARIO: Webcam + Screen with timeline switching
            Log::info('🎬 DETECTED: Timeline switching scenario - analyzing timestamps');
            $scenario = $this->createTimelineBasedScenario($metadata, $webcamInfo, $screenInfo);
        }
        
        Log::info('✅ FINAL SCENARIO DETERMINED:', [
            'type' => $scenario['type'],
            'description' => $scenario['description'],
            'segments_count' => count($scenario['segments']),
            'total_duration' => $scenario['total_duration']
        ]);
        
            return $scenario;
        }

    /**
     * Create timeline-based scenario with proper timestamp analysis
     * 
     * @param array $metadata
     * @param array $webcamInfo  
     * @param array $screenInfo
     * @return array
     */
    private function createTimelineBasedScenario(array $metadata, array $webcamInfo, array $screenInfo): array
    {
        $syncTimestamps = $metadata['sync_timestamps'] ?? [];
        $screenSharingSessions = $metadata['screen_sharing_sessions'] ?? [];
        
        Log::info('🕒 CREATING TIMELINE-BASED SCENARIO:', [
            'sync_timestamps' => $syncTimestamps,
            'screen_sharing_sessions' => $screenSharingSessions,
            'webcam_duration' => $webcamInfo['duration'],
            'screen_duration' => $screenInfo['duration']
        ]);
        
        // Get recording session start time (baseline)
        $sessionStart = $syncTimestamps['sessionStart'] ?? $syncTimestamps['webcamStart'] ?? 0;
        $webcamStart = $syncTimestamps['webcamStart'] ?? $sessionStart;
        $screenStart = $syncTimestamps['screenStart'] ?? null;
        
        // Calculate durations in seconds (convert from milliseconds if needed)
        $webcamDuration = $webcamInfo['duration']; // Already in seconds from analyzeVideoStream
        $screenDuration = $screenInfo['duration']; // Already in seconds from analyzeVideoStream
        $totalDuration = max($webcamDuration, $screenDuration);
        
        $segments = [];
        
        if (!$screenStart || empty($screenSharingSessions)) {
            // No screen sharing timestamps - fallback to webcam only
            Log::info('⚠️ No screen sharing timestamps found, falling back to webcam-only');
            $segments[] = [
                'type' => 'webcam_only',
                'start_time' => 0,
                'duration' => $webcamDuration,
                'description' => 'Full webcam recording (no screen sharing timestamps)'
            ];
            
            return [
                'type' => 'webcam_only',
                'description' => 'Webcam only (no screen timestamps)',
                'segments' => $segments,
                'total_duration' => $webcamDuration
            ];
        }
        
        // Calculate relative timing (convert milliseconds to seconds)
        $screenStartRelative = ($screenStart - $sessionStart) / 1000.0; // Convert to seconds
        $screenStartRelative = max(0, $screenStartRelative); // Ensure non-negative
        
        Log::info('📊 TIMELINE ANALYSIS:', [
            'session_start' => $sessionStart,
            'webcam_start' => $webcamStart,
            'screen_start' => $screenStart,
            'screen_start_relative' => $screenStartRelative,
            'webcam_duration_sec' => $webcamDuration,
            'screen_duration_sec' => $screenDuration,
            'total_duration_sec' => $totalDuration
        ]);
        
        // TIMELINE SCENARIO 1: Screen sharing starts after webcam (most common)
        if ($screenStartRelative > 0.5) { // At least 0.5 seconds after webcam start
            Log::info('📹➡️🖥️ TIMELINE: Webcam first, then screen sharing overlay');
            
            // Segment 1: Webcam only (before screen sharing)
            $webcamOnlyDuration = min($screenStartRelative, $webcamDuration);
            if ($webcamOnlyDuration > 0.1) { // At least 0.1 seconds
                $segments[] = [
                    'type' => 'webcam_only',
                    'start_time' => 0,
                    'duration' => $webcamOnlyDuration,
                    'description' => 'Webcam before screen sharing'
                ];
            }
            
            // Segment 2: Screen with webcam overlay (during screen sharing)
            $overlayStartTime = $webcamOnlyDuration;
            $overlayDuration = min($screenDuration, $webcamDuration - $overlayStartTime);
            if ($overlayDuration > 0.1) { // At least 0.1 seconds
                $segments[] = [
                    'type' => 'screen_overlay',
                    'start_time' => $overlayStartTime,
                    'duration' => $overlayDuration,
                    'description' => 'Screen with webcam overlay'
                ];
            }
            
            // Segment 3: Webcam only again (if webcam continues after screen ends)
            $webcamAfterScreen = $webcamDuration - ($overlayStartTime + $overlayDuration);
            if ($webcamAfterScreen > 0.1) { // At least 0.1 seconds
                $segments[] = [
                    'type' => 'webcam_only',
                    'start_time' => $overlayStartTime + $overlayDuration,
                    'duration' => $webcamAfterScreen,
                    'description' => 'Webcam after screen sharing ended'
                ];
            }
            
            return [
                'type' => 'timeline_switching',
                'description' => 'Webcam → Screen+Webcam → Webcam timeline',
                'segments' => $segments,
                'total_duration' => $totalDuration
            ];
            
        } else {
            // TIMELINE SCENARIO 2: Screen sharing starts immediately or simultaneously
            Log::info('🖥️📹 TIMELINE: Simultaneous or immediate screen sharing');
            
            $overlayDuration = min($screenDuration, $webcamDuration);
            if ($overlayDuration > 0.1) {
                $segments[] = [
                    'type' => 'screen_overlay',
                    'start_time' => 0,
                    'duration' => $overlayDuration,
                    'description' => 'Screen with webcam overlay (simultaneous start)'
                ];
            }
            
            // Add webcam-only segment if webcam continues after screen
            $webcamAfterScreen = $webcamDuration - $overlayDuration;
            if ($webcamAfterScreen > 0.1) {
                $segments[] = [
                    'type' => 'webcam_only',
                    'start_time' => $overlayDuration,
                    'duration' => $webcamAfterScreen,
                    'description' => 'Webcam only after screen sharing'
                ];
            }
            
            return [
                'type' => count($segments) > 1 ? 'timeline_switching' : 'simultaneous_overlay',
                'description' => count($segments) > 1 ? 'Screen+Webcam → Webcam timeline' : 'Simultaneous screen and webcam',
                'segments' => $segments,
                'total_duration' => $totalDuration
            ];
        }
    }

    /**
     * Process webcam-only recording scenario
     * 
     * @param string $ffmpegBinary
     * @param string $webcamPath
     * @param string $outputPath
     * @param array $webcamInfo
     * @return string
     */
    private function processWebcamOnlyScenario(string $ffmpegBinary, string $webcamPath, string $outputPath, array $webcamInfo): string
    {
        Log::info('Processing webcam-only scenario');
        
        // Simple conversion/optimization of webcam stream
        $command = sprintf(
            '%s -i "%s" -c:v libx264 -c:a aac -preset medium -crf 23 -movflags +faststart "%s"',
            $ffmpegBinary,
            $webcamPath,
            $outputPath
        );
        
        return $this->executeCommand($command, $outputPath);
    }

    /**
     * Process screen-only recording scenario
     * 
     * @param string $ffmpegBinary
     * @param string $screenPath
     * @param string $outputPath
     * @param array $screenInfo
     * @return string
     */
    private function processScreenOnlyScenario(string $ffmpegBinary, string $screenPath, string $outputPath, array $screenInfo): string
    {
        Log::info('Processing screen-only scenario');
        
        // Simple conversion/optimization of screen stream
        $command = sprintf(
            '%s -i "%s" -c:v libx264 -c:a aac -preset medium -crf 23 -movflags +faststart "%s"',
            $ffmpegBinary,
            $screenPath,
            $outputPath
        );
        
        return $this->executeCommand($command, $outputPath);
    }

    /**
     * Process timeline switching scenario (webcam only -> screen overlay -> webcam only)
     * 
     * @param string $ffmpegBinary
     * @param string $webcamPath
     * @param string $screenPath
     * @param string $outputPath
     * @param array $webcamInfo
     * @param array $screenInfo
     * @param array $scenario
     * @param array $metadata
     * @return string
     */
    private function processTimelineSwitchingScenario(
        string $ffmpegBinary,
        string $webcamPath,
        string $screenPath,
        string $outputPath,
        array $webcamInfo,
        array $screenInfo,
        array $scenario,
        array $metadata
    ): string {
        Log::info('🎬 PROCESSING TIMELINE SWITCHING SCENARIO with segments', [
            'segments_count' => count($scenario['segments']),
            'total_duration' => $scenario['total_duration']
        ]);
        
        $segments = $scenario['segments'];
        
        if (empty($segments)) {
            Log::warning('No segments found, falling back to simultaneous overlay');
        return $this->processSimultaneousOverlayScenarioFinal(
            $ffmpegBinary, 
            $webcamPath, 
            $screenPath, 
            $outputPath, 
            $webcamInfo, 
            $screenInfo, 
            $metadata
            );
        }
        
        // Check if we only have one segment (simple case)
        if (count($segments) === 1) {
            $segment = $segments[0];
            Log::info('Single segment timeline processing', ['segment_type' => $segment['type']]);
            
            switch ($segment['type']) {
                case 'webcam_only':
                    return $this->processWebcamOnlyScenario($ffmpegBinary, $webcamPath, $outputPath, $webcamInfo);
                case 'screen_only':
                    return $this->processScreenOnlyScenario($ffmpegBinary, $screenPath, $outputPath, $screenInfo);
                case 'screen_overlay':
                    return $this->processSimultaneousOverlayScenarioFinal(
                        $ffmpegBinary, $webcamPath, $screenPath, $outputPath, 
                        $webcamInfo, $screenInfo, $metadata
                    );
            }
        }
        
        // Multi-segment timeline processing
        return $this->processMultiSegmentTimeline(
            $ffmpegBinary, $webcamPath, $screenPath, $outputPath,
            $webcamInfo, $screenInfo, $segments, $metadata
        );
    }
    
    /**
     * Process multi-segment timeline with proper video concatenation
     * 
     * @param string $ffmpegBinary
     * @param string $webcamPath
     * @param string $screenPath
     * @param string $outputPath
     * @param array $webcamInfo
     * @param array $screenInfo
     * @param array $segments
     * @param array $metadata
     * @return string
     */
    private function processMultiSegmentTimeline(
        string $ffmpegBinary,
        string $webcamPath,
        string $screenPath,
        string $outputPath,
        array $webcamInfo,
        array $screenInfo,
        array $segments,
        array $metadata
    ): string {
        Log::info('🎬 PROCESSING MULTI-SEGMENT TIMELINE:', [
            'segments_count' => count($segments),
            'segments' => $segments
        ]);
        
        $tempSegmentFiles = [];
        $tempDir = storage_path('app/temp/video_segments_' . uniqid());
        
        try {
            // Create temp directory
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            
            // Process each segment
            foreach ($segments as $index => $segment) {
                $segmentPath = $tempDir . "/segment_{$index}.mp4";
                $tempSegmentFiles[] = $segmentPath;
                
                Log::info("Processing segment {$index}: {$segment['type']}", [
                    'start_time' => $segment['start_time'],
                    'duration' => $segment['duration']
                ]);
                
                switch ($segment['type']) {
                    case 'webcam_only':
                        $this->extractWebcamSegment(
                            $ffmpegBinary, $webcamPath, $segmentPath,
                            $segment['start_time'], $segment['duration'], $webcamInfo
                        );
                        break;
                        
                    case 'screen_only':
                        $this->extractScreenSegment(
                            $ffmpegBinary, $screenPath, $segmentPath,
                            $segment['start_time'], $segment['duration'], $screenInfo
                        );
                        break;
                        
                    case 'screen_overlay':
                        $this->createOverlaySegment(
                            $ffmpegBinary, $webcamPath, $screenPath, $segmentPath,
                            $segment['start_time'], $segment['duration'], 
                            $webcamInfo, $screenInfo, $metadata
                        );
                        break;
                        
                    default:
                        throw new Exception("Unknown segment type: {$segment['type']}");
                }
                
                if (!file_exists($segmentPath)) {
                    throw new Exception("Failed to create segment {$index}: {$segmentPath}");
                }
                
                Log::info("Segment {$index} created successfully", [
                    'path' => $segmentPath,
                    'size' => filesize($segmentPath)
                ]);
            }
            
            // Concatenate all segments
            $this->concatenateSegments($ffmpegBinary, $tempSegmentFiles, $outputPath);
            
            return $outputPath;
            
        } finally {
            // Cleanup temporary files
            $this->cleanupTempFiles($tempSegmentFiles);
            if (file_exists($tempDir)) {
                rmdir($tempDir);
            }
        }
    }
    
    /**
     * Extract webcam segment with proper timing
     */
    private function extractWebcamSegment(
        string $ffmpegBinary, 
        string $webcamPath, 
        string $outputPath,
        float $startTime, 
        float $duration,
        array $webcamInfo
    ): void {
        Log::info("Extracting webcam segment", [
            'start' => $startTime,
            'duration' => $duration,
            'input' => $webcamPath,
            'output' => $outputPath
        ]);
        
        $command = sprintf(
            '%s -i "%s" -ss %.3f -t %.3f -c:v libx264 -c:a aac -preset medium -crf 23 -avoid_negative_ts make_zero "%s"',
            $ffmpegBinary,
            $webcamPath,
            $startTime,
            $duration,
            $outputPath
        );
        
        $this->executeCommand($command, $outputPath);
    }
    
    /**
     * Extract screen segment with proper timing
     */
    private function extractScreenSegment(
        string $ffmpegBinary,
        string $screenPath,
        string $outputPath,
        float $startTime,
        float $duration,
        array $screenInfo
    ): void {
        Log::info("Extracting screen segment", [
            'start' => $startTime,
            'duration' => $duration,
            'input' => $screenPath,
            'output' => $outputPath
        ]);
        
        $command = sprintf(
            '%s -i "%s" -ss %.3f -t %.3f -c:v libx264 -c:a aac -preset medium -crf 23 -avoid_negative_ts make_zero "%s"',
            $ffmpegBinary,
            $screenPath,
            $startTime,
            $duration,
            $outputPath
        );
        
        $this->executeCommand($command, $outputPath);
    }
    
    /**
     * Create overlay segment (screen with webcam overlay)
     */
    private function createOverlaySegment(
        string $ffmpegBinary,
        string $webcamPath,
        string $screenPath,
        string $outputPath,
        float $startTime,
        float $duration,
        array $webcamInfo,
        array $screenInfo,
        array $metadata
    ): void {
        Log::info("Creating overlay segment", [
            'start' => $startTime,
            'duration' => $duration,
            'webcam' => $webcamPath,
            'screen' => $screenPath,
            'output' => $outputPath
        ]);
        
        // Calculate overlay position and size
        $overlayPosition = $this->calculateOverlayPosition($metadata, $screenInfo['width'], $screenInfo['height']);
        $webcamSize = $this->calculateWebcamOverlaySize($metadata, $screenInfo['width'], $screenInfo['height']);
        
        $overlayX = $overlayPosition['x'];
        $overlayY = $overlayPosition['y'];
        $webcamWidth = $webcamSize['width'];
        $webcamHeight = $webcamSize['height'];
        
        // CRITICAL FIX: Enhanced overlay filter with EXPLICIT screen canvas size
        // This ensures output is screen dimensions, not webcam dimensions
        $videoFilter = sprintf(
            "[0:v]scale=%d:%d,fps=30[screen_base];[1:v]scale=%d:%d:force_original_aspect_ratio=decrease,pad=%d:%d:(ow-iw)/2:(oh-ih)/2:color=black,fps=30[webcam_scaled];[screen_base][webcam_scaled]overlay=%d:%d:format=auto:shortest=0,setpts=PTS-STARTPTS[outv]",
            $screenInfo['width'],  // CRITICAL: Set output canvas to screen width
            $screenInfo['height'], // CRITICAL: Set output canvas to screen height
            $webcamWidth,
            $webcamHeight,
            $webcamWidth,
            $webcamHeight,
            $overlayX,
            $overlayY
        );
        
        // Audio mixing
        $hasScreenAudio = $screenInfo['has_audio'] ?? false;
        $hasWebcamAudio = $webcamInfo['has_audio'] ?? false;
        
        $audioFilter = "";
        if ($hasScreenAudio && $hasWebcamAudio) {
            $audioFilter = "[0:a][1:a]amix=inputs=2:duration=longest:dropout_transition=2,volume=1.2[outa]";
        } elseif ($hasScreenAudio) {
            $audioFilter = "[0:a]aresample=44100,volume=1.0[outa]";
        } elseif ($hasWebcamAudio) {
            $audioFilter = "[1:a]aresample=44100,volume=1.0[outa]";
        }
        
        // ENHANCED: Build command with enhanced encoding options to prevent freezing
        Log::info('ENHANCED OVERLAY SEGMENT DEBUG:', [
            'start_time' => $startTime,
            'duration' => $duration,
            'video_filter' => $videoFilter,
            'audio_filter' => $audioFilter ?: 'no audio',
            'screen_path' => basename($screenPath),
            'webcam_path' => basename($webcamPath)
        ]);
        
        if ($audioFilter) {
            $command = sprintf(
                '%s -ss %.3f -i "%s" -ss %.3f -i "%s" -t %.3f -filter_complex "%s;%s" -map "[outv]" -map "[outa]" -c:v libx264 -c:a aac -preset fast -crf 20 -g 30 -keyint_min 30 -sc_threshold 0 -avoid_negative_ts make_zero "%s"',
                $ffmpegBinary,
                $startTime, $screenPath,  // Screen input with start time
                $startTime, $webcamPath,  // Webcam input with start time  
                $duration,                // Duration
                $videoFilter,             // Video filter
                $audioFilter,             // Audio filter
                $outputPath
            );
        } else {
            $command = sprintf(
                '%s -ss %.3f -i "%s" -ss %.3f -i "%s" -t %.3f -filter_complex "%s" -map "[outv]" -c:v libx264 -preset fast -crf 20 -g 30 -keyint_min 30 -sc_threshold 0 -avoid_negative_ts make_zero "%s"',
                $ffmpegBinary,
                $startTime, $screenPath,  // Screen input with start time
                $startTime, $webcamPath,  // Webcam input with start time
                $duration,                // Duration
                $videoFilter,             // Video filter
                $outputPath
            );
        }
        
        $this->executeCommand($command, $outputPath);
    }
    
    /**
     * Concatenate video segments
     */
    private function concatenateSegments(string $ffmpegBinary, array $segmentFiles, string $outputPath): void
    {
        Log::info("Concatenating segments", [
            'segment_count' => count($segmentFiles),
            'output' => $outputPath
        ]);
        
        // Create concat list file
        $concatListPath = storage_path('app/temp/concat_list_' . uniqid() . '.txt');
        $concatContent = '';
        
        foreach ($segmentFiles as $segmentFile) {
            $concatContent .= "file '" . $segmentFile . "'\n";
        }
        
        file_put_contents($concatListPath, $concatContent);
        
        try {
            $command = sprintf(
                '%s -f concat -safe 0 -i "%s" -c copy -avoid_negative_ts make_zero "%s"',
                $ffmpegBinary,
                $concatListPath,
                $outputPath
            );
            
            $this->executeCommand($command, $outputPath);
            
        } finally {
            if (file_exists($concatListPath)) {
                unlink($concatListPath);
            }
        }
    }

    /**
     * FINAL CORRECTED: Process simultaneous overlay scenario (screen with webcam overlay)
     * 
     * @param string $ffmpegBinary
     * @param string $webcamPath
     * @param string $screenPath
     * @param string $outputPath
     * @param array $webcamInfo
     * @param array $screenInfo
     * @param array $metadata
     * @return string
     */
    private function processSimultaneousOverlayScenarioFinal(
        string $ffmpegBinary,
        string $webcamPath,
        string $screenPath,
        string $outputPath,
        array $webcamInfo,
        array $screenInfo,
        array $metadata
    ): string {
        Log::info('🎬 PROCESSING FINAL SIMULTANEOUS OVERLAY SCENARIO - This should create screen with webcam overlay!');
        
        // Calculate overlay position and size
        $overlayPosition = $this->calculateOverlayPosition($metadata, $screenInfo['width'], $screenInfo['height']);
        $webcamSize = $this->calculateWebcamOverlaySize($metadata, $screenInfo['width'], $screenInfo['height']);
        
        // Build the FINAL CORRECTED FFmpeg command with proper input order
        $command = $this->buildFinalMergeCommand(
            $ffmpegBinary, 
            $screenPath,  // Screen is input 0 (base layer) - CORRECT ORDER
            $webcamPath,  // Webcam is input 1 (overlay) - CORRECT ORDER
            $outputPath, 
            $overlayPosition, 
            $webcamSize,
            $screenInfo,
            $webcamInfo,
            $metadata
        );
        
        return $this->executeCommand($command, $outputPath);
    }

    /**
     * FINAL CORRECTED: Build FFmpeg merge command with CORRECTED input order and filter logic
     * 
     * @param string $ffmpegBinary
     * @param string $screenPath - Input 0 (base layer)
     * @param string $webcamPath - Input 1 (overlay)
     * @param string $outputPath
     * @param array $overlayPosition
     * @param array $webcamSize
     * @param array $screenInfo
     * @param array $webcamInfo
     * @param array $metadata
     * @return string
     */
    private function buildFinalMergeCommand(
        string $ffmpegBinary,
        string $screenPath,   // Input 0: Screen (base layer)
        string $webcamPath,   // Input 1: Webcam (overlay)
        string $outputPath,
        array $overlayPosition,
        array $webcamSize,
        array $screenInfo,
        array $webcamInfo,
        array $metadata
    ): string {
        Log::info('Building FINAL CORRECTED FFmpeg merge command', [
            'screen_dimensions' => $screenInfo['width'] . 'x' . $screenInfo['height'],
            'webcam_dimensions' => $webcamInfo['width'] . 'x' . $webcamInfo['height'],
            'overlay_position' => $overlayPosition,
            'webcam_size' => $webcamSize,
            'input_order' => 'screen=0, webcam=1 (CORRECTED)'
        ]);

        // Calculate overlay position string
        $overlayX = $overlayPosition['x'];
        $overlayY = $overlayPosition['y'];
        $webcamWidth = $webcamSize['width'];
        $webcamHeight = $webcamSize['height'];

        // CRITICAL FIX: Build the video filter complex with EXPLICIT screen dimensions as output canvas
        // Input 0: Screen video (base layer) - CRITICAL: This must be the screen!
        // Input 1: Webcam video (overlay) - CRITICAL: This must be the webcam!
        // The output MUST have SCREEN dimensions, not webcam dimensions!
        
        // Step 1: Prepare screen as base canvas with explicit size
        // Step 2: Scale webcam to overlay size
        // Step 3: Overlay webcam on screen canvas at specified position
        $videoFilter = sprintf(
            "[0:v]scale=%d:%d,fps=30[screen_base];[1:v]scale=%d:%d:force_original_aspect_ratio=decrease,pad=%d:%d:(ow-iw)/2:(oh-ih)/2:color=black,fps=30[webcam_scaled];[screen_base][webcam_scaled]overlay=%d:%d:format=auto:shortest=0,setpts=PTS-STARTPTS[outv]",
            $screenInfo['width'],  // CRITICAL: Set output canvas to screen width
            $screenInfo['height'], // CRITICAL: Set output canvas to screen height
            $webcamWidth,
            $webcamHeight,
            $webcamWidth, 
            $webcamHeight,
            $overlayX,
            $overlayY
        );
        
        Log::info('🎥 FFMPEG FILTER BREAKDOWN (FIXED):', [
            'step_1' => 'Set screen as base canvas at ' . $screenInfo['width'] . 'x' . $screenInfo['height'],
            'step_2' => 'Scale webcam to overlay size ' . $webcamWidth . 'x' . $webcamHeight,
            'step_3' => 'Overlay scaled webcam on screen canvas at position ' . $overlayX . ',' . $overlayY,
            'critical_fix' => 'Output canvas is now explicitly set to screen dimensions',
            'expected_output_size' => $screenInfo['width'] . 'x' . $screenInfo['height'] . ' (GUARANTEED screen dimensions)',
            'filter' => $videoFilter
        ]);

        // ENHANCED: Build audio filter with improved mixing
        $audioFilter = "";
        $hasScreenAudio = $screenInfo['has_audio'] ?? false;
        $hasWebcamAudio = $webcamInfo['has_audio'] ?? false;
        
        if ($hasScreenAudio && $hasWebcamAudio) {
            // Mix both audio streams with enhanced levels and normalization
            $audioFilter = "[0:a][1:a]amix=inputs=2:duration=longest:dropout_transition=2,volume=1.2[outa]";
        } elseif ($hasScreenAudio) {
            // Use only screen audio with proper mapping
            $audioFilter = "[0:a]aresample=44100,volume=1.0[outa]";
        } elseif ($hasWebcamAudio) {
            // Use only webcam audio with proper mapping
            $audioFilter = "[1:a]aresample=44100,volume=1.0[outa]";
        }

        // Build the complete command with enhanced encoding parameters
        if ($audioFilter) {
            // Both video and audio filters with optimized encoding
            $command = sprintf(
                '%s -i "%s" -i "%s" -filter_complex "%s;%s" -map "[outv]" -map "[outa]" -c:v libx264 -c:a aac -preset medium -crf 23 -profile:v main -level 4.0 -movflags +faststart -avoid_negative_ts make_zero "%s"',
                $ffmpegBinary,
                $screenPath,   // Input 0: Screen (base layer)
                $webcamPath,   // Input 1: Webcam (overlay)
                $videoFilter,  // Video filter with CORRECTED input order
                $audioFilter,  // Audio filter
                $outputPath
            );
        } else {
            // Video filter only (no audio) with optimized encoding
            $command = sprintf(
                '%s -i "%s" -i "%s" -filter_complex "%s" -map "[outv]" -c:v libx264 -preset medium -crf 23 -profile:v main -level 4.0 -movflags +faststart -avoid_negative_ts make_zero "%s"',
                $ffmpegBinary,
                $screenPath,   // Input 0: Screen (base layer)
                $webcamPath,   // Input 1: Webcam (overlay)
                $videoFilter,  // Video filter with CORRECTED input order
                $outputPath
            );
        }

        Log::info('FINAL CORRECTED FFmpeg command built', [
            'command_preview' => substr($command, 0, 250) . '...',
            'has_audio_filter' => !empty($audioFilter),
            'screen_first' => true,
            'webcam_second' => true,
            'video_filter' => $videoFilter,
            'audio_filter' => $audioFilter ?: 'none'
        ]);

        return $command;
    }

    /**
     * Calculate timeline segments for switching scenarios
     * 
     * @param float $webcamStartTime
     * @param float $webcamEndTime
     * @param float $screenStartTime
     * @param float $screenEndTime
     * @return array
     */
    private function calculateTimelineSegments(
        float $webcamStartTime, 
        float $webcamEndTime, 
        float $screenStartTime, 
        float $screenEndTime
    ): array {
        $segments = [];
        
        // Determine the sequence of events
        if ($screenStartTime > $webcamStartTime) {
            // Webcam starts first
            $webcamOnlyDuration = $screenStartTime - $webcamStartTime;
            $overlayDuration = min($webcamEndTime, $screenEndTime) - $screenStartTime;
            $webcamOnlyAfterDuration = max(0, $webcamEndTime - $screenEndTime);
            
            if ($webcamOnlyDuration > 0) {
                $segments[] = [
                    'type' => 'webcam_only',
                    'start' => 0,
                    'duration' => $webcamOnlyDuration,
                    'description' => 'Webcam only (before screen sharing)'
                ];
            }
            
            if ($overlayDuration > 0) {
                $segments[] = [
                    'type' => 'screen_overlay',
                    'start' => $webcamOnlyDuration,
                    'duration' => $overlayDuration,
                    'description' => 'Screen with webcam overlay'
                ];
            }
            
            if ($webcamOnlyAfterDuration > 0) {
                $segments[] = [
                    'type' => 'webcam_only',
                    'start' => $webcamOnlyDuration + $overlayDuration,
                    'duration' => $webcamOnlyAfterDuration,
                    'description' => 'Webcam only (after screen sharing)'
                ];
            }
        } else {
            // Screen starts first or simultaneously
            $overlayDuration = min($webcamEndTime, $screenEndTime) - max($webcamStartTime, $screenStartTime);
            
            if ($overlayDuration > 0) {
                $segments[] = [
                    'type' => 'screen_overlay',
                    'start' => 0,
                    'duration' => $overlayDuration,
                    'description' => 'Screen with webcam overlay'
                ];
            }
        }
        
        return $segments;
    }

    /**
     * Execute FFmpeg command with error handling
     * 
     * @param string $command
     * @param string $outputPath
     * @return string
     * @throws Exception
     */
    private function executeCommand(string $command, string $outputPath): string
    {
        Log::info('Executing FFmpeg command', ['command' => $command]);

        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $errorMessage = 'FFmpeg merge failed: ' . implode("\n", $output);
            Log::error($errorMessage, [
                'return_code' => $returnCode,
                'command' => $command
            ]);
            throw new Exception($errorMessage);
        }

        if (!file_exists($outputPath)) {
            throw new Exception("Merged video file was not created: {$outputPath}");
        }

        Log::info('Video merge completed successfully', [
            'output_path' => $outputPath,
            'file_size' => filesize($outputPath)
        ]);

        return $outputPath;
    }

    /**
     * Merge streams with fallback for single stream scenarios
     *
     * @param string|null $webcamPath
     * @param string|null $screenPath
     * @param array $metadata
     * @return string
     * @throws Exception
     */
    public function mergeStreamsWithFallback(?string $webcamPath, ?string $screenPath, array $metadata = []): string
    {
        if ($webcamPath && $screenPath) {
            // Both streams available - merge them
            return $this->mergeStreams($webcamPath, $screenPath, $metadata);
        } elseif ($screenPath) {
            // Only screen recording - convert to standard format
            return $this->convertSingleStream($screenPath, 'screen');
        } elseif ($webcamPath) {
            // Only webcam - convert to standard format
            return $this->convertSingleStream($webcamPath, 'webcam');
        } else {
            throw new Exception("No video streams provided for processing");
        }
    }

    /**
     * Convert single stream to standard format
     *
     * @param string $inputPath
     * @param string $streamType
     * @return string
     * @throws Exception
     */
    private function convertSingleStream(string $inputPath, string $streamType): string
    {
        $ffmpegBinary = config('ffmpeg.ffmpeg.binaries', 'ffmpeg');
        $outputPath = $this->generateOutputPath();
        
        Log::info("Converting single {$streamType} stream", [
            'input_path' => $inputPath,
            'output_path' => $outputPath
        ]);

        // Validate input file
        if (!file_exists($inputPath)) {
            throw new Exception("Input file does not exist: {$inputPath}");
        }

        // Build conversion command with error handling and stream mapping
        $command = "\"{$ffmpegBinary}\" " .
                   "-i \"{$inputPath}\" " .
                   "-c:v libx264 -preset medium -crf 23 -profile:v high -level 4.0 " .
                   "-c:a aac -b:a 128k -ar 44100 -ac 2 " .
                   "-movflags +faststart " .
                   "-avoid_negative_ts make_zero " .
                   "-max_muxing_queue_size 1024 " .
                   "\"{$outputPath}\" 2>&1";

        Log::info('Executing single stream conversion', ['command' => $command]);
        
        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            $errorMessage = "Single stream conversion failed: " . implode("\n", $output);
            Log::error($errorMessage, ['return_code' => $returnCode]);
            throw new Exception($errorMessage);
        }
        
        if (!file_exists($outputPath)) {
            throw new Exception("Converted video file was not created: {$outputPath}");
        }
        
        Log::info('Single stream conversion completed', [
            'output_path' => $outputPath,
            'file_size' => filesize($outputPath)
        ]);
        
        return $outputPath;
    }

    /**
     * Enhanced stream analysis with comprehensive codec and format detection
     */
    private function analyzeVideoStream(string $videoPath): array
    {
        $ffprobe = config('ffmpeg.ffprobe.binaries', 'ffprobe');
        
        $command = "\"{$ffprobe}\" -v quiet -print_format json -show_format -show_streams \"{$videoPath}\"";
        
        Log::info('Analyzing video stream', ['path' => basename($videoPath), 'command' => $command]);
        
        $output = shell_exec($command);
        $data = json_decode($output, true);
        
        if (!$data) {
            $error = "Failed to analyze video stream: {$videoPath}";
            Log::error($error, ['ffprobe_output' => $output]);
            throw new Exception($error);
        }
        
        $info = [
            'width' => 0,
            'height' => 0,
            'duration' => 0,
            'has_video' => false,
            'has_audio' => false,
            'video_codec' => null,
            'audio_codec' => null,
            'frame_rate' => null,
            'bitrate' => null,
            'format_name' => null
        ];
        
        // Enhanced format analysis
        if (isset($data['format'])) {
            $format = $data['format'];
            $info['format_name'] = $format['format_name'] ?? null;
            $info['duration'] = isset($format['duration']) ? (float)$format['duration'] : 0;
            $info['bitrate'] = isset($format['bit_rate']) ? (int)$format['bit_rate'] : null;
        }
        
        // Enhanced stream analysis
        if (isset($data['streams'])) {
            foreach ($data['streams'] as $stream) {
                if ($stream['codec_type'] === 'video') {
                    $info['has_video'] = true;
                    $info['width'] = $stream['width'] ?? 0;
                    $info['height'] = $stream['height'] ?? 0;
                    $info['video_codec'] = $stream['codec_name'] ?? null;
                    
                    // Extract frame rate
                    if (isset($stream['r_frame_rate'])) {
                        $frameRate = $stream['r_frame_rate'];
                        if (strpos($frameRate, '/') !== false) {
                            [$num, $den] = explode('/', $frameRate);
                            if ($den > 0) {
                                $info['frame_rate'] = round($num / $den, 2);
                            }
                        }
                    }
                } elseif ($stream['codec_type'] === 'audio') {
                    $info['has_audio'] = true;
                    $info['audio_codec'] = $stream['codec_name'] ?? null;
                }
            }
        }
        
        Log::info('Stream analysis completed', [
            'file' => basename($videoPath),
            'analysis' => $info
        ]);
        
        // Validate that we have video stream
        if (!$info['has_video']) {
            throw new Exception("No video stream found in file: {$videoPath}");
        }
        
        return $info;
    }

    /**
     * Enhanced validation with detailed file checks
     */
    private function validateInputFiles(string $webcamPath, string $screenPath): void
    {
        $files = [
            'webcam' => $webcamPath,
            'screen' => $screenPath
        ];
        
        foreach ($files as $type => $path) {
            if (!file_exists($path)) {
                throw new Exception("{$type} video file does not exist: {$path}");
            }
            
            if (!is_readable($path)) {
                throw new Exception("{$type} video file is not readable: {$path}");
            }
            
            $fileSize = filesize($path);
            if ($fileSize === 0) {
                throw new Exception("{$type} video file is empty: {$path}");
            }
            
            // Minimum size check (1KB)
            if ($fileSize < 1024) {
                Log::warning("{$type} video file is very small", [
                    'path' => basename($path),
                    'size' => $fileSize
                ]);
            }
            
            Log::info("Validated {$type} video file", [
                'path' => basename($path),
                'size' => $fileSize
            ]);
        }
    }

    /**
     * Calculate webcam overlay size based on screen dimensions
     */
    private function calculateWebcamOverlaySize(array $metadata, int $screenWidth, int $screenHeight): array
    {
        // Default size (20% of screen width)
        $defaultSize = 20;
        
        $overlaySize = $defaultSize;
        if (isset($metadata['overlay_settings']['size'])) {
            $overlaySize = max(10, min(50, (int)$metadata['overlay_settings']['size'])); // Clamp between 10-50%
        }
        
        // Calculate webcam overlay dimensions
        $webcamWidth = (int)(($screenWidth * $overlaySize) / 100);
        $webcamHeight = (int)($webcamWidth * 3 / 4); // 4:3 aspect ratio
        
        // Ensure even dimensions for better codec compatibility
        $webcamWidth = $webcamWidth % 2 === 0 ? $webcamWidth : $webcamWidth - 1;
        $webcamHeight = $webcamHeight % 2 === 0 ? $webcamHeight : $webcamHeight - 1;
        
        return [
            'width' => $webcamWidth,
            'height' => $webcamHeight
        ];
    }

    /**
     * Calculate overlay position based on screen dimensions
     */
    private function calculateOverlayPosition(array $metadata, int $screenWidth, int $screenHeight): array
    {
        // Default position (bottom-right corner with 10px margin)
        $position = ['x' => 'main_w-overlay_w-10', 'y' => 'main_h-overlay_h-10'];
        
        if (isset($metadata['overlay_settings'])) {
            $overlaySettings = $metadata['overlay_settings'];
            
            // Use custom coordinates if available
            if (isset($overlaySettings['custom_x']) && isset($overlaySettings['custom_y']) && 
                $overlaySettings['custom_x'] !== null && $overlaySettings['custom_y'] !== null) {
                $position = [
                    'x' => (int)$overlaySettings['custom_x'],
                    'y' => (int)$overlaySettings['custom_y']
                ];
            } else if (isset($overlaySettings['position'])) {
                // Use preset position
                switch ($overlaySettings['position']) {
                    case 'top-left':
                        $position = ['x' => '10', 'y' => '10'];
                        break;
                    case 'top-right':
                        $position = ['x' => 'main_w-overlay_w-10', 'y' => '10'];
                        break;
                    case 'bottom-left':
                        $position = ['x' => '10', 'y' => 'main_h-overlay_h-10'];
                        break;
                    case 'bottom-right':
                    default:
                        $position = ['x' => 'main_w-overlay_w-10', 'y' => 'main_h-overlay_h-10'];
                        break;
                }
            }
        }
        
        return $position;
    }

    /**
     * Generate unique output file path
     */
    private function generateOutputPath(string $prefix = 'merged_video_'): string
    {
        $tempDir = sys_get_temp_dir();
        $filename = $prefix . uniqid() . '.mp4';
        return $tempDir . DIRECTORY_SEPARATOR . $filename;
    }

    /**
     * Validate video streams for potential freezing issues
     */
    private function validateStreamsForFreezingIssues(array $webcamInfo, array $screenInfo): void
    {
        Log::info('🔍 VALIDATING STREAMS FOR FREEZING ISSUES');
        
        // Check webcam stream
        if (isset($webcamInfo['frame_rate']) && $webcamInfo['frame_rate'] < 15) {
            Log::warning('⚠️ LOW WEBCAM FRAME RATE DETECTED', [
                'frame_rate' => $webcamInfo['frame_rate'],
                'warning' => 'This may cause choppy video playback'
            ]);
        }
        
        // Check screen stream  
        if (isset($screenInfo['frame_rate']) && $screenInfo['frame_rate'] < 15) {
            Log::warning('⚠️ LOW SCREEN FRAME RATE DETECTED', [
                'frame_rate' => $screenInfo['frame_rate'],
                'warning' => 'This is likely the cause of frozen screen frames'
            ]);
        }
        
        // Check for very large resolution differences
        $webcamWidth = $webcamInfo['width'] ?? 0;
        $screenWidth = $screenInfo['width'] ?? 0;
        
        if ($webcamWidth > 0 && $screenWidth > 0) {
            $resolutionRatio = max($webcamWidth, $screenWidth) / min($webcamWidth, $screenWidth);
            if ($resolutionRatio > 5) {
                Log::warning('⚠️ LARGE RESOLUTION DIFFERENCE DETECTED', [
                    'webcam_resolution' => ($webcamInfo['width'] ?? 'unknown') . 'x' . ($webcamInfo['height'] ?? 'unknown'),
                    'screen_resolution' => ($screenInfo['width'] ?? 'unknown') . 'x' . ($screenInfo['height'] ?? 'unknown'),
                    'ratio' => $resolutionRatio,
                    'warning' => 'This may affect overlay quality'
                ]);
            }
        }
        
        // Check stream durations
        $webcamDuration = $webcamInfo['duration'] ?? 0;
        $screenDuration = $screenInfo['duration'] ?? 0;
        
        if (abs($webcamDuration - $screenDuration) > 5) {
            Log::warning('⚠️ SIGNIFICANT DURATION DIFFERENCE DETECTED', [
                'webcam_duration' => $webcamDuration,
                'screen_duration' => $screenDuration,
                'difference' => abs($webcamDuration - $screenDuration),
                'warning' => 'Streams have significantly different durations'
            ]);
        }
        
        Log::info('✅ Stream validation completed');
    }

    /**
     * Clean up temporary files
     */
    public function cleanupTempFiles(array $filePaths): void
    {
        foreach ($filePaths as $path) {
            if (file_exists($path)) {
                unlink($path);
                Log::info("Cleaned up temporary file: {$path}");
            }
        }
    }

    /**
     * Move merged video to final storage location
     */
    public function moveToFinalLocation(string $tempPath, string $finalPath): bool
    {
        try {
            if (!file_exists($tempPath)) {
                Log::error('Temporary merged video file not found', ['temp_path' => $tempPath]);
                return false;
            }
            
            // Ensure destination directory exists
            $finalDir = dirname($finalPath);
            if (!is_dir($finalDir)) {
                mkdir($finalDir, 0755, true);
            }
            
            // Move file
            if (rename($tempPath, $finalPath)) {
                Log::info('Merged video moved to final location', [
                    'from' => basename($tempPath),
                    'to' => basename($finalPath),
                    'size' => filesize($finalPath)
                ]);
                return true;
            } else {
                Log::error('Failed to move merged video to final location', [
                    'from' => $tempPath,
                    'to' => $finalPath
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Error moving merged video to final location: ' . $e->getMessage());
            return false;
        }
    }
} 