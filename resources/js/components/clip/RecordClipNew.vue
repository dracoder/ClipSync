<template>
    <div class="flex flex-col items-center justify-center">
        <div class="w-[90vw] md:w-[60vw] p-5 shadow-brutal rounded-xl overflow-hidden bg-yellow-200 h-[60vh] md:h-[60vh] lg:h-[70vh] 2xl:h-[70vh] mx-auto mt-16 md:mt-20 lg:mt-4 2xl:mt-1 relative">            
            
            <button @click="$emit('close')" v-if="isModal" class="absolute top-2 right-2 text-black hover:text-red-600">
                <i class="iconoir-xmark text-2xl text-center"></i>
            </button>
            
            <!-- Recording Status Indicator -->
            <div v-if="isRecording || isPaused" class="absolute bg-yellow-200 flex items-center justify-center px-2 py-1 rounded-md top-2 left-2 gap-1 z-50">
                <p class="clip-text text-sm">
                    {{ isRecording ? t('recording') : t('paused') }} 
                </p>
                <span v-if="isRecording" class="text-white rounded-full p-1.5 bg-red-600 animate-pulse"></span>
                <i v-else class="iconoir-pause-solid text-xs text-white rounded-full p-0.5 bg-black animate-pulse"></i>
            </div>

            <!-- Mute Status Indicator -->
            <div v-if="(isRecording || isPaused) && (isWebcamAudioMuted || (isScreenSharing && isScreenAudioMuted))" class="absolute bg-red-600 bg-opacity-90 text-white px-2 py-1 rounded-md top-2 right-16 text-xs z-50 flex items-center gap-1">
                <i class="iconoir-mic-mute text-xs" v-if="isWebcamAudioMuted"></i>
                <span v-if="isWebcamAudioMuted">{{ t('clip.webcam_muted') }}</span>
                <span v-if="isWebcamAudioMuted && isScreenSharing && isScreenAudioMuted" class="mx-1">|</span>
                <i class="iconoir-sound-off text-xs" v-if="isScreenSharing && isScreenAudioMuted"></i>
                <span v-if="isScreenSharing && isScreenAudioMuted">{{ t('clip.screen_muted') }}</span>
            </div>
            
            <!-- Start Recording UI -->
            <div class="bg-white shadow-md rounded-md p-4 h-full flex flex-col md:flex-row items-center justify-center gap-4" v-if="!isRecording && !isPaused">
                <!-- Preview will be shown here when not recording -->
            </div>
            
            <!-- Recording Canvas Container -->
            <div class="bg-black shadow-md rounded-md p-4 h-full relative overflow-hidden" v-if="isRecording || isPaused">
                <!-- Main Canvas for Recording -->
                <canvas
                    ref="recordingCanvas"
                    class="w-full h-full object-contain rounded-md"
                    :class="{ 'opacity-75': isPaused }"
                />
                
                <!-- Hidden video elements for stream sources -->
                <video
                    ref="webcamVideo"
                    class="hidden"
                    autoplay
                    playsinline
                    muted
                />
                <video
                    ref="screenVideo"
                    class="hidden"
                    autoplay
                    playsinline
                    muted
                />
                
                <!-- Overlay Position Controls (when screen sharing) -->
                <div v-if="isScreenSharing && (isRecording || isPaused)" class="absolute bottom-4 left-4 bg-black bg-opacity-75 text-white p-2 rounded-md text-xs">
                    <div class="flex items-center gap-2 mb-2">
                        <span>{{ t('clip.overlay_position') }}:</span>
                        <select 
                            v-model="overlayPosition" 
                            @change="updateOverlayPosition" 
                            class="bg-gray-800 text-white text-xs rounded px-1 tooltip tooltip-top"
                            :data-tip="t('clip.choose_overlay_position')"
                        >
                                                <option value="bottom-right">{{ t('clip.bottom_right') }}</option>
                    <option value="bottom-left">{{ t('clip.bottom_left') }}</option>
                    <option value="top-right">{{ t('clip.top_right') }}</option>
                    <option value="top-left">{{ t('clip.top_left') }}</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-2">
                        <span>{{ t('clip.overlay_size') }}:</span>
                        <input 
                            type="range" 
                            v-model="overlaySize" 
                            @input="updateOverlaySize"
                            min="15" 
                            max="35" 
                            step="5"
                            class="w-16 tooltip tooltip-top"
                            :data-tip="t('clip.adjust_overlay_size')"
                        />
                        <span class="text-xs">{{ overlaySize }}%</span>
                    </div>
                </div>
            </div>
            
            <!-- Start Recording Button -->
            <button class="btn btn-theme btn-lg absolute inset-0 m-auto w-5/6 md:w-full max-w-xs lg:max-w-md" @click="startRecording" v-if="!isRecording && !isSaving && !isPaused">
                <p class="clip-text text-xs md:text-sm lg:text-xl">
                    {{ t('clip.record_start') }}
                </p>
                <i class="iconoir-play-solid text-lg md:text-xl lg:text-2xl"></i>
            </button>
            
            <!-- Paused State Button -->
            <button class="btn btn-warning btn-lg absolute inset-0 m-auto w-5/6 md:w-full max-w-xs lg:max-w-md" v-if="isPaused && !isSaving">
                <p class="clip-text text-xs md:text-sm lg:text-xl">
                    {{ t('paused') }}
                </p>
                <i class="iconoir-pause-solid text-lg md:text-xl lg:text-2xl"></i>
            </button>
        </div>
        
        <!-- Recording Controls -->
        <div v-if="isRecording || isPaused" class="flex justify-center mt-2 items-center">
            <!-- Screen Share Toggle -->
            <button 
                class="btn btn-theme btn-md px-4 py-2 clip-text mx-2 my-4 transition-all duration-300 tooltip tooltip-top"
                :class="isScreenSharing ? 'btn-error' : ''"
                :data-tip="isScreenSharing ? t('clip.stop_screen_share') : t('clip.start_screen_share')"
                @click="toggleScreenShare"
            >
                <i class="text-2xl" :class="!isScreenSharing ? 'iconoir-multi-mac-os-window' : 'iconoir-window-xmark'"></i>
            </button>
            
            <!-- Audio Controls -->
            <button 
                class="btn btn-theme btn-md px-4 py-2 clip-text mx-2 my-4 tooltip tooltip-top"
                :class="isWebcamAudioMuted ? 'btn-success' : 'btn-error'"
                :data-tip="isWebcamAudioMuted ? t('clip.unmute_webcam') : t('clip.mute_webcam')"
                @click="toggleWebcamAudio"
            >
                <i class="text-2xl" :class="isWebcamAudioMuted ? 'iconoir-microphone-mute' : 'iconoir-microphone'"></i>
            </button>
            
            <button 
                class="btn btn-theme btn-md px-4 py-2 clip-text mx-2 my-4 tooltip tooltip-top"
                :class="isScreenAudioMuted ? 'btn-success' : 'btn-error'"
                :data-tip="isScreenAudioMuted ? t('clip.unmute_screen') : t('clip.mute_screen')"
                @click="toggleScreenAudio"
                v-if="isScreenSharing"
            >
                <i class="text-2xl" :class="isScreenAudioMuted ? 'iconoir-sound-off' : 'iconoir-sound-high'"></i>
            </button>
            
            <!-- Pause/Resume -->
            <button 
                class="btn btn-theme btn-md px-4 py-2 clip-text mx-2 my-4 tooltip tooltip-top" 
                :data-tip="isPaused ? t('clip.resume_recording') : t('clip.pause_recording')"
                @click="togglePause"
            >
                <i class="text-2xl" :class="!isPaused ? 'iconoir-pause-solid' : 'iconoir-play-solid'"></i>
            </button>
            
            <!-- Stop Recording -->
            <button 
                class="btn btn-theme btn-md px-4 py-2 clip-text mx-2 my-4 tooltip tooltip-top" 
                :data-tip="t('clip.stop_recording')"
                @click="stopRecording"
            >
                <i class="text-lg iconoir-stop-solid"></i>
            </button>
            
            <!-- Timer -->
            <span 
                class="shadow-brutal rounded-md bg-white px-4 py-3 clip-text mx-2 my-4 border border-black hover:border-white hover:bg-black hover:text-white tooltip tooltip-top"
                :data-tip="t('clip.recording_time')"
            >
                {{ Math.floor(elapsedTime / 60).toString().padStart(2, '0') }}:{{ (elapsedTime % 60).toString().padStart(2, '0') }}
            </span>
        </div>
        
        <!-- Save Modal -->
        <div 
        v-if="createModal.show" 
        class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 w-full z-[1000]">
            <div class="p-5 w-full">
                <CreateClipCard 
                    v-if="moduleType === 'clip'"
                    :video-blob="createModal.blob" 
                    @close="closeModal"
                />
                <CreateCommentCard 
                    v-else-if="moduleType === 'clip_comment'"
                    :video-blob="createModal.blob" 
                    :clip-id="clipId"
                    @close="closeModal"
                    @saved="handleCommentSaved"
                />
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue';
import { useI18n } from 'vue-i18n';
import { useAuthStore } from '@/stores/auth';
import CreateClipCard from '@/components/clip/CreateClipCard.vue';
import CreateCommentCard from '@/components/clip/CreateCommentCard.vue';

const { t } = useI18n();
const authStore = useAuthStore();

// Component props
const props = defineProps({
    isModal: {
        type: Boolean,
        default: false
    },
    moduleType: {
        type: String,
        default: 'clip'
    },
    clipId: {
        type: Number,
        default: null
    }
});

const emit = defineEmits(['close', 'recordCompleted']);

// Refs for DOM elements
const recordingCanvas = ref(null);
const webcamVideo = ref(null);
const screenVideo = ref(null);

// Recording state
const isRecording = ref(false);
const isPaused = ref(false);
const isSaving = ref(false);
const isScreenSharing = ref(false);

// Audio control state
const isWebcamAudioMuted = ref(false);
const isScreenAudioMuted = ref(true); // Default to muted as requested

// Streams and recording
const webcamStream = ref(null);
const screenStream = ref(null);
const canvasStream = ref(null);
const mediaRecorder = ref(null);
const recordedChunks = ref([]);

// Canvas and rendering
const canvasContext = ref(null);
const animationFrameId = ref(null);
const renderIntervalId = ref(null); // For screen sharing when tab is inactive

// Overlay settings
const overlayPosition = ref('bottom-right');
const overlaySize = ref(20); // Percentage of screen width

// Timer
const elapsedTime = ref(0);
const maxRecordingTime = ref(300); // 5 minutes
let timerInterval = null;

// Tab visibility handling for screen sharing
const isTabVisible = ref(true);
const visibilityChangeHandler = () => {
    isTabVisible.value = !document.hidden;
    console.log('Tab visibility changed:', isTabVisible.value ? 'visible' : 'hidden');
    
    if (canvasStream.value && isScreenSharing.value) {
        const videoTrack = canvasStream.value.getVideoTracks()[0];
        if (videoTrack && videoTrack.applyConstraints) {
            const frameRate = isTabVisible.value ? 30 : 15; // Reduce frame rate when hidden
            videoTrack.applyConstraints({
                frameRate: { ideal: frameRate, max: frameRate }
            }).catch(err => console.log('Frame rate adjustment failed:', err));
        }
    }
    
    if (isScreenSharing.value && !isTabVisible.value) {
        console.log('🔄 Tab hidden during screen sharing - reducing rendering frequency');
    } else if (isScreenSharing.value && isTabVisible.value) {
        console.log('✅ Tab visible again - restoring normal rendering frequency');
    }
};

// Modal state
const createModal = ref({
    show: false,
    blob: null
});

// Canvas dimensions (Full HD)
const CANVAS_WIDTH = 1920;
const CANVAS_HEIGHT = 1080;

// Browser compatibility check
const checkBrowserCompatibility = () => {
    if (!navigator.mediaDevices || !window.MediaRecorder) {
        Toast.fire({ 
            icon: 'error', 
            title: t('clip.browser_not_supported'), 
            timer: 3000 
        });
        return false;
    }
    
    // Check for canvas.captureStream support
    const testCanvas = document.createElement('canvas');
    if (!testCanvas.captureStream && !testCanvas.mozCaptureStream) {
        Toast.fire({ 
            icon: 'error', 
            title: t('clip.canvas_recording_not_supported'), 
            timer: 3000 
        });
        return false;
    }
    
    return true;
};

// Get optimal MIME type for recording
const getSupportedMimeType = () => {
    const types = [
        'video/mp4;codecs=avc1.42E01E,mp4a.40.2', // H.264 + AAC (preferred for MP4)
        'video/webm;codecs=vp9,opus',              // VP9 + Opus (fallback)
        'video/webm;codecs=vp8,opus',              // VP8 + Opus
        'video/webm',                              // Default WebM
        'video/mp4'                                // Default MP4
    ];
    
    for (const type of types) {
        if (MediaRecorder.isTypeSupported(type)) {
            console.log('Using MIME type:', type);
            return type;
        }
    }
    
    console.log('No supported MIME type found, using browser default');
    return '';
};

// Initialize canvas
const initializeCanvas = () => {
    if (!recordingCanvas.value) {
        console.error('Canvas element not found in DOM');
        return false;
    }
    
    const canvas = recordingCanvas.value;
    canvas.width = CANVAS_WIDTH;
    canvas.height = CANVAS_HEIGHT;
    
    canvasContext.value = canvas.getContext('2d');
    
    if (!canvasContext.value) {
        console.error('Failed to get canvas 2D context');
        return false;
    }
    
    // Set canvas style for proper display
    canvas.style.width = '100%';
    canvas.style.height = '100%';
    canvas.style.objectFit = 'contain';
    
    console.log('Canvas initialized successfully:', CANVAS_WIDTH + 'x' + CANVAS_HEIGHT);
    return true;
};

// Start webcam stream
const startWebcamStream = async () => {
    try {
        webcamStream.value = await navigator.mediaDevices.getUserMedia({
            video: {
                width: { ideal: 1280, max: 1920 },
                height: { ideal: 720, max: 1080 },
                frameRate: { ideal: 30 }
            },
            audio: {
                echoCancellation: true,
                noiseSuppression: true,
                autoGainControl: true
            }
        });
        
        if (webcamVideo.value) {
            webcamVideo.value.srcObject = webcamStream.value;
            await webcamVideo.value.play();
        }
        
        console.log('Webcam stream started successfully');
        return true;
    } catch (error) {
        console.error('Error starting webcam:', error);
        Toast.fire({ 
            icon: 'error', 
            title: t('clip.webcam_access_error'), 
            timer: 3000 
        });
        return false;
    }
};

// Start screen sharing
const startScreenSharing = async () => {
    try {
        // Request screen sharing with optimal settings
        screenStream.value = await navigator.mediaDevices.getDisplayMedia({
            video: {
                width: { ideal: 1920, max: 3840 },
                height: { ideal: 1080, max: 2160 },
                frameRate: { ideal: 30, max: 30 },
                cursor: 'always'
            },
            audio: {
                echoCancellation: false,
                noiseSuppression: false,
                autoGainControl: false
            }
        });
        
        if (screenVideo.value) {
            screenVideo.value.srcObject = screenStream.value;
            await screenVideo.value.play();
        }
        
        // Handle screen share ending
        const videoTrack = screenStream.value.getVideoTracks()[0];
        videoTrack.onended = () => {
            console.log('Screen sharing ended by user from browser UI');
            stopScreenSharing();
        };
        
        videoTrack.addEventListener('mute', () => {
            console.log('Screen track muted - may indicate tab switching or system pause');
        });
        
        videoTrack.addEventListener('unmute', () => {
            console.log('Screen track unmuted - tab/system became active again');
        });
        
        // Monitor track state changes
        videoTrack.addEventListener('ended', () => {
            console.log('Screen track ended - cleaning up screen sharing');
            stopScreenSharing();
        });
        
        screenStream.value.addEventListener('removetrack', (event) => {
            console.log('Track removed from screen stream:', event.track.kind);
            if (event.track.kind === 'video') {
                stopScreenSharing();
            }
        });
        
        isScreenSharing.value = true;
        console.log('Screen sharing started successfully');
        
        // Restart rendering loop with setInterval for screen sharing
        if (isRecording.value) {
            startRenderingLoop();
        }
        
        return true;
    } catch (error) {
        console.error('Error starting screen sharing:', error);
        
        isScreenSharing.value = false;
        
        if (error.name === 'NotAllowedError') {
            Toast.fire({ 
                icon: 'info', 
                title: t('clip.screen_share_cancelled'), 
                timer: 3000 
            });
        } else {
            Toast.fire({ 
                icon: 'error', 
                title: t('clip.screen_share_error'), 
                timer: 3000 
            });
        }
        return false;
    }
};

// Stop screen sharing
const stopScreenSharing = () => {
    console.log('Stopping screen sharing...');
    
    // Stop all tracks in the screen stream
    if (screenStream.value) {
        screenStream.value.getTracks().forEach(track => {
            console.log('Stopping screen track:', track.kind, track.readyState);
            track.stop();
        });
        screenStream.value = null;
    }
    
    // Clear video element
    if (screenVideo.value) {
        screenVideo.value.srcObject = null;
    }
    
    // Update state
    isScreenSharing.value = false;
    
    // Restart rendering loop with requestAnimationFrame for webcam only
    if (isRecording.value) {
        startRenderingLoop();
    }
    
    console.log('Screen sharing stopped successfully');
};

// Toggle screen sharing with smooth transition
const toggleScreenShare = async () => {
    if (isScreenSharing.value) {
        stopScreenSharing();
    } else {
        await startScreenSharing();
    }
};

// Calculate overlay position based on settings
const calculateOverlayPosition = () => {
    const overlayWidth = (CANVAS_WIDTH * overlaySize.value) / 100;
    
    // Calculate proper aspect ratio based on actual webcam video dimensions
    let overlayHeight = overlayWidth * 0.75; // Default 4:3 fallback
    
    if (webcamVideo.value && webcamVideo.value.videoWidth && webcamVideo.value.videoHeight) {
        const webcamAspectRatio = webcamVideo.value.videoHeight / webcamVideo.value.videoWidth;
        overlayHeight = overlayWidth * webcamAspectRatio;
    }
    
    const margin = 20;
    
    let x, y;
    
    switch (overlayPosition.value) {
        case 'top-left':
            x = margin;
            y = margin;
            break;
        case 'top-right':
            x = CANVAS_WIDTH - overlayWidth - margin;
            y = margin;
            break;
        case 'bottom-left':
            x = margin;
            y = CANVAS_HEIGHT - overlayHeight - margin;
            break;
        case 'bottom-right':
        default:
            x = CANVAS_WIDTH - overlayWidth - margin;
            y = CANVAS_HEIGHT - overlayHeight - margin;
            break;
    }
    
    return { x, y, width: overlayWidth, height: overlayHeight };
};

// Render frame to canvas with proper timing mechanism
const renderFrame = () => {
    if (!canvasContext.value || isPaused.value) {
        return;
    }
    
    const ctx = canvasContext.value;
    
    // Clear canvas
    ctx.clearRect(0, 0, CANVAS_WIDTH, CANVAS_HEIGHT);
    
    const isScreenStreamHealthy = isScreenSharing.value && 
                                  screenVideo.value && 
                                  screenVideo.value.readyState >= 2 &&
                                  screenStream.value &&
                                  screenStream.value.getVideoTracks().length > 0 &&
                                  screenStream.value.getVideoTracks()[0].readyState === 'live';
    
    if (isScreenStreamHealthy) {
        
        try {
            ctx.drawImage(screenVideo.value, 0, 0, CANVAS_WIDTH, CANVAS_HEIGHT);
        } catch (error) {
            console.warn('Failed to draw screen video:', error);
            // Fallback to black background
            ctx.fillStyle = '#000000';
            ctx.fillRect(0, 0, CANVAS_WIDTH, CANVAS_HEIGHT);
        }
        
        // Draw webcam overlay
        if (webcamVideo.value && webcamVideo.value.readyState >= 2) {
            const overlay = calculateOverlayPosition();
            
            try {
                // Calculate object-fit cover behavior for webcam
                const videoAspectRatio = webcamVideo.value.videoWidth / webcamVideo.value.videoHeight;
                const overlayAspectRatio = overlay.width / overlay.height;
                
                let sourceX = 0, sourceY = 0, sourceWidth = webcamVideo.value.videoWidth, sourceHeight = webcamVideo.value.videoHeight;
                
                if (videoAspectRatio > overlayAspectRatio) {
                    // Video is wider than overlay - crop sides
                    sourceWidth = webcamVideo.value.videoHeight * overlayAspectRatio;
                    sourceX = (webcamVideo.value.videoWidth - sourceWidth) / 2;
                } else if (videoAspectRatio < overlayAspectRatio) {
                    // Video is taller than overlay - crop top/bottom
                    sourceHeight = webcamVideo.value.videoWidth / overlayAspectRatio;
                    sourceY = (webcamVideo.value.videoHeight - sourceHeight) / 2;
                }
                
                // Add rounded corners and border to overlay
                ctx.save();
                ctx.beginPath();
                ctx.roundRect(overlay.x, overlay.y, overlay.width, overlay.height, 10);
                ctx.clip();
                
                // Draw webcam video with calculated crop
                ctx.drawImage(
                    webcamVideo.value,
                    sourceX, sourceY, sourceWidth, sourceHeight, // Source rectangle (cropped)
                    overlay.x, overlay.y, overlay.width, overlay.height // Destination rectangle
                );
                
                ctx.restore();
                
                // Add border around webcam overlay
                ctx.strokeStyle = '#FCD34D'; // Yellow border
                ctx.lineWidth = 3;
                ctx.beginPath();
                ctx.roundRect(overlay.x, overlay.y, overlay.width, overlay.height, 10);
                ctx.stroke();
                
            } catch (error) {
                console.warn('Failed to draw webcam overlay:', error);
            }
        }
        
        // Screen sharing indicator
        // if (!isTabVisible.value) {
        //     ctx.save();
        //     ctx.fillStyle = 'rgba(0, 0, 0, 0.7)';
        //     ctx.fillRect(0, 0, CANVAS_WIDTH, 100);
            
        //     ctx.fillStyle = '#FCD34D';
        //     ctx.font = 'bold 32px Arial';
        //     ctx.textAlign = 'center';
        //     ctx.fillText('🔄 Recording continues in background', CANVAS_WIDTH / 2, 50);
        //     ctx.restore();
        // }
        
    } else if (webcamVideo.value && webcamVideo.value.readyState >= 2) {
        // Webcam only mode
        try {
            ctx.drawImage(webcamVideo.value, 0, 0, CANVAS_WIDTH, CANVAS_HEIGHT);
        } catch (error) {
            console.warn('Failed to draw webcam video:', error);
                            drawPlaceholder(ctx, t('clip.webcam_error'));
        }
    } else {
        // No video available - show placeholder
                        drawPlaceholder(ctx, t('clip.waiting_for_video'));
    }
};

// Helper function for placeholder
const drawPlaceholder = (ctx, message) => {
    ctx.fillStyle = '#1F2937';
    ctx.fillRect(0, 0, CANVAS_WIDTH, CANVAS_HEIGHT);
    
    ctx.fillStyle = '#9CA3AF';
    ctx.font = '48px Arial';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText(message, CANVAS_WIDTH / 2, CANVAS_HEIGHT / 2);
};

// Start rendering loop with proper timing mechanism
const startRenderingLoop = () => {
    stopRenderingLoop(); // Clean up any existing loops
    
    if (isScreenSharing.value) {
        // Use setInterval for screen sharing to prevent tab throttling
        console.log('Starting screen sharing render loop with setInterval');
        renderIntervalId.value = setInterval(renderFrame, 33); // ~30 FPS
    } else {
        // Use requestAnimationFrame for webcam only
        console.log('Starting webcam render loop with requestAnimationFrame');
        const loop = () => {
            renderFrame();
            animationFrameId.value = requestAnimationFrame(loop);
        };
        animationFrameId.value = requestAnimationFrame(loop);
    }
};

// Stop rendering loop
const stopRenderingLoop = () => {
    if (animationFrameId.value) {
        cancelAnimationFrame(animationFrameId.value);
        animationFrameId.value = null;
    }
    if (renderIntervalId.value) {
        clearInterval(renderIntervalId.value);
        renderIntervalId.value = null;
    }
};

// Create mixed audio stream
const createMixedAudioStream = () => {
    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
    const destination = audioContext.createMediaStreamDestination();
    
    // Add webcam audio if not muted
    if (webcamStream.value && !isWebcamAudioMuted.value) {
        const webcamAudioTracks = webcamStream.value.getAudioTracks();
        if (webcamAudioTracks.length > 0) {
            const webcamSource = audioContext.createMediaStreamSource(new MediaStream(webcamAudioTracks));
            webcamSource.connect(destination);
        }
    }
    
    // Add screen audio if screen sharing and not muted
    if (screenStream.value && isScreenSharing.value && !isScreenAudioMuted.value) {
        const screenAudioTracks = screenStream.value.getAudioTracks();
        if (screenAudioTracks.length > 0) {
            const screenSource = audioContext.createMediaStreamSource(new MediaStream(screenAudioTracks));
            screenSource.connect(destination);
        }
    }
    
    return destination.stream;
};

// Start recording
const startRecording = async () => {
    if (!checkBrowserCompatibility()) return;
    
    try {
        // Set recording state first to render the canvas
        isRecording.value = true;
        
        // Wait for DOM to update and canvas to be rendered
        await nextTick();
        
        // Initialize canvas
        const canvasInitialized = initializeCanvas();
        
        // Verify canvas is available and initialized
        if (!canvasInitialized || !recordingCanvas.value) {
            throw new Error('Canvas element not available after DOM update');
        }
        
        // Start webcam stream
        const webcamStarted = await startWebcamStream();
        if (!webcamStarted) {
            isRecording.value = false;
            return;
        }
        
        // Wait for video to be ready
        await nextTick();
        
        // Start rendering loop
        startRenderingLoop();
        
        // Create canvas stream
        const canvas = recordingCanvas.value;
        
        // Check if captureStream is available
        if (!canvas.captureStream && !canvas.mozCaptureStream) {
            throw new Error('Canvas captureStream not supported in this browser');
        }
        
        // Use the appropriate method for the browser
        const captureMethod = canvas.captureStream || canvas.mozCaptureStream;
        canvasStream.value = captureMethod.call(canvas, 30); // 30 FPS
        
        console.log('Canvas stream created successfully');
        
        // Create mixed audio stream
        const audioStream = createMixedAudioStream();
        
        // Combine video and audio streams
        const combinedStream = new MediaStream([
            ...canvasStream.value.getVideoTracks(),
            ...audioStream.getAudioTracks()
        ]);
        
        // Setup MediaRecorder
        const mimeType = getSupportedMimeType();
        const options = {
            videoBitsPerSecond: 2500000, // 2.5 Mbps
            audioBitsPerSecond: 128000   // 128 kbps
        };
        
        if (mimeType) {
            options.mimeType = mimeType;
        }
        
        mediaRecorder.value = new MediaRecorder(combinedStream, options);
        
        // Setup event handlers
        mediaRecorder.value.ondataavailable = (event) => {
            if (event.data && event.data.size > 0) {
                recordedChunks.value.push(event.data);
            }
        };
        
        mediaRecorder.value.onstop = () => {
            console.log('Recording stopped, processing video...');
            processRecordedVideo();
        };
        
        mediaRecorder.value.onerror = (event) => {
            console.error('MediaRecorder error:', event);
            Toast.fire({ 
                icon: 'error', 
                title: t('clip.recording_error'), 
                timer: 3000 
            });
        };
        
        // Start recording
        mediaRecorder.value.start(250); // 250ms chunks
        
        // Start timer
        startTimer();
        
        console.log('Recording started successfully');
        
    } catch (error) {
        console.error('Error starting recording:', error);
        
        // Reset recording state on error
        isRecording.value = false;
        isPaused.value = false;
        
        // Clean up any partially initialized resources
        stopRenderingLoop();
        resetTimer();
        
        Toast.fire({ 
            icon: 'error', 
            title: t('clip.recording_start_error'), 
            timer: 3000 
        });
    }
};

// Stop recording
const stopRecording = async () => {
    try {
        if (mediaRecorder.value && mediaRecorder.value.state !== 'inactive') {
            mediaRecorder.value.stop();
        }
        
        isRecording.value = false;
        isPaused.value = false;
        
        stopTimer();
        stopRenderingLoop();
        
        console.log('Recording stopped');
        
    } catch (error) {
        console.error('Error stopping recording:', error);
        Toast.fire({ 
            icon: 'error', 
            title: t('clip.recording_stop_error'), 
            timer: 3000 
        });
    }
};

// Toggle pause/resume
const togglePause = () => {
    if (!mediaRecorder.value) return;
    
    if (isPaused.value) {
        mediaRecorder.value.resume();
        isPaused.value = false;
        startTimer();
        console.log('Recording resumed');
    } else {
        mediaRecorder.value.pause();
        isPaused.value = true;
        stopTimer();
        console.log('Recording paused');
    }
};

// Process recorded video
const processRecordedVideo = async () => {
    try {
        isSaving.value = true;
        
        if (recordedChunks.value.length === 0) {
            throw new Error('No recorded data available');
        }
        
        // Create blob from recorded chunks
        const mimeType = getSupportedMimeType() || 'video/mp4';
        const videoBlob = new Blob(recordedChunks.value, { type: mimeType });
        
        console.log('Video processed:', {
            size: videoBlob.size,
            type: videoBlob.type,
            duration: elapsedTime.value
        });
        
        // Show create modal
        createModal.value = {
            show: true,
            blob: videoBlob
        };
        
        // Clear recorded chunks
        recordedChunks.value = [];
        
    } catch (error) {
        console.error('Error processing video:', error);
        Toast.fire({ 
            icon: 'error', 
            title: t('clip.processing_error'), 
            timer: 3000 
        });
    } finally {
        isSaving.value = false;
    }
};

// Audio control functions
const toggleWebcamAudio = () => {
    isWebcamAudioMuted.value = !isWebcamAudioMuted.value;
    
    if (webcamStream.value) {
        webcamStream.value.getAudioTracks().forEach(track => {
            track.enabled = !isWebcamAudioMuted.value;
        });
    }
    
    console.log('Webcam audio:', isWebcamAudioMuted.value ? 'muted' : 'unmuted');
};

const toggleScreenAudio = () => {
    isScreenAudioMuted.value = !isScreenAudioMuted.value;
    
    if (screenStream.value) {
        screenStream.value.getAudioTracks().forEach(track => {
            track.enabled = !isScreenAudioMuted.value;
        });
    }
    
    console.log('Screen audio:', isScreenAudioMuted.value ? 'muted' : 'unmuted');
};

// Overlay control functions
const updateOverlayPosition = () => {
    console.log('Overlay position changed to:', overlayPosition.value);
};

const updateOverlaySize = () => {
    console.log('Overlay size changed to:', overlaySize.value + '%');
};

// Timer functions
const startTimer = () => {
    timerInterval = setInterval(() => {
        elapsedTime.value++;
        if (elapsedTime.value >= maxRecordingTime.value) {
            stopRecording();
            Toast.fire({
                icon: 'info',
                title: t('clip.max_recording_time_reached'),
                timer: 3000
            });
        }
    }, 1000);
};

const stopTimer = () => {
    if (timerInterval) {
        clearInterval(timerInterval);
        timerInterval = null;
    }
};

const resetTimer = () => {
    elapsedTime.value = 0;
    stopTimer();
};

// Modal functions
const closeModal = () => {
    createModal.value = {
        show: false,
        blob: null
    };
    
    // Reset component state
    resetComponent();
    
    // Emit close if this is a modal
    if (props.isModal) {
        emit('close');
    }
};

const handleCommentSaved = () => {
    console.log('Comment saved successfully, closing modal');
    
    // Close modal immediately
    createModal.value = {
        show: false,
        blob: null
    };
    
    // Reset component state
    resetComponent();
    
    // Emit recordCompleted for comment mode to notify parent
    if (props.moduleType === 'clip_comment') {
        emit('recordCompleted', null);
    }
    
    // If this is a modal, also emit close
    if (props.isModal) {
        emit('close');
    }
};

// Reset component to initial state
const resetComponent = () => {
    // Stop all streams
    if (webcamStream.value) {
        webcamStream.value.getTracks().forEach(track => track.stop());
        webcamStream.value = null;
    }
    
    if (screenStream.value) {
        screenStream.value.getTracks().forEach(track => track.stop());
        screenStream.value = null;
    }
    
    if (canvasStream.value) {
        canvasStream.value.getTracks().forEach(track => track.stop());
        canvasStream.value = null;
    }
    
    // Reset state
    isRecording.value = false;
    isPaused.value = false;
    isSaving.value = false;
    isScreenSharing.value = false;
    isWebcamAudioMuted.value = false;
    isScreenAudioMuted.value = true;
    
    // Clear recorded data
    recordedChunks.value = [];
    
    // Reset timer
    resetTimer();
    
    // Stop rendering
    stopRenderingLoop();
    
    console.log('Component reset to initial state');
};

// Add roundRect polyfill for older browsers
const addRoundRectPolyfill = () => {
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

// Lifecycle hooks
onMounted(() => {
    addRoundRectPolyfill();
    
    // Visibility change listener
    document.addEventListener('visibilitychange', visibilityChangeHandler);
    
    console.log('Record component mounted');
});

onUnmounted(() => {
    // Remove visibility change listener
    document.removeEventListener('visibilitychange', visibilityChangeHandler);
    
    resetComponent();
    console.log('Record component unmounted');
});
</script>

<style scoped>
/* Smooth transitions for UI elements */
.transition-all {
    transition: all 0.3s ease-in-out;
}

/* Canvas container styling */
canvas {
    background-color: #000;
    border-radius: 0.375rem;
}

/* Button hover effects */
.btn:hover {
    transform: translateY(-1px);
}

/* Recording indicator animation */
@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style> 