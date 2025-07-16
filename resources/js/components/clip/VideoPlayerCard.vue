<template>
    <div class="w-full shadow-brutal rounded-xl h-full overflow-hidden bg-yellow-200 relative" :class="clip ? 'p-5' : (isModal ? 'p-4' : 'p-0')">
      <button @click="$emit('close')" v-if="isModal" class="absolute top-0 right-2 text-black hover:text-red-600 z-[1000] p-1">
        <i class="iconoir-xmark text-2xl text-center"></i>
      </button>
      <div class="flex items-center justify-between">
        <slot name="header"></slot>
       <div class="flex justify-end gap-2">
          <span @click.stop="copyLink" v-if="clip && clip.slug" class="cursor-pointer">
            <i class="text-xl iconoir-share-android-solid"></i>
          </span>
          <span @click.stop="edit" v-if="authStore.isAuthenticated && authStore.user && clip && clip.user_id == authStore.user.id" class="cursor-pointer">
            <i class="text-2xl iconoir-edit"></i>
          </span>
          <span @click.stop="destroy" v-if="authStore.isAuthenticated && authStore.user && clip && clip.user_id == authStore.user.id" class="cursor-pointer">
            <i class="text-xl iconoir-trash-solid text-error"></i>
          </span>
       </div>
      </div>
      <div class="flex flex-col gap-2 bg-yellow-100 rounded-lg items-center justify-center w-full my-4 relative" @click.stop>
        <button @click.stop="togglePlayPause" class="btn btn-sm clip-text absolute inset-0 m-auto rounded-full w-16 h-16 btn-secondary z-40" :class="(playing ? 'opacity-0 hover:opacity-85' : 'opacity-85') + ' ' + customStyle">
          <i class="text-2xl text-yellow-200" :class="playing ? 'iconoir-pause' : 'iconoir-play-solid'"></i>
        </button>
        <video ref="videoPlayer" class="video-js vjs-default-skin vjs-16-9" @click.prevent></video>
      </div>
      <div class="bg-yellow-100 rounded-lg" v-if="clip && clip.content">
        <slot name="content"></slot>
      </div>
    </div>
    <!-- Edit Data Modal -->
    <div v-if="showSaveModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-[1000] w-full" @click.stop>
      <div class="p-5 w-full">
        <CreateClipCard @close="showSaveModal = false" :edit-mode="clip ? true : false" :record="clip" @saved="emit('onSaved')"/>
      </div>
    </div>
  </template>
  
  <script setup>
  import { ref, onMounted, onBeforeUnmount, computed, watch, nextTick } from 'vue';
  import { useI18n } from 'vue-i18n';
  import videojs from 'video.js';
  import 'video.js/dist/video-js.css';
  import '@videojs/themes/dist/fantasy/index.css';
  import CreateClipCard from '@/components/clip/CreateClipCard.vue';
  import { useClipStore } from '@/stores/clip/clip';
  import { useAuthStore } from '@/stores/auth';
  
  const { t } = useI18n();
  const clipStore = useClipStore();
  const authStore = useAuthStore();

  const props = defineProps({
    videoUrl: {
      type: String,
      required: true
    },
    customStyle: {
      type: String,
      default: ''
    },
    clip: {
      type: Object,
      default: null
    },
    isModal: {
        type: Boolean,
        default: false
    },
    isThumbnail: {
      type: Boolean,
      default: false
    }
  });

  const emit = defineEmits(['onDestroy', 'onSaved', 'viewVideo']);

  const showSaveModal = ref(false);
  const videoPlayer = ref(null);
  const player = ref(null);
  const playing = ref(false);
  const showControls = ref(false);
  const buttonDisplay = ref(true);
  const isFullscreen = ref(false);
  const createdObjectUrl = ref(null);
  
  const videoLink = computed(() => {
    if(!props.videoUrl){
      return null;
    }
    if(typeof props.videoUrl === 'string'){
      return props.videoUrl;
    } else {
      // Clean up previous object URL if it exists
      if (createdObjectUrl.value) {
        URL.revokeObjectURL(createdObjectUrl.value);
      }
      // Create new object URL and store reference
      createdObjectUrl.value = URL.createObjectURL(props.videoUrl);
      return createdObjectUrl.value;
    }
  });

  const initializePlayer = () => {
    if (!videoPlayer.value || !videoLink.value) {
      return;
    }

    // Dispose existing player if it exists
    if (player.value) {
      try {
        player.value.dispose();
      } catch (error) {
        console.warn('Error disposing previous player:', error);
      }
      player.value = null;
    }

    const options = {
      autoplay: false,
      controls: showControls.value,
      preload: 'metadata',
      fluid: true,
      muted: false,
      sources: [
        {
          src: videoLink.value,
          type: 'video/mp4'
        }
      ],
      techOrder: ['html5'],
    };

    try {
      player.value = videojs(videoPlayer.value, options, () => {
        console.log('Player is ready');
      });

      player.value.on('play', () => {
        playing.value = true;
        if(!isFullscreen.value){
          player.value.controls(true);
        }
      });

      player.value.on('pause', () => {
        playing.value = false;
        if(!isFullscreen.value){
          player.value.controls(false);
        }
      });

      player.value.on('error', () => {
        const error = player.value.error();
        console.error('Error playing video:', error);
        // Don't show alert for revoked URLs as this is expected behavior
        if (error && error.code !== 4) {
          Toast.fire({ 
            icon: 'error', 
            title: t('video_playback_error'), 
            timer: 3000 
          });
        }
      });

      player.value.on('fullscreenchange', () => {
        if (player.value.isFullscreen()) {
          isFullscreen.value = true;
          player.value.controls(true);
        } else {
          isFullscreen.value = false;
          player.value.controls(false);
        }
      });
    } catch (error) {
      console.error('Error initializing video player:', error);
    }
  };
  
  const togglePlayPause = () => {
    if(props.isThumbnail){
      return emit('viewVideo');
    }
    if (!player.value) {
      return;
    }
    if (playing.value) {
      player.value.pause();
    } else {
      player.value.play();
    }
  };

  const edit = () => {
    showSaveModal.value = true;
  }

  const destroy = () => {
    Swal.fire({
        title: t('are_you_sure'),
        text: `${t('crud_messages.delete_confirm', { model: props.clip.title })}`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: t('yes_delete'),
        cancelButtonText: t('cancel'),
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                let response = await clipStore.destroy(props.clip.id);
                if (response.success) {
                    Toast.fire({ icon: 'success', title: response.message, timer: 3000 })
                    emit('onDestroy');
                } else {
                    Toast.fire({ icon: 'error', title: response.message ?? t('crud_messages.delete_error', { model: 'clip' }), timer: 3000 });
                }
            } catch (e) {
                console.error(e);
                Toast.fire({ icon: 'error', title: t('crud_messages.delete_error', { model: 'clip' }), timer: 3000 });
            }
        }
    });
  }

  const copyLink = () => {
    let baseUrl = window.location.origin;
    let clipLink = `${baseUrl}/c/clip/${props.clip.slug}`;
    navigator.clipboard.writeText(clipLink).then(() => {
      Toast.fire({ icon: 'success', title: t('link_copied'), timer: 3000 });
    }).catch(err => {
      console.error(err);
      Toast.fire({ icon: 'error', title: t('link_copy_failed'), timer: 3000 });
    });
  };

  // Watch for changes in videoUrl and reinitialize player
  watch(() => props.videoUrl, (newUrl, oldUrl) => {
    if (newUrl !== oldUrl) {
      nextTick(() => {
        initializePlayer();
      });
    }
  });
  
  onMounted(() => {
    nextTick(() => {
      initializePlayer();
    });
  });
  
  onBeforeUnmount(() => {
    // Dispose player
    if (player.value) {
      try {
        player.value.dispose();
      } catch (error) {
        console.warn('Error disposing player on unmount:', error);
      }
    }
    
    // Clean up created object URL
    if (createdObjectUrl.value) {
      try {
        URL.revokeObjectURL(createdObjectUrl.value);
      } catch (error) {
        console.warn('Error revoking object URL on unmount:', error);
      }
    }
  });
  
  </script>
  
  <style scoped>
  .video-js {
    width: 100%;
    height: auto;
  }
  </style>