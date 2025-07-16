<template>
    <video
        id="presenter-video" data-setup='{"liveui": true}' preload="metadata" ref="video" :controls="false"
        playsInline loop autoplay muted
        class="video-js aspect-video h-full w-full"
    ></video>

    <p class="absolute top-0 left-0 text-white p-2 text-shadow text-xl">{{ name }}</p>

    <div class="absolute bottom-0 right-0 flex justify-end items-center w-full gap-2 sm:gap-5 p-2">
        <button class="btn btn-theme rounded btn-sm p-2 sm:p-0 sm:w-10 sm:h-10" :class="{'btn-error': muted, 'btn-warning': !muted }" @click="toggleMute">
            <i class="text-[0.95rem] sm:text-2xl" :class="{'iconoir-microphone-solid': !muted, 'iconoir-microphone-mute-solid': muted}"></i>
        </button>
        <button class="btn btn-theme rounded btn-sm p-2 sm:p-0 sm:w-10 sm:h-10" :class="{'btn-error': !camera, 'btn-warning': camera  }" @click="toggleCamera">
            <i class="text-[0.95rem] sm:text-2xl" :class="{'iconoir-video-camera': camera, 'iconoir-video-camera-off': !camera}"></i>
        </button>
        <button class="btn btn-theme rounded btn-sm p-2 sm:p-0 sm:w-10 sm:h-10" :class="{'btn-error': screen, 'btn-warning': !screen }" @click="shareScreen">
            <i class="text-[0.85rem] sm:text-sm fa" :class="{'fa-display': !screen, 'fa-stop': screen}"></i>
        </button>
        <button class="btn btn-theme btn-error rounded btn-sm p-2 sm:p-0 sm:w-10 sm:h-10" @click="disconnect">
            <i class="text-[0.85rem] sm:text-2xl fa fa-times"></i>
        </button>
    </div>
</template>
<script setup>
import { ref, onMounted } from 'vue';
import { useI18n } from 'vue-i18n'

const { t } = useI18n();

const emit = defineEmits(['init', 'share-screen', 'disconnect', 'mute']);

const props = defineProps({
    name: String
});

const video = ref(null);
const muted = ref(false);
const camera = ref(true);
const screen = ref(false);

const videoConstraints = {
    video: {
        width: { max: 1920 },
        height: { max: 1080 },
        frameRate: { ideal: 30 },
    },
    audio: true
};

const initUserMedia = () => {
    navigator.mediaDevices.getUserMedia(videoConstraints)
        .then(stream => {
            video.value.srcObject = stream;
            emit('init', stream);
        })
        .catch(err => {
            Toast.fire({ icon: 'error', title: t('camera_error') , timer: 10000 });
            console.error('Error:', err);

        });
};

const toggleMute = () => {
    muted.value = !muted.value;
    emit('mute', {
        type: 'audio',
        value: !muted.value
    });
    video.value.srcObject.getAudioTracks().forEach(track => {
        track.enabled = !muted.value;
    });
};

const toggleCamera = () => {
    camera.value = !camera.value;
    emit('mute', {
        type: 'video',
        value: camera.value
    });
    video.value.srcObject.getVideoTracks().forEach(track => {
        track.enabled = camera.value;
    });
};

const shareScreen = () => {
    screen.value = !screen.value;
    emit('share-screen');
};

const disconnect = () => {
    emit('disconnect');
};

const handleMute = (type , state) => {
    if(type=='video')
    {
        video.value.srcObject.getVideoTracks().forEach(track => {
            track.enabled = state;
        });
        camera.value = state;
    }else{
        video.value.srcObject.getAudioTracks().forEach(track => {
            track.enabled = state;
        });
        muted.value = !state;
    }
}

onMounted(() => {
    initUserMedia();
});

defineExpose({handleMute});
</script>
