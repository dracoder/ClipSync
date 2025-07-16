<template>
    <video
        id="my-video" data-setup='{"liveui": true}' preload="metadata" ref="video" :controls="false"
        playsInline loop autoplay
        class="video-js aspect-video h-full w-full"
    ></video>
    <p class="absolute top-0 left-0 text-white p-2 text-shadow text-xl">{{ user.name }}</p>

    <div v-if="owner" class="absolute top-0 right-0 p-2 dropdown dropdown-end dropdown-hover">
        <i tabindex="0" role="button" class="fas fa-ellipsis-v text-lg  w-5 cursor-pointer text-shadow text-white"></i>
        <ul tabindex="0" class="dropdown-content z-[1] w-52 shadow-brutal bg-stone-50 border border-black p-1 rounded-md text-sm">
            <li class="hover:bg-yellow-50 transition-all  p-1 cursor-pointer flex items-center gap-1" @click="adminRestriction('audio')">
                <template v-if="user.audio">
                    <i class="iconoir-microphone-mute-solid text-red-500 text-lg"></i> {{ t('turn_off_mic') }}
                </template>
                <template v-else>
                    <i class="iconoir-microphone-mute-solid text-gray-700 text-lg"></i>
                    <span class="text-gray-700">{{ t('turned_off_mic') }}</span>
                </template>
            </li>
            <li class="hover:bg-yellow-50 transition-all  p-1 cursor-pointer flex items-center gap-1" @click="adminRestriction('video')">
                <template v-if="user.video">
                    <i class="iconoir-video-camera-off text-red-500 text-lg"></i> {{ t('turn_off_camera') }}
                </template>
                <template v-else>
                    <i class="iconoir-video-camera-off text-gray-700 text-lg"></i>
                    <span class="text-gray-700">{{ t('turned_off_camera') }}</span>
                </template>
            </li>
            <li class="hover:bg-yellow-50 transition-all  p-1 cursor-pointer flex items-center gap-1" @click="adminKickOut">
                <i class="iconoir-xmark text-red-500 text-xl"></i> {{ t('kick_out') }}
            </li>
        </ul>
    </div>
    <div  class="absolute bottom-0 right-0 flex justify-start items-center w-full gap-5 p-2">
        <div class="flex justify-end items-center w-full gap-5">
            <i class="text-2xl video-icon" :class="{'iconoir-microphone-solid': !muted, 'iconoir-microphone-mute-solid': muted}"></i>
            <i class="text-2xl video-icon" :class="{'iconoir-video-camera': camera, 'iconoir-video-camera-off': !camera}"></i>
        </div>
    </div>
</template>
<script setup>
import { ref, onMounted, computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const emit = defineEmits(['mute-user','admin-kick-out']);
const props = defineProps({
    participant: {
        type: Object,
        required: true
    },
    owner: {
        type: Boolean,
        required: true
    }
});

const video = ref(null);
const muted = computed(() => {
    if(user.value && user.value.audio) {
        return false;
    }
    return true;
})
const camera = computed(() => {
    if(user.value && user.value.video) {
        return true;
    }
    return false;
})

const adminRestriction = (type) => {
    emit('mute-user', props.participant, type);
}

const adminKickOut = (type) => {
    emit('admin-kick-out', props.participant);
}
const user = ref({});

onMounted(() => {
    user.value = props.participant.participant;
    video.value.srcObject = props.participant.stream;
});


</script>

<style lang="scss" scoped>
.video-icon {
    @apply text-white;
    text-shadow: 2px 2px 2px rgba(0, 0, 0, 0.5);
}
</style>
