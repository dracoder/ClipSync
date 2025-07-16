<template>
    <div class="flex flex-col items-center justify-center">
        <div class="w-[90vw] md:w-[60vw] p-5 shadow-brutal rounded-xl overflow-hidden bg-yellow-200 h-[60vh] md:h-[60vh] lg:h-[70vh] 2xl:h-[70vh] mx-auto mt-16 md:mt-20 lg:mt-4 2xl:mt-1 relative">            
            
            <button @click="$emit('close')" v-if="isModal" class="absolute top-2 right-2 text-black hover:text-red-600">
                <i class="iconoir-xmark text-2xl text-center"></i>
            </button>
            <div v-if="recordingVideo || isPaused" class="absolute bg-yellow-200 flex items-center justify-center px-2 py-1 rounded-md top-2 left-2 gap-1 z-50">
                <p class="clip-text text-sm">
                    {{ recordingVideo ? t('recording') : t('paused') }} 
                </p>
                <span v-if="recordingVideo" class=" text-white rounded-full p-1.5 bg-red-600 animate-pulse"></span>
                <i v-else class="iconoir-pause-solid text-xs text-white rounded-full p-0.5 bg-black animate-pulse"></i>
                <!-- <i class=""></i> -->
            </div>
            <div class="bg-white shadow-md rounded-md p-4 h-full flex flex-col md:flex-row items-center justify-center gap-4" v-if="!recordingVideo && !isPaused">
                <!-- <p class="clip-text text-2xl">
                    {{ t('clip.record_start') }}
                </p> -->
            </div>
            <div class="bg-black shadow-md rounded-md p-4 h-full relative" v-if="recordingVideo || isPaused">
                <!-- Screen sharing video (main display when screen sharing is active) -->
                <video
                    v-if="isScreenSharing"
                    id="screen-video"
                    class="w-full h-full object-contain rounded-md"
                    autoplay
                    playsinline
                    muted
                    ref="screenElement"
                />
                <!-- Webcam video (main display when not screen sharing, overlay when screen sharing) -->
                <video
                    id="video-element"
                    :class="[
                        'rounded-md',
                        isScreenSharing ? 'absolute bottom-4 right-4 w-48 h-36 object-cover shadow-lg border-2 border-yellow-400 z-10' : 'w-full h-full object-contain'
                    ]"
                    autoplay
                    playsinline
                    muted
                    ref="videoElement"
                />
            </div>
            <button class="btn btn-theme btn-lg absolute inset-0 m-auto w-5/6 md:w-full max-w-xs lg:max-w-md" @click="playPauseRecording" v-if="!recordingVideo && !isSaving">
                <p class="clip-text text-xs md:text-sm lg:text-xl">
                    {{ isPaused ? t('clip.record_resume') : t('clip.record_start') }}
                </p>
                <i class="iconoir-play-solid text-lg md:text-xl lg:text-2xl"></i>
            </button>
        </div>
        <div v-if="recordingVideo || isPaused" class="flex justify-center mt-2 items-center">
            <button 
                class="btn btn-theme btn-md px-4 py-2 clip-text mx-2 my-4"
                :class="isScreenSharing ? 'btn-error' : ''"
                @click="startScreenShare"
            >
                <i class="text-2xl" :class="!isScreenSharing ? 'iconoir-multi-mac-os-window' : 'iconoir-window-xmark'"></i>
            </button>
            <button class="btn btn-theme btn-md px-4 py-2 clip-text mx-2 my-4" @click="pauseVideoRecording">
                <i class="text-2xl" :class="!isPaused ? 'iconoir-pause-solid' : 'iconoir-play-solid'"></i>
            </button>
            <button class="btn btn-theme btn-md px-4 py-2 clip-text mx-2 my-4" @click="stopVideoRecording">
                <i class="text-lg iconoir-stop-solid"></i>
            </button>
            <span class="shadow-brutal rounded-md bg-white px-4 py-3 clip-text mx-2 my-4 border border-black hover:border-white hover:bg-black hover:text-white">
                {{ Math.floor(elapsedTime / 60).toString().padStart(2, '0') }}:{{ (elapsedTime % 60).toString().padStart(2, '0') }}
            </span>
        </div>
        
        <!-- Save Modal -->
        <div v-if="createModal.show" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 w-full z-[1000]">
            <div class="p-5 w-full">
                <CreateClipCard :video-blob="createModal.blob" @close="close"/>
            </div>
        </div>
    </div>
    <div v-if="recordingVideo && isScreenSharing && false" class="absolute top-2 right-2 bg-black bg-opacity-50 p-2 rounded-md z-50">
        <!-- CRITICAL FIX: Removed webcam overlay controls since we're hiding webcam during screen recording -->
        <!-- This prevents confusion and makes it clear that only screen is being recorded -->
    </div>

</template>

<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue';
import { useI18n } from 'vue-i18n';
import { useAuthStore } from '@/stores/auth';
import CreateClipCard from '@/components/clip/CreateClipCard.vue';
//import { createFFmpeg, fetchFile } from '@ffmpeg/ffmpeg';
import { useRouter, useRoute } from 'vue-router';

const { t } = useI18n();
const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();
const screenElement = ref(null);
const isScreenSharing = ref(false);
const videoElement = ref(null);
const recordingVideo = ref(false);
const isPaused = ref(false);
const mediaRecorder = ref(null);
const videoBlob = ref(null);
const chunks = ref([]);
const videoStream = ref(null);
const screenSessions = ref([]);
const currentSessionStartTime = ref(null);
const webcamSessions = ref([]);
// Removed: Canvas-related refs no longer needed for direct screen display
// const compositeCanvas = ref(null);
// const compositeContext = ref(null);
// const compositeStream = ref(null);
// const compositeRecorder = ref(null);
const isProcessing = ref(false);
const showSaveModal = ref(false);
const isSaving = ref(false);
const createModal = ref({
    show: false,
    blob: null
});
const elapsedTime = ref(0);
const maxRecordingTime = ref(300);
let timerInterval = null;

// Options: 'bottom-right', 'bottom-left', 'top-right', 'top-left'
const webcamOverlayPosition = ref('bottom-right'); 
const webcamOverlaySize = ref(20); // % screen width
const showWebcamOverlay = ref(true);
const webcamOverlayX = ref(null);
const webcamOverlayY = ref(null);
const isDragging = ref(false);
const dragStartX = ref(0);
const dragStartY = ref(0);

// Enhanced dual recording variables
const webcamRecorder = ref(null);
const webcamChunks = ref([]);
const webcamStream = ref(null);
const webcamStartTime = ref(null);
const webcamEndTime = ref(null);

const screenRecorder = ref(null);
const screenChunks = ref([]);
const screenStream = ref(null);
const screenStartTime = ref(null);
const screenEndTime = ref(null);

// Recording session management
const recordingSessionId = ref(null);
const syncTimestamps = ref({
    sessionStart: null,
    webcamStart: null,
    screenStart: null,
    webcamEnd: null,
    screenEnd: null
});

// Recording state
const webcamRecordingState = ref('idle'); // idle, recording, paused, stopped
const screenRecordingState = ref('idle'); // idle, recording, paused, stopped

// CRITICAL FIX: Track actual screen sharing usage during recording
const screenSharingUsedDuringRecording = ref(false);
const screenSharingActiveSessions = ref([]); // Track when screen sharing was active during recording

const emit = defineEmits(['close', 'recordCompleted']);

const props = defineProps({
    isModal: {
        type: Boolean,
        default: false
    },
    moduleType: {
        type: String,
        default: 'clip'
    }
});

const getSupportedMimeType = () => {
    // Try MP4 first if supported (more reliable for FFmpeg)
    const types = [
        'video/mp4;codecs=avc1.42E01E,mp4a.40.2', // H.264 + AAC
        'video/webm;codecs=vp8,opus',              // VP8 + Opus (fallback)
        'video/webm;codecs=vp9,opus',              // VP9 + Opus
        'video/webm',                              // Default WebM
        'video/mp4'                                // Default MP4
    ];
    
    for (const type of types) {
        if (MediaRecorder.isTypeSupported(type)) {
            console.log('Using MIME type:', type);
            return type;
        }
    }
    
    console.log('No supported MIME type found, falling back to browser default');
    return ''; // Let browser choose
};

const startTimer = () => {
    timerInterval = setInterval(() => {
        elapsedTime.value++;
        if (elapsedTime.value >= maxRecordingTime.value) {
            stopVideoRecording();
            Toast.fire({
                icon: 'info',
                                    title: t('clip.max_recording_time_reached'),
                timer: 3000
            });
        }
    }, 1000);
};

const stopTimer = () => {
    clearInterval(timerInterval);
};

const resetTimer = () => {
    elapsedTime.value = 0;
    clearInterval(timerInterval);
};

//const ffmpeg = createFFmpeg({ log: true });

if(mediaRecorder.value){
    mediaRecorder.value.addEventListener('dataavailable', event => {
        chunks.value.push(event.data)
    })
    mediaRecorder.value.addEventListener('stop', () => {
        console.log('MediaRecorder stopped')
    })
}
const playPauseRecording = async () => {
    if(isPaused.value){
        await pauseVideoRecording();
    } else {
        await startRecording();
    }
}

const checkBrowserCompatibility = () => {
  if (!navigator.mediaDevices || !window.MediaRecorder) {
    Toast.fire({ 
      icon: 'error', 
                          title: t('clip.browser_not_supported'), 
      timer: 3000 
    });
    return false;
  }
  return true;
};

/**
 * Enhanced dual recording system - starts both camera and screen recording independently
 */
const startRecording = async () => {
    if (!checkBrowserCompatibility()) return;
    
    try {
        recordingVideo.value = true;
        recordingSessionId.value = Date.now();
        syncTimestamps.value.sessionStart = Date.now();
        
        // CRITICAL FIX: Reset screen sharing tracking for new recording session
        screenSharingUsedDuringRecording.value = false;
        screenSharingActiveSessions.value = [];
        
        // Clear previous chunks
        webcamChunks.value = [];
        screenChunks.value = [];
        chunks.value = []; // Keep for backward compatibility
        
        // Start webcam recording (always required)
        await startWebcamRecording();
        
        // Start screen recording if screen sharing is active
        if (isScreenSharing.value) {
            await startScreenRecording();
            // CRITICAL FIX: Mark that screen sharing was actually used during this recording
            screenSharingUsedDuringRecording.value = true;
            screenSharingActiveSessions.value.push({
                startTime: Date.now(),
                sessionStart: syncTimestamps.value.sessionStart
            });
        }
        
        startTimer();
        
        console.log('🎬 ENHANCED RECORDING SESSION STARTED:', {
            sessionId: recordingSessionId.value,
            webcamActive: webcamRecordingState.value === 'recording',
            screenActive: screenRecordingState.value === 'recording',
            screenSharingActive: isScreenSharing.value,
            screenSharingUsedDuringRecording: screenSharingUsedDuringRecording.value,
            timestamp: syncTimestamps.value.sessionStart
        });
        
    } catch (error) {
        recordingVideo.value = false;
        console.error('Error starting dual recording:', error);
        await handleRecordingError(error);
    }
};

/**
 * Start robust webcam recording session
 */
const startWebcamRecording = async () => {
    try {
        // Request webcam with optimal settings
        webcamStream.value = await navigator.mediaDevices.getUserMedia({ 
            video: {
                width: { ideal: 1280 },
                height: { ideal: 720 },
                frameRate: { ideal: 30 }
            }, 
            audio: true 
        });
        
        videoElement.value.srcObject = webcamStream.value;
        
        // Use the most compatible format
        const mimeType = getSupportedMimeType();
        console.log('Using MIME type for webcam:', mimeType);
        
        // Create robust MediaRecorder for webcam
        const recorderOptions = {
            videoBitsPerSecond: 1500000, // 1.5 Mbps for stability
            audioBitsPerSecond: 128000   // 128 kbps audio
        };
        
        if (mimeType) {
            recorderOptions.mimeType = mimeType;
        }
        
        webcamRecorder.value = new MediaRecorder(webcamStream.value, recorderOptions);
        
        // Enhanced webcam data collection
        webcamRecorder.value.ondataavailable = (e) => {
            if (e.data && e.data.size > 0) {
                const chunkData = {
                    timestamp: Date.now(),
                    data: e.data,
                    type: e.data.type,
                    sessionId: recordingSessionId.value,
                    relativeTime: Date.now() - syncTimestamps.value.sessionStart
                };
                
                webcamChunks.value.push(chunkData);
                chunks.value.push(chunkData); // Maintain backward compatibility
                
                console.log('Webcam chunk collected:', {
                    size: e.data.size,
                    totalChunks: webcamChunks.value.length,
                    sessionTime: chunkData.relativeTime
                });
            }
        };
        
        // Webcam recorder error handling
        webcamRecorder.value.onerror = (event) => {
            console.error('Webcam MediaRecorder error:', event);
            webcamRecordingState.value = 'error';
            Toast.fire({ 
                icon: 'error', 
                                    title: t('clip.webcam_recorder_error'), 
                timer: 3000 
            });
        };
        
        webcamRecorder.value.onstart = () => {
            webcamRecordingState.value = 'recording';
            webcamStartTime.value = Date.now();
            syncTimestamps.value.webcamStart = webcamStartTime.value;
            console.log('Webcam recording started successfully');
        };
        
        webcamRecorder.value.onstop = () => {
            webcamRecordingState.value = 'stopped';
            webcamEndTime.value = Date.now();
            syncTimestamps.value.webcamEnd = webcamEndTime.value;
            console.log('Webcam recording stopped, chunks:', webcamChunks.value.length);
        };
        
        webcamRecorder.value.onpause = () => {
            webcamRecordingState.value = 'paused';
            console.log('Webcam recording paused');
        };
        
        webcamRecorder.value.onresume = () => {
            webcamRecordingState.value = 'recording';
            console.log('Webcam recording resumed');
        };
        
        // Start webcam recording
        webcamRecorder.value.start(250); // 250ms chunks for responsiveness
        
    } catch (error) {
        webcamRecordingState.value = 'error';
        console.error('Error starting webcam recording:', error);
        throw error;
    }
};

/**
 * Start robust screen recording session
 */
const startScreenRecording = async () => {
    try {
        if (!screenStream.value) {
            throw new Error('Screen stream not available');
        }
        
        // Ensure screen element exists before setting srcObject
        if (!screenElement.value) {
            console.warn('Screen element not available, waiting for next tick...');
            await nextTick();
            
            if (!screenElement.value) {
                console.error('Screen video element still not found after nextTick. isScreenSharing:', isScreenSharing.value);
                throw new Error('Screen video element not found in DOM');
            }
        }
        
        console.log('Setting screen element srcObject, element exists:', !!screenElement.value);
        screenElement.value.srcObject = screenStream.value;
        
        // CRITICAL FIX: Enhanced screen recording configuration to prevent frozen frames
        const mimeType = getSupportedMimeType();
        console.log('Using MIME type for screen:', mimeType);
        
        // ENHANCED: Screen recording configuration with optimized settings for dynamic content
        const recorderOptions = {
            videoBitsPerSecond: 3000000, // Increased to 3 Mbps for better screen content quality
            audioBitsPerSecond: 128000   // 128 kbps audio
        };
        
        // CRITICAL FIX: Use specific codec for screen content if available
        if (MediaRecorder.isTypeSupported('video/webm;codecs=vp9')) {
            recorderOptions.mimeType = 'video/webm;codecs=vp9';
            console.log('🎯 Using VP9 codec for optimal screen recording');
        } else if (MediaRecorder.isTypeSupported('video/webm;codecs=vp8')) {
            recorderOptions.mimeType = 'video/webm;codecs=vp8';
            console.log('🎯 Using VP8 codec for screen recording');
        } else if (mimeType) {
            recorderOptions.mimeType = mimeType;
        }
        
        console.log('🖥️ FINAL SCREEN RECORDER CONFIG:', recorderOptions);
        
        screenRecorder.value = new MediaRecorder(screenStream.value, recorderOptions);
        
        // Enhanced screen data collection with better timing
        screenRecorder.value.ondataavailable = (e) => {
            if (e.data && e.data.size > 0) {
                const chunkData = {
                    timestamp: Date.now(),
                    data: e.data,
                    type: e.data.type,
                    sessionId: recordingSessionId.value,
                    relativeTime: Date.now() - syncTimestamps.value.sessionStart
                };
                
                screenChunks.value.push(chunkData);
                
                console.log('Screen chunk collected:', {
                    size: e.data.size,
                    totalChunks: screenChunks.value.length,
                    sessionTime: chunkData.relativeTime,
                    mimeType: e.data.type
                });
            } else {
                console.warn('Empty or invalid screen chunk received:', e.data?.size || 'undefined size');
            }
        };
        
        // Screen recorder error handling
        screenRecorder.value.onerror = (event) => {
            console.error('Screen MediaRecorder error:', event);
            screenRecordingState.value = 'error';
            Toast.fire({ 
                icon: 'warning', 
                                    title: t('clip.screen_recorder_error'), 
                timer: 3000 
            });
        };
        
        screenRecorder.value.onstart = () => {
            screenRecordingState.value = 'recording';
            screenStartTime.value = Date.now();
            syncTimestamps.value.screenStart = screenStartTime.value;
            
            // CRITICAL FIX: Mark that screen sharing is being used during this recording
            if (recordingVideo.value || isPaused.value) {
                screenSharingUsedDuringRecording.value = true;
                screenSharingActiveSessions.value.push({
                    startTime: screenStartTime.value,
                    sessionStart: syncTimestamps.value.sessionStart
                });
                
                console.log('🖥️ SCREEN SHARING ACTIVATED DURING RECORDING - Will create overlay video');
            }
            
            console.log('Screen recording started successfully with enhanced config');
        };
        
        screenRecorder.value.onstop = () => {
            screenRecordingState.value = 'stopped';
            screenEndTime.value = Date.now();
            syncTimestamps.value.screenEnd = screenEndTime.value;
            console.log('Screen recording stopped, chunks:', screenChunks.value.length);
        };
        
        screenRecorder.value.onpause = () => {
            screenRecordingState.value = 'paused';
            console.log('Screen recording paused');
        };
        
        screenRecorder.value.onresume = () => {
            screenRecordingState.value = 'recording';
            console.log('Screen recording resumed');
        };
        
        // CRITICAL FIX: Enhanced timing for screen recording chunks
        // Use shorter chunks for dynamic screen content to prevent freezing
        screenRecorder.value.start(100); // 100ms chunks for more responsive screen capture
        
    } catch (error) {
        screenRecordingState.value = 'error';
        console.error('Error starting screen recording:', error);
        throw error;
    }
};

/**
 * Enhanced pause/resume functionality for dual recording
 */
const pauseVideoRecording = async () => {
    try {
        if (recordingVideo.value) {
            // Pause both recorders
            await pauseWebcamRecording();
            
            if (isScreenSharing.value && screenRecorder.value) {
                await pauseScreenRecording();
            }
            
            isPaused.value = true;
            recordingVideo.value = false;
            stopTimer();
            
            console.log('Both recording streams paused');
            
        } else {
            // Resume both recorders
            await resumeWebcamRecording();
            
            if (isScreenSharing.value && screenRecorder.value) {
                await resumeScreenRecording();
            }
            
            isPaused.value = false;
            recordingVideo.value = true;
            startTimer();
            
            console.log('Both recording streams resumed');
        }
    } catch (error) {
        console.error('Error pausing/resuming dual recording:', error);
        await handleRecordingError(error);
    }
};

/**
 * Pause webcam recording safely
 */
const pauseWebcamRecording = async () => {
    if (webcamRecorder.value && webcamRecorder.value.state === 'recording') {
        webcamRecorder.value.requestData(); // Get any pending data
        webcamRecorder.value.pause();
    }
};

/**
 * Resume webcam recording safely
 */
const resumeWebcamRecording = async () => {
    if (webcamRecorder.value && webcamRecorder.value.state === 'paused') {
        webcamRecorder.value.resume();
    }
};

/**
 * Pause screen recording safely
 */
const pauseScreenRecording = async () => {
    if (screenRecorder.value && screenRecorder.value.state === 'recording') {
        screenRecorder.value.requestData(); // Get any pending data
        screenRecorder.value.pause();
    }
};

/**
 * Resume screen recording safely
 */
const resumeScreenRecording = async () => {
    if (screenRecorder.value && screenRecorder.value.state === 'paused') {
        screenRecorder.value.resume();
    }
};

/**
 * Enhanced stop recording with proper dual stream handling
 */
const stopVideoRecording = async () => {
    try {
        if (animationFrameId) {
            cancelAnimationFrame(animationFrameId);
            animationFrameId = null;
        }
        
        console.log('Stopping dual recording session:', recordingSessionId.value);
        
        // Stop both recorders safely
        await stopWebcamRecording();
        
        if (isScreenSharing.value && screenRecorder.value) {
            await stopScreenRecording();
        }
        
        // Wait for all data to be processed
        await new Promise(resolve => setTimeout(resolve, 500));
        
        await saveEnhancedVideo();
        stopVideoStream();
        resetTimer();
        
        console.log('Dual recording session completed successfully');
        
    } catch (error) {
        console.error('Error stopping dual recording:', error);
        await handleRecordingError(error);
    } finally {
        recordingVideo.value = false;
        isPaused.value = false;
        resetRecordingState();
    }
};

/**
 * Stop webcam recording safely
 */
const stopWebcamRecording = async () => {
    return new Promise((resolve) => {
        if (webcamRecorder.value && webcamRecorder.value.state !== 'inactive') {
            webcamRecorder.value.onstop = () => {
                webcamRecordingState.value = 'stopped';
                console.log('Webcam recording stopped successfully');
                resolve();
            };
            
            webcamRecorder.value.requestData(); // Get final data
            webcamRecorder.value.stop();
        } else {
            resolve();
        }
    });
};

/**
 * Stop screen recording safely
 */
const stopScreenRecording = async () => {
    return new Promise((resolve) => {
        if (screenRecorder.value && screenRecorder.value.state !== 'inactive') {
            screenRecorder.value.onstop = () => {
                screenRecordingState.value = 'stopped';
                console.log('Screen recording stopped successfully');
                resolve();
            };
            
            screenRecorder.value.requestData(); // Get final data
            screenRecorder.value.stop();
        } else {
            resolve();
        }
    });
};

/**
 * Enhanced video processing with proper dual stream handling
 */
const saveEnhancedVideo = async () => {
    try {
        isSaving.value = true;
        isProcessing.value = true;

        console.log('🎬 STARTING ENHANCED VIDEO PROCESSING...');
        console.log('📊 DETAILED RECORDING SESSION ANALYSIS:');
        console.log('   - Session ID:', recordingSessionId.value);
        console.log('   - Recording duration:', elapsedTime.value, 'seconds');
        console.log('   - Screen sharing was active:', isScreenSharing.value ? 'YES' : 'NO');
        console.log('   - Screen sharing used during recording:', screenSharingUsedDuringRecording.value ? 'YES' : 'NO');
        console.log('   - Screen sharing sessions:', screenSharingActiveSessions.value.length);
        console.log('   - Webcam chunks recorded:', webcamChunks.value.length);
        console.log('   - Screen chunks recorded:', screenChunks.value.length);
        console.log('   - Webcam recording state:', webcamRecordingState.value);
        console.log('   - Screen recording state:', screenRecordingState.value);

        console.log('Processing enhanced dual streams:', {
            webcamChunks: webcamChunks.value.length,
            screenChunks: screenChunks.value.length,
            sessionId: recordingSessionId.value,
            syncData: syncTimestamps.value,
            screenSharingUsedDuringRecording: screenSharingUsedDuringRecording.value
        });

        // Process webcam stream
        let webcamBlob = null;
        if (webcamChunks.value.length > 0) {
            webcamBlob = await createBlobFromChunks(webcamChunks.value, 'webcam');
        }

        // CRITICAL FIX: Only process screen stream if screen sharing was actually used during recording
        let screenBlob = null;
        if (screenChunks.value.length > 0 && screenSharingUsedDuringRecording.value) {
            console.log('🖥️ PROCESSING SCREEN BLOB: Screen sharing was used during recording');
            screenBlob = await createBlobFromChunks(screenChunks.value, 'screen');
        } else if (screenChunks.value.length > 0 && !screenSharingUsedDuringRecording.value) {
            console.log('🚫 IGNORING SCREEN CHUNKS: Screen sharing was not used during recording');
            console.log('   - Screen chunks exist but were recorded outside of active recording session');
            console.log('   - This prevents false dual stream overlay');
        }

        // Validate that we have at least one valid stream
        if (!webcamBlob && !screenBlob) {
            throw new Error('No valid video streams recorded');
        }

        // Create enhanced metadata with precise timing and usage tracking
        const enhancedMetadata = {
            session_id: recordingSessionId.value,
            recording_duration: elapsedTime.value * 1000,
            sync_timestamps: syncTimestamps.value,
            screen_sharing_used_during_recording: screenSharingUsedDuringRecording.value,
            screen_sharing_sessions: screenSharingActiveSessions.value,
            webcam_stream_info: webcamBlob ? {
                start_time: webcamStartTime.value,
                end_time: webcamEndTime.value,
                chunk_count: webcamChunks.value.length,
                total_size: webcamBlob.size,
                mime_type: webcamBlob.type
            } : null,
            screen_stream_info: screenBlob ? {
                start_time: screenStartTime.value,
                end_time: screenEndTime.value,
                chunk_count: screenChunks.value.length,
                total_size: screenBlob.size,
                mime_type: screenBlob.type
            } : null,
            overlay_settings: {
                position: webcamOverlayPosition.value,
                size: webcamOverlaySize.value,
                custom_x: webcamOverlayX.value,
                custom_y: webcamOverlayY.value,
                visible: showWebcamOverlay.value
            },
            recording_quality: {
                webcam_bitrate: 1500000,
                screen_bitrate: 2500000,
                audio_bitrate: 128000
            }
        };

        // Store processed data for upload
        videoBlob.value = {
            webcam: webcamBlob,
            screen: screenBlob, // Will be null if screen sharing wasn't used
            metadata: enhancedMetadata
        };

        console.log('🎬 FINAL ENHANCED DUAL STREAM PROCESSING RESULT:');
        console.log('   ✅ Has webcam stream:', !!webcamBlob);
        console.log('   ✅ Has screen stream:', !!screenBlob);
        console.log('   📹 Webcam size:', webcamBlob?.size || 0, 'bytes');
        console.log('   🖥️ Screen size:', screenBlob?.size || 0, 'bytes');
        console.log('   🔄 Will use dual stream upload:', !!(webcamBlob && screenBlob));
        console.log('   📊 Sync timestamps:', enhancedMetadata.sync_timestamps);
        console.log('   🎯 Screen sharing used during recording:', screenSharingUsedDuringRecording.value);
        
        // CRITICAL DEBUG: Log exactly what we're sending to backend
        if (webcamBlob && screenBlob) {
            console.log('✅ DUAL STREAM DETECTED - Will use dual stream upload API');
            console.log('📊 SYNC TIMESTAMPS:', enhancedMetadata.sync_timestamps);
            console.log('📹 WEBCAM INFO:', enhancedMetadata.webcam_stream_info);
            console.log('🖥️ SCREEN INFO:', enhancedMetadata.screen_stream_info);
            console.log('🎯 EXPECTED RESULT: Screen video (1920x1080) with webcam overlay in', webcamOverlayPosition.value, 'position');
        } else if (webcamBlob && !screenBlob) {
            console.log('📹 WEBCAM ONLY - Screen sharing was never used during recording');
            console.log('🎯 EXPECTED RESULT: Full-screen webcam video (correct behavior)');
        } else if (!webcamBlob && screenBlob) {
            console.log('🖥️ SCREEN ONLY - Webcam was never recorded or no webcam chunks');
            console.log('🎯 EXPECTED RESULT: Full-screen screen recording');
        } else {
            console.log('❌ NO VALID STREAMS - This should not happen!');
        }

        console.log('🎯 USER GUIDANCE:');
        if (webcamBlob && screenBlob) {
            console.log('   📝 You should see: Your screen recording with your webcam overlay in the corner');
            console.log('   ⚠️ If you only see webcam: Check browser console for backend errors');
        } else if (webcamBlob && !screenBlob) {
            console.log('   📝 You should see: Only your webcam video (this is correct - you didn\'t use screen sharing)');
            console.log('   💡 To get overlay: Start screen sharing BEFORE or DURING recording');
        }

        openCreateModal();
        showSaveModal.value = true;

        // Clear chunks after successful processing
        webcamChunks.value = [];
        screenChunks.value = [];
        chunks.value = [];

    } catch (error) {
        console.error('Error in enhanced video processing:', error);
        close();
        isSaving.value = false;
        Toast.fire({ 
            icon: 'error', 
                                title: t('clip.processing_error'), 
            timer: 3000 
        });
    } finally {
        isProcessing.value = false;
    }
};

/**
 * Create a validated blob from chunks
 */
const createBlobFromChunks = async (chunks, streamType) => {
    try {
        const sortedChunks = chunks
            .sort((a, b) => a.timestamp - b.timestamp)
            .map(chunk => chunk.data)
            .filter(chunk => chunk && chunk.size > 0);

        if (sortedChunks.length === 0) {
            throw new Error(`No valid ${streamType} chunks found`);
        }

        const totalSize = sortedChunks.reduce((sum, chunk) => sum + chunk.size, 0);
        
        if (totalSize < 10000) { // Less than 10KB is suspicious
            console.warn(`${streamType} recording seems too small:`, totalSize, 'bytes');
            throw new Error(`${streamType} recording is too small or corrupted`);
        }

        const mimeType = chunks.length > 0 ? chunks[0].type : 'video/mp4';
        const blob = new Blob(sortedChunks, { type: mimeType });

        // Validate blob
        const testUrl = URL.createObjectURL(blob);
        URL.revokeObjectURL(testUrl);

        console.log(`Created ${streamType} blob:`, {
            size: blob.size,
            type: blob.type,
            chunkCount: sortedChunks.length
        });

        return blob;

    } catch (error) {
        console.error(`Error creating ${streamType} blob:`, error);
        throw error;
    }
};

/**
 * Reset recording state
 */
const resetRecordingState = () => {
    webcamRecordingState.value = 'idle';
    screenRecordingState.value = 'idle';
    recordingSessionId.value = null;
    syncTimestamps.value = {
        sessionStart: null,
        webcamStart: null,
        screenStart: null,
        webcamEnd: null,
        screenEnd: null
    };
    webcamStartTime.value = null;
    webcamEndTime.value = null;
    screenStartTime.value = null;
    screenEndTime.value = null;
    
    // CRITICAL FIX: Reset screen sharing tracking
    screenSharingUsedDuringRecording.value = false;
    screenSharingActiveSessions.value = [];
};

/**
 * Handle recording errors gracefully
 */
const handleRecordingError = async (error) => {
    console.error('Recording error occurred:', error);
    
    // Stop all recording safely
    try {
        if (webcamRecorder.value && webcamRecorder.value.state !== 'inactive') {
            webcamRecorder.value.stop();
        }
        if (screenRecorder.value && screenRecorder.value.state !== 'inactive') {
            screenRecorder.value.stop();
        }
    } catch (stopError) {
        console.error('Error stopping recorders during error handling:', stopError);
    }
    
    // Reset state
    recordingVideo.value = false;
    isPaused.value = false;
    resetRecordingState();
    stopVideoStream();
    resetTimer();
    
    // Show appropriate error message
    if (error.name === 'NotAllowedError') {
        Toast.fire({ 
            icon: 'error', 
                                title: t('clip.permission_denied'), 
            timer: 3000 
        });
    } else {
        Toast.fire({ 
            icon: 'error', 
                                title: t('clip.recording_error'), 
            timer: 3000 
        });
    }
};

/**
 * Stop all video streams and clean up resources
 */
const stopVideoStream = () => {
    try {
        // Stop webcam stream
        if (webcamStream.value) {
            webcamStream.value.getTracks().forEach(track => track.stop());
            webcamStream.value = null;
        }
        
        // Fallback for old videoStream reference
        if (videoStream.value) {
            videoStream.value.getTracks().forEach(track => track.stop());
            videoStream.value = null;
        }
        
        // Stop screen stream
        if (screenStream.value) {
            screenStream.value.getTracks().forEach(track => track.stop());
            screenStream.value = null;
        }
        
        // Clear video elements
        if (videoElement.value) {
            videoElement.value.srcObject = null;
        }
        if (screenElement.value) {
            screenElement.value.srcObject = null;
        }
        
        console.log('All video streams stopped and cleaned up');
        
    } catch (error) {
        console.error('Error stopping video streams:', error);
        Toast.fire({ 
            icon: 'error', 
            title: t('something_went_wrong'), 
            timer: 3000 
        });
    }
};

const openCreateModal = () => {
    if(props.moduleType === 'clip'){
        createModal.value = {
            show: true,
            blob: videoBlob.value
        }
    } else {
        emit('recordCompleted', videoBlob.value);
        isSaving.value = false;
    }
}

const close = () => {
    createModal.value = {
        show: false,
        blob: null
    }
    isSaving.value = false;
    
    // Clear all chunks
    chunks.value = [];
    webcamChunks.value = [];
    screenChunks.value = [];
    
    // Reset recording state
    resetRecordingState();
    currentSessionStartTime.value = null;
    
    stopVideoStream();
}

const checkScreenCaptureSupport = () => {
  if (!navigator.mediaDevices || !navigator.mediaDevices.getDisplayMedia) {
    alert(t('screen_capture_not_supported'));
    return false;
  }
  return true;
};

// Enhanced screen sharing with proper integration
const startScreenShare = async () => {
    if (!checkScreenCaptureSupport()) return;
    
    try {
        if (isScreenSharing.value) {
            console.log('🔄 STOPPING screen sharing...');
            // Stop screen sharing and recording
            if (screenRecorder.value && screenRecorder.value.state !== 'inactive') {
                await stopScreenRecording();
            }
            
            if (screenStream.value) {
                screenStream.value.getTracks().forEach(track => track.stop());
                screenStream.value = null;
            }
            
            isScreenSharing.value = false;
            screenRecordingState.value = 'idle';
            
            console.log('✅ Screen sharing stopped');
            
        } else {
            console.log('🔄 STARTING screen sharing...');
            console.log('📊 CURRENT RECORDING STATE:');
            console.log('   - Currently recording:', recordingVideo.value);
            console.log('   - Currently paused:', isPaused.value);
            console.log('   - Recording session ID:', recordingSessionId.value);
            console.log('   - Webcam recording state:', webcamRecordingState.value);
            
            // CRITICAL FIX: Enhanced screen sharing configuration to prevent frozen frames
            screenStream.value = await navigator.mediaDevices.getDisplayMedia({
                video: {
                    width: { ideal: 1920, max: 3840 },
                    height: { ideal: 1080, max: 2160 },
                    frameRate: { ideal: 30, max: 60 },
                    // CRITICAL: Prevent low frame rates that can cause apparent freezing
                    // Ensure minimum quality for dynamic content
                    mediaSource: 'screen',
                    cursor: 'always'
                },
                audio: {
                    echoCancellation: false,
                    noiseSuppression: false,
                    autoGainControl: false,
                    sampleRate: 44100
                }
            });
            
            // CRITICAL: Log actual screen stream properties with enhanced validation
            const videoTrack = screenStream.value.getVideoTracks()[0];
            const settings = videoTrack.getSettings();
            console.log('🖥️ ACTUAL SCREEN STREAM PROPERTIES:');
            console.log('   - Actual resolution:', settings.width + 'x' + settings.height);
            console.log('   - Frame rate:', settings.frameRate);
            console.log('   - Device ID:', settings.deviceId);
            console.log('   - Display surface:', settings.displaySurface);
            console.log('   - Cursor:', settings.cursor);
            console.log('   - Track state:', videoTrack.readyState);
            console.log('   - Track enabled:', videoTrack.enabled);
            
            // CRITICAL VALIDATION: Check for potential issues that cause frozen frames
            if (settings.frameRate < 15) {
                console.warn('⚠️ LOW FRAME RATE DETECTED:', settings.frameRate, 'fps');
                console.warn('   - This may cause apparent "frozen" video in final output');
                console.warn('   - Consider requesting higher frame rate or using different display source');
            }
            
            if (settings.width < 1280 || settings.height < 720) {
                console.warn('⚠️ LOW RESOLUTION DETECTED:', settings.width + 'x' + settings.height);
                console.warn('   - This may affect video quality but should not cause freezing');
            }
            
            // WARN if resolution is below expectations
            if (settings.width < 1920 || settings.height < 1080) {
                console.warn('⚠️ SCREEN RESOLUTION BELOW 1080p:');
                console.warn('   - Got:', settings.width + 'x' + settings.height);
                console.warn('   - Expected: 1920x1080 or higher');
                console.warn('   - This may be due to display scaling, browser limitations, or hardware constraints');
                console.warn('   - The video will still work but with lower screen quality');
            }
            
            isScreenSharing.value = true;
            console.log('✅ Screen stream obtained successfully');
            console.log('📊 SCREEN SHARING ACTIVATION RESULT:');
            console.log('   - isScreenSharing set to:', isScreenSharing.value);
            console.log('   - Screen stream active:', !!screenStream.value);
            console.log('   - Screen stream video tracks:', screenStream.value?.getVideoTracks().length || 0);
            console.log('   - Screen stream audio tracks:', screenStream.value?.getAudioTracks().length || 0);
            
            // Wait for Vue to update the DOM with the screen video element
            await nextTick();
            
            // CRITICAL FIX: Immediately set the screen video element srcObject for visual feedback
            if (screenElement.value && screenStream.value) {
                console.log('🖥️ Setting screen element srcObject for immediate display');
                screenElement.value.srcObject = screenStream.value;
                // Ensure the video plays automatically
                try {
                    await screenElement.value.play();
                    console.log('✅ Screen video element started playing');
                } catch (playError) {
                    console.warn('Screen video autoplay failed (this may be normal):', playError);
                }
            } else {
                console.error('❌ Screen element or stream not available for immediate display');
                console.log('   - screenElement.value:', !!screenElement.value);
                console.log('   - screenStream.value:', !!screenStream.value);
            }
            
            // CRITICAL: Check if currently recording
            console.log('📹 Recording state check:', {
                recordingVideo: recordingVideo.value,
                isPaused: isPaused.value,
                webcamRecordingState: webcamRecordingState.value,
                screenRecordingState: screenRecordingState.value
            });
            
            // If currently recording, start screen recording immediately
            if (recordingVideo.value || isPaused.value) {
                console.log('🎥 STARTING screen recording immediately since we are already recording...');
                await startScreenRecording();
                console.log('✅ Screen recording started, state:', screenRecordingState.value);
                
                // CRITICAL FIX: Mark that screen sharing was activated during active recording
                screenSharingUsedDuringRecording.value = true;
                screenSharingActiveSessions.value.push({
                    startTime: Date.now(),
                    sessionStart: syncTimestamps.value.sessionStart,
                    activatedDuringRecording: true
                });
                
                console.log('🎯 SCREEN SHARING ACTIVATED DURING ACTIVE RECORDING - Will create dual stream');
            } else {
                console.log('⏸️ Not recording yet, screen recording will start when recording begins');
            }
            
            // Handle screen share ending
            screenStream.value.getVideoTracks()[0].addEventListener('ended', () => {
                console.log('❌ Screen sharing ended by user');
                isScreenSharing.value = false;
                screenRecordingState.value = 'idle';
                
                if (screenRecorder.value && screenRecorder.value.state !== 'inactive') {
                    screenRecorder.value.stop();
                }
            });
            
            console.log('✅ Screen sharing setup completed');
        }
        
    } catch (error) {
        console.error('❌ Error in screen sharing:', error);
        isScreenSharing.value = false;
        Toast.fire({ 
            icon: 'error', 
                                title: t('clip.screen_share_error'), 
            timer: 3000 
        });
    }
};

const setupRoundRectPolyfill = () => {
    if (!CanvasRenderingContext2D.prototype.roundRect) {
        CanvasRenderingContext2D.prototype.roundRect = function(x, y, width, height, radius) {
            if (width < 2 * radius) radius = width / 2;
            if (height < 2 * radius) radius = height / 2;
            this.beginPath();
            this.moveTo(x + radius, y);
            this.arcTo(x + width, y, x + width, y + height, radius);
            this.arcTo(x + width, y + height, x, y + height, radius);
            this.arcTo(x, y + height, x, y, radius);
            this.arcTo(x, y, x + width, y, radius);
            this.closePath();
            return this;
        };
    }
};

// Animation frame for composite drawing
let animationFrameId = null;

// Removed: Canvas drag functions no longer needed for direct video display
// These were used for positioning webcam overlay on canvas composite
/*
const startDrag = (event) => {
    // Canvas drag functionality removed
};

const doDrag = (event) => {
    // Canvas drag functionality removed  
};

const endDrag = () => {
    // Canvas drag functionality removed
};
*/

onMounted(() => {
    // Removed canvas setup - now using direct video display
    setupRoundRectPolyfill();
});

onUnmounted(() => {
    // Removed canvas cleanup - now using direct video display
    
    stopTimer();
    stopVideoStream();
    if (animationFrameId) {
        cancelAnimationFrame(animationFrameId);
        animationFrameId = null;
    }
    
    // Clean up enhanced recorders
    if (webcamRecorder.value && webcamRecorder.value.state !== 'inactive') {
        webcamRecorder.value.stop();
    }
    
    if (screenRecorder.value && screenRecorder.value.state !== 'inactive') {
        screenRecorder.value.stop();
    }
    
    // Legacy cleanup
    if (mediaRecorder.value && mediaRecorder.value.state !== 'inactive') {
        mediaRecorder.value.stop();
    }
    
    // Removed: compositeRecorder cleanup (no longer used)
    
    resetRecordingState();
});

</script>

<style scoped>
.custom-scrollbar {
overflow-y: auto;
scrollbar-width: thin;
}

.custom-scrollbar::-webkit-scrollbar {
width: 10px;
height: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
background-color: #ccc;
border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-track {
background-color: #f0f0f0;
border-radius: 10px;
}

.cursor-move {
    cursor: move;
}
</style>