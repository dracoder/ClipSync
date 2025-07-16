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
                <canvas
                v-if="isScreenSharing"
                ref="compositeCanvas"
                class="w-full h-full object-contain rounded-md cursor-move"
                :style="{ display: recordingVideo ? 'block' : 'none' }"
                ></canvas>
                <video
                    v-if="isScreenSharing"
                    id="screen-video"
                    class="w-full h-full object-contain rounded-md"
                    autoplay
                    playsinline
                    muted
                    ref="screenElement"
                />
                <video
                    id="video-element"
                    :class="[
                        'rounded-md',
                        isScreenSharing ? 'absolute bottom-4 right-4 w-48 h-36 object-cover shadow-lg border-2 border-yellow-400' : 'w-full h-full object-contain'
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
        
        <!-- Save Clip Modal -->
        <div v-if="createModal.show" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 w-full z-[1000]">
            <div class="p-5 w-full">
                <CreateClipCard :video-blob="createModal.blob" @close="close"/>
            </div>
        </div>
    </div>
    <div v-if="recordingVideo && isScreenSharing" class="absolute top-2 right-2 bg-black bg-opacity-50 p-2 rounded-md z-50">
        <div class="flex flex-col gap-2">
            <button @click="showWebcamOverlay = !showWebcamOverlay" class="text-white hover:text-yellow-400">
                <i :class="showWebcamOverlay ? 'iconoir-eye' : 'iconoir-eye-off'" class="text-lg"></i>
            </button>
            
            <div class="flex flex-col">
                <button @click="webcamOverlayPosition = 'top-left'" class="text-white hover:text-yellow-400" :class="{'text-yellow-400': webcamOverlayPosition === 'top-left'}">
                    <i class="iconoir-corner-top-left text-lg"></i>
                </button>
                <button @click="webcamOverlayPosition = 'top-right'" class="text-white hover:text-yellow-400" :class="{'text-yellow-400': webcamOverlayPosition === 'top-right'}">
                    <i class="iconoir-corner-top-right text-lg"></i>
                </button>
                <button @click="webcamOverlayPosition = 'bottom-left'" class="text-white hover:text-yellow-400" :class="{'text-yellow-400': webcamOverlayPosition === 'bottom-left'}">
                    <i class="iconoir-corner-bottom-left text-lg"></i>
                </button>
                <button @click="webcamOverlayPosition = 'bottom-right'" class="text-white hover:text-yellow-400" :class="{'text-yellow-400': webcamOverlayPosition === 'bottom-right'}">
                    <i class="iconoir-corner-bottom-right text-lg"></i>
                </button>
            </div>
            
            <div class="flex flex-col">
                <button @click="webcamOverlaySize = Math.min(webcamOverlaySize + 5, 40)" class="text-white hover:text-yellow-400">
                    <i class="iconoir-zoom-in text-lg"></i>
                </button>
                <button @click="webcamOverlaySize = Math.max(webcamOverlaySize - 5, 10)" class="text-white hover:text-yellow-400">
                    <i class="iconoir-zoom-out text-lg"></i>
                </button>
            </div>

            <button @click="webcamOverlayX = null; webcamOverlayY = null;" class="text-white hover:text-yellow-400 mt-2">
                <i class="iconoir-reset text-lg"></i>
            </button>
        </div>
    </div>

</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
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
const screenStream = ref(null);
const screenRecorder = ref(null);
const screenChunks = ref([]);
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
const compositeCanvas = ref(null);
const compositeContext = ref(null);
const compositeStream = ref(null);
const compositeRecorder = ref(null);
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
    const types = [
        'video/webm;codecs=vp9,opus',
        'video/webm;codecs=vp8,opus',
        'video/webm',
        'video/mp4'
    ];
    
    for (const type of types) {
        if (MediaRecorder.isTypeSupported(type)) {
            return type;
        }
    }
    
    return '';
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

const startRecording = async () => {
    if (!checkBrowserCompatibility()) return;
    try {
        recordingVideo.value = true;
        currentSessionStartTime.value = Date.now();
        
        videoStream.value = await navigator.mediaDevices.getUserMedia({ 
            video: true, 
            audio: true 
        });
        videoElement.value.srcObject = videoStream.value;

        // Initialize webcam recorder
        let mimeType = getSupportedMimeType();
        mediaRecorder.value = new MediaRecorder(videoStream.value, {
            mimeType: mimeType,
            videoBitsPerSecond: 2500000 // 2.5 Mbps
        });
        // mediaRecorder.value = new MediaRecorder(videoStream.value);
        mediaRecorder.value.ondataavailable = (e) => {
            if (e.data.size > 0) {
                chunks.value.push({
                    timestamp: Date.now(),
                    data: e.data
                });
            }
        };
        mediaRecorder.value.start(250);

        // If screen sharing is active, start screen recording
        if (isScreenSharing.value && screenStream.value) {
            screenElement.value.srcObject = screenStream.value;
            screenRecorder.value = new MediaRecorder(screenStream.value);
            screenRecorder.value.ondataavailable = (e) => {
                if (e.data.size > 0) {
                    screenChunks.value.push({
                        timestamp: Date.now(),
                        data: e.data
                    });
                }
            };
            screenRecorder.value.start(250);
        }

        startTimer();
    } catch (error) {
        recordingVideo.value = false;
        console.error(error);
        if (error.name === 'NotAllowedError') {
            Toast.fire({ 
                icon: 'error', 
                                    title: t('clip.permission_denied'), 
                timer: 3000 
            });
        } else {
            Toast.fire({ 
                icon: 'error', 
                                    title: t('clip.recorder_unavailable'), 
                timer: 3000 
            });
        }
    }
};

const pauseVideoRecording = async () => {
    try {
        if (recordingVideo.value) {
            // Pause both recorders
            await mediaRecorder.value.requestData();
            await mediaRecorder.value.pause();
            
            if (isScreenSharing.value && screenRecorder.value) {
                await screenRecorder.value.requestData();
                await screenRecorder.value.pause();
            }
            
            isPaused.value = true;
            recordingVideo.value = false;
            stopTimer();
        } else {
            await mediaRecorder.value.resume();
            
            if (isScreenSharing.value && screenRecorder.value) {
                await screenRecorder.value.resume();
            }
            
            isPaused.value = false;
            recordingVideo.value = true;
            startTimer();
        }
    } catch (error) {
        console.error(error);
        recordingVideo.value = false;
        isPaused.value = false;
        Toast.fire({ icon: 'error', title: t('something_went_wrong'), timer: 3000 });
    }
};

const stopVideoRecording = async () => {
    try {
        // Request final data before stopping recorders
        if (mediaRecorder.value && mediaRecorder.value.state !== 'inactive') {
            mediaRecorder.value.requestData();
        }
        
        if (isScreenSharing.value && compositeRecorder.value && compositeRecorder.value.state !== 'inactive') {
            compositeRecorder.value.requestData();
        }

        // Stop recorders
        if (isScreenSharing.value && compositeRecorder.value) {
            compositeRecorder.value.stop();
        }
        
        if (mediaRecorder.value) {
            mediaRecorder.value.stop();
        }
        
        mediaRecorder.value.onstop = async () => {
            saveVideo();
            stopVideoStream();
            resetTimer();
        };
    } catch (error) {
        console.error(error);
        Toast.fire({ 
            icon: 'error', 
            title: t('something_went_wrong'), 
            timer: 3000 
        });
    } finally {
        recordingVideo.value = false;
        isPaused.value = false;
        isScreenSharing.value = false;
    }
};

const stopVideoStream = () => {
    try {
        if (videoStream.value) {
            videoStream.value.getTracks().forEach(track => track.stop());
            videoStream.value = null;
        }
        if (screenStream.value) {
            screenStream.value.getTracks().forEach(track => track.stop());
            screenStream.value = null;
        }
    } catch (error) {
        console.error(error);
        Toast.fire({ 
            icon: 'error', 
            title: t('something_went_wrong'), 
            timer: 3000 
        });
    }
}

const saveVideo = async () => {
    try {
        isSaving.value = true;
        isProcessing.value = true;

        // Save final sessions
        if (isScreenSharing.value) {
            await saveScreenShareSession();
        }
        await saveWebcamSession();

        let allChunks = [];
        let finalChunks = [];

        // If screen sharing was used and we have composite recording chunks
        if (screenChunks.value && screenChunks.value.length > 0) {
            console.log('Processing recording with screen sharing segments');
            
            // Create a timeline of recording segments
            const timeline = [];
            
            // Add webcam segments to timeline
            webcamSessions.value.forEach(session => {
                timeline.push({
                    startTime: session.startTime,
                    endTime: session.endTime,
                    type: 'webcam',
                    chunks: session.chunks
                });
            });
            
            // Add screen sharing segments to timeline
            screenSessions.value.forEach(session => {
                timeline.push({
                    startTime: session.startTime,
                    endTime: session.endTime,
                    type: 'screen',
                    chunks: session.chunks
                });
            });
            
            // Sort timeline by start time
            timeline.sort((a, b) => a.startTime - b.startTime);
            
            console.log('Recording timeline:', timeline);
            
            // Process all chunks based on their timestamps
            let processedChunks = [];
            
            // Extract webcam chunks data
            let webcamChunks = [];
            chunks.value.forEach(chunk => {
                webcamChunks.push(chunk.data);
            });
            
            // If we have composite recording chunks, combine them with webcam chunks
            // This ensures we get both the picture-in-picture effect and the webcam recording
            if (screenChunks.value.length > 0) {
                console.log('Using composite recording with screen sharing and webcam');
                
                // Extract screen chunks data
                let screenChunksData = screenChunks.value.map(chunk => chunk.data);
                
                // Combine both recordings
                finalChunks = [...screenChunksData, ...webcamChunks];
            } else {
                // Fallback to merging chunks based on timeline if composite recording failed
                console.log('Fallback: Merging chunks based on timeline');
                
                // Web chunks with timestamps
                let webcamChunks = chunks.value.map(chunk => ({
                    timestamp: chunk.timestamp,
                    data: chunk.data,
                    type: 'webcam'
                }));

                // Screen chunks with timestamps
                let screenShareChunks = screenChunks.value ? screenChunks.value.map(chunk => ({
                    timestamp: chunk.timestamp,
                    data: chunk.data,
                    type: 'screen'
                })) : [];

                // Merge ande sort all chunks by timestamp
                allChunks = [...webcamChunks, ...screenShareChunks].sort((a, b) => a.timestamp - b.timestamp);
                
                // Extract just the data for the final blob
                finalChunks = allChunks.map(chunk => chunk.data);
            }
        } else {
            // No screen sharing, just use webcam chunks
            console.log('Using webcam recording only');
            finalChunks = chunks.value.map(chunk => chunk.data);
        }
        
        // Create final blob with proper MIME type and extension
        const mimeType = getSupportedMimeType();
        // Ensure we have a valid MIME type with proper extension mapping
        const finalMimeType = mimeType || "video/mp4";
        
        // Extract the extension from the MIME type
        let extension = 'mp4';
        if (finalMimeType.includes('webm')) {
            extension = 'webm';
        } else if (finalMimeType.includes('mp4')) {
            extension = 'mp4';
        }
        
        // Create the blob with the proper MIME type
        videoBlob.value = new Blob(finalChunks, { type: finalMimeType });
        
        // Create a proper File object from the Blob with filename and extension
        // This ensures FFMpeg can properly identify the file format
        const filename = `recording.${extension}`;
        videoBlob.value = new File([videoBlob.value], filename, { 
            type: finalMimeType,
            lastModified: new Date().getTime()
        });
        
        console.log('Video file created with type:', finalMimeType, 'and name:', filename);
        
        openCreateModal();
        showSaveModal.value = true;

        // Clear all chunks and sessions
        chunks.value = [];
        screenChunks.value = [];
        screenSessions.value = [];
        webcamSessions.value = [];
    } catch (error) {
        console.error(error);
        close();
        isSaving.value = false;
        Toast.fire({ 
            icon: 'error', 
            title: t('something_went_wrong'), 
            timer: 3000 
        });
    } finally {
        isProcessing.value = false;
    }
};

const saveWebcamSession = async () => {
    return new Promise((resolve) => {
        if (mediaRecorder.value && mediaRecorder.value.state !== 'inactive') {
            mediaRecorder.value.requestData();
        }
        webcamSessions.value.push({
            startTime: currentSessionStartTime.value,
            endTime: Date.now(),
            chunks: [...chunks.value]
        });
        resolve();
    });
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
    screenSessions.value = [];
    webcamSessions.value = [];
    chunks.value = [];
    screenChunks.value = [];
    currentSessionStartTime.value = null;
    stopVideoStream();
}

// const saveVideo = async () => {
//     videoBlob.value = new Blob(chunks.value, { type: "video/webm" });
    
//     if (!ffmpeg.isLoaded()) {
//         await ffmpeg.load();
//     }
    
//     const fileName = 'recorded_video.webm';
//     ffmpeg.FS('writeFile', fileName, await fetchFile(videoBlob.value));
//     await ffmpeg.run('-i', fileName, 'output.mp4'); // Convert to mp4 format
//     const data = ffmpeg.FS('readFile', 'output.mp4');

//     const convertedBlob = new Blob([data.buffer], { type: 'video/mp4' });
    
//     showSaveModal.value = true;
//     videoBlob.value = convertedBlob;
// }

const checkScreenCaptureSupport = () => {
  if (!navigator.mediaDevices || !navigator.mediaDevices.getDisplayMedia) {
    alert(t('screen_capture_not_supported'));
    return false;
  }
  return true;
};

const startScreenShare = async () => {
  if (!checkScreenCaptureSupport()) return;
  
  try {
      if (isScreenSharing.value) {
          // Save current screen session before stopping
          if (recordingVideo.value) {
              let sessionEndTime = Date.now();
              
              if (compositeRecorder.value) {
                  // Request final data before stopping
                  if (compositeRecorder.value.state !== 'inactive') {
                      compositeRecorder.value.requestData();
                  }
                  compositeRecorder.value.stop();
              }
              
              // Save this session with proper timestamps
              screenSessions.value.push({
                  startTime: currentSessionStartTime.value,
                  endTime: sessionEndTime,
                  chunks: [...screenChunks.value]
              });
              
              screenChunks.value = [];
          }

          // Stop screen sharing
          if (screenStream.value) {
              screenStream.value.getTracks().forEach(track => track.stop());
              screenStream.value = null;
          }
          isScreenSharing.value = false;
          
          if (recordingVideo.value) {
              if (screenElement.value) {
                  screenElement.value.srcObject = null;
              }
              if (screenRecorder.value) {
                  screenRecorder.value.stop();
                  screenRecorder.value = null;
              }
          }

          // Composite recorder
          if (compositeRecorder.value) {
              compositeRecorder.value.stop();
              compositeRecorder.value = null;
          }
          if (compositeStream.value) {
              compositeStream.value.getTracks().forEach(track => track.stop());
              compositeStream.value = null;
          }
          
          // Resume normal webcam recording
          if (recordingVideo.value) {
              // Save the current session as a webcam session
              currentSessionStartTime.value = Date.now();
          }
      } else {
          try {
              // Save current webcam session before switching to screen sharing
              if (recordingVideo.value) {
                  await saveWebcamSession();
                  // Reset chunks for new recording session
                  chunks.value = [];
              }
              
              screenStream.value = await navigator.mediaDevices.getDisplayMedia({ 
                  video: {
                      cursor: 'always',
                      displaySurface: 'monitor'
                  },
                  audio: true 
              });
              if (!screenStream.value) {
                  return;
              }
              screenStream.value.getVideoTracks()[0].addEventListener('ended', () => {
                  if (isScreenSharing.value) {
                      startScreenShare();
                  }
              });
              isScreenSharing.value = true;
              currentSessionStartTime.value = Date.now();
              
              if (recordingVideo.value) {
                  // Wait for the DOM to update and screenElement to be available
                  await new Promise(resolve => setTimeout(resolve, 50));
                  
                  if (screenElement.value) {
                      screenElement.value.srcObject = screenStream.value;
                      await new Promise(resolve => {
                          screenElement.value.onloadedmetadata = resolve;
                      });
                  } else {
                      console.error('Screen element not found');
                      Toast.fire({ 
                          icon: 'error', 
                          title: t('something_went_wrong'), 
                          timer: 3000 
                      });
                      return;
                  }
                  setupCompositeRecording();
              }
          } catch (error) {
              console.error(error);
              isScreenSharing.value = false;
              Toast.fire({ 
                  icon: 'error', 
                  title: t('clip.screen_share_error'), 
                  timer: 3000 
              });
          }
      }
  } catch (error) {
      console.error(error);
      isScreenSharing.value = false;
      Toast.fire({ 
          icon: 'error', 
          title: t('clip.screen_share_error'), 
          timer: 3000 
      });
  }
};

const saveScreenShareSession = async () => {
    return new Promise((resolve) => {
        if (compositeRecorder.value && compositeRecorder.value.state !== 'inactive') {
            compositeRecorder.value.requestData();
        }
        
        if (screenRecorder.value && screenRecorder.value.state !== 'inactive') {
            screenRecorder.value.requestData();
        }
        
        // Add a small delay to ensure we get the final chunks
        setTimeout(() => {
            screenSessions.value.push({
                startTime: currentSessionStartTime.value,
                endTime: Date.now(),
                chunks: [...screenChunks.value]
            });
            console.log('Saved screen session with', screenChunks.value.length, 'chunks');
            resolve();
        }, 100);
    });
};

const setupCompositeRecording = () => {
    try {
        // Wait a moment to ensure DOM elements are available
        setTimeout(() => {
            const canvas = compositeCanvas.value;
            if (!canvas) {
                console.error('Canvas element not found');
                Toast.fire({ 
                    icon: 'error', 
                    title: t('something_went_wrong'), 
                    timer: 3000 
                });
                return;
            }
            const ctx = canvas.getContext('2d');
            
            if (!screenElement.value) {
                console.error('Screen element not found');
                Toast.fire({ 
                    icon: 'error', 
                    title: t('something_went_wrong'), 
                    timer: 3000 
                });
                return;
            }
            
            // Ensure video has loaded metadata
            if (!screenElement.value.videoWidth || !screenElement.value.videoHeight) {
                screenElement.value.onloadedmetadata = () => setupCompositeRecording();
                return;
            }
            
            canvas.width = screenElement.value.videoWidth;
            canvas.height = screenElement.value.videoHeight;
            
            compositeContext.value = ctx;
            
            // Stop any existing composite stream
            if (compositeStream.value) {
                compositeStream.value.getTracks().forEach(track => track.stop());
            }
            
            // Create a new stream from the canvas
            compositeStream.value = canvas.captureStream(30); // 30fps for smoother video
            
            // Add audio track from webcam to composite stream
            if (videoStream.value && videoStream.value.getAudioTracks().length > 0) {
                const audioTrack = videoStream.value.getAudioTracks()[0];
                compositeStream.value.addTrack(audioTrack);
            }
            
            // Add audio track from screen sharing if available
            if (screenStream.value && screenStream.value.getAudioTracks().length > 0) {
                const screenAudioTrack = screenStream.value.getAudioTracks()[0];
                compositeStream.value.addTrack(screenAudioTrack);
            }
            
            // Initialize the composite recorder with the same mime type as the webcam recorder
            const mimeType = getSupportedMimeType();
            compositeRecorder.value = new MediaRecorder(compositeStream.value, {
                mimeType: mimeType,
                videoBitsPerSecond: 2500000 // 2.5 Mbps
            });
            
            compositeRecorder.value.ondataavailable = (e) => {
                if (e.data.size > 0) {
                    screenChunks.value.push({
                        timestamp: Date.now(),
                        data: e.data
                    });
                    console.log('Composite recorder data available, chunk size:', e.data.size);
                }
            };

            compositeRecorder.value.start(250);
            requestAnimationFrame(drawComposite);
            
            console.log('Composite recording started successfully');
        }, 100); // Small delay to ensure DOM is updated
    } catch (error) {
        console.error(error);
        Toast.fire({ 
            icon: 'error', 
            title: t('clip.screen_share_error'), 
            timer: 3000 
        });
    }
};

let animationFrameId = null;

const drawComposite = () => {
    if (!isScreenSharing.value || !recordingVideo.value) {
        if (animationFrameId) {
            cancelAnimationFrame(animationFrameId);
            animationFrameId = null;
        }
        return;
    }
    
    let canvas = compositeCanvas.value;
    let ctx = compositeContext.value;
    let screenVideo = screenElement.value;
    let webcamVideo = videoElement.value;
    
    // Check if all required elements are available
    if (!canvas || !ctx || !screenVideo) {
        // Schedule next frame but don't draw anything
        animationFrameId = requestAnimationFrame(drawComposite);
        return;
    }
  
    // Clear canvas and draw screen capture
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.drawImage(screenVideo, 0, 0, canvas.width, canvas.height);
  
    if (showWebcamOverlay.value && webcamVideo) {
        let webcamWidth = canvas.width * (webcamOverlaySize.value / 100);
        let webcamHeight = (webcamWidth * 3) / 4;

        let webcamX, webcamY;

        // Use custom position if dragged, otherwise use preset positions
        if (webcamOverlayX.value !== null && webcamOverlayY.value !== null) {
            webcamX = webcamOverlayX.value;
            webcamY = webcamOverlayY.value;
        } else {
            // Use preset positions based on selected position
            switch (webcamOverlayPosition.value) {
                case 'top-left':
                    webcamX = 20;
                    webcamY = 20;
                    break;
                case 'top-right':
                    webcamX = canvas.width - webcamWidth - 20;
                    webcamY = 20;
                    break;
                case 'bottom-left':
                    webcamX = 20;
                    webcamY = canvas.height - webcamHeight - 20;
                    break;
                case 'bottom-right':
                default:
                    webcamX = canvas.width - webcamWidth - 20;
                    webcamY = canvas.height - webcamHeight - 20;
                    break;
            }
            
            // Initialize custom position with the preset position on first run
            if (webcamOverlayX.value === null) {
                webcamOverlayX.value = webcamX;
            }
            if (webcamOverlayY.value === null) {
                webcamOverlayY.value = webcamY;
            }
        }
  
        // Draw webcam with rounded corners
        ctx.save();
        ctx.beginPath();
        ctx.roundRect(webcamX, webcamY, webcamWidth, webcamHeight, 10);
        ctx.clip();
        ctx.drawImage(webcamVideo, webcamX, webcamY, webcamWidth, webcamHeight);
  
        // Border
        ctx.strokeStyle = '#FCD34D';
        ctx.lineWidth = 2;
        ctx.stroke();
        ctx.restore();
    }
  
    animationFrameId = requestAnimationFrame(drawComposite);
};

// Add these new functions for draggable functionality
const startDrag = (event) => {
    if (!isScreenSharing.value || !recordingVideo.value || !showWebcamOverlay.value) return;
    
    const canvas = compositeCanvas.value;
    if (!canvas) return;
    
    const rect = canvas.getBoundingClientRect();
    const x = (event.clientX - rect.left) * (canvas.width / rect.width);
    const y = (event.clientY - rect.top) * (canvas.height / rect.height);
    
    const webcamWidth = canvas.width * (webcamOverlaySize.value / 100);
    const webcamHeight = (webcamWidth * 3) / 4;
    
    // Check if click is inside webcam overlay
    if (
        x >= webcamOverlayX.value && 
        x <= webcamOverlayX.value + webcamWidth && 
        y >= webcamOverlayY.value && 
        y <= webcamOverlayY.value + webcamHeight
    ) {
        isDragging.value = true;
        dragStartX.value = x - webcamOverlayX.value;
        dragStartY.value = y - webcamOverlayY.value;
    }
};

const doDrag = (event) => {
    if (!isDragging.value) return;
    
    const canvas = compositeCanvas.value;
    if (!canvas) return;
    
    const rect = canvas.getBoundingClientRect();
    const x = (event.clientX - rect.left) * (canvas.width / rect.width);
    const y = (event.clientY - rect.top) * (canvas.height / rect.height);
    
    const webcamWidth = canvas.width * (webcamOverlaySize.value / 100);
    const webcamHeight = (webcamWidth * 3) / 4;
    
    // Calculate new position
    let newX = x - dragStartX.value;
    let newY = y - dragStartY.value;
    
    // Keep webcam within canvas bounds
    newX = Math.max(0, Math.min(canvas.width - webcamWidth, newX));
    newY = Math.max(0, Math.min(canvas.height - webcamHeight, newY));
    
    webcamOverlayX.value = newX;
    webcamOverlayY.value = newY;
};

const endDrag = () => {
    isDragging.value = false;
};

// Add polyfill for roundRect if needed
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

// Add reset position function
const resetWebcamPosition = () => {
    webcamOverlayX.value = null;
    webcamOverlayY.value = null;
};

onMounted(() => {
    setupRoundRectPolyfill();
    
    // Add event listeners for dragging
    const canvas = compositeCanvas.value;
    if (canvas) {
        canvas.addEventListener('mousedown', startDrag);
        window.addEventListener('mousemove', doDrag);
        window.addEventListener('mouseup', endDrag);
    }
});

onUnmounted(() => {
    // Remove event listeners
    const canvas = compositeCanvas.value;
    if (canvas) {
        canvas.removeEventListener('mousedown', startDrag);
        window.removeEventListener('mousemove', doDrag);
        window.removeEventListener('mouseup', endDrag);
    }
    
    // Clean up other resources
    stopTimer();
    stopVideoStream();
    if (animationFrameId) {
        cancelAnimationFrame(animationFrameId);
        animationFrameId = null;
    }
    
    if (mediaRecorder.value && mediaRecorder.value.state !== 'inactive') {
        mediaRecorder.value.stop();
    }
    
    if (screenRecorder.value && screenRecorder.value.state !== 'inactive') {
        screenRecorder.value.stop();
    }
    
    if (compositeRecorder.value && compositeRecorder.value.state !== 'inactive') {
        compositeRecorder.value.stop();
    }
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
