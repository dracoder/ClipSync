<template>
    <div class="relative w-full h-full">
        <video ref="video" class="video-js aspect-video h-full w-full" :class="{'fixed top-0 left-0 z-50 bg-black w-screen h-screen': expanded}" data-setup='{"liveui": true}' preload="metadata" autoplay muted :controls="false"></video>
        <div class="absolute top-0 left-0 p-2 z-10" :class="{'fixed z-[51]': expanded}">
            <div v-if="!expanded" class="bg-black bg-opacity-70 text-white px-3 py-1 rounded-md text-shadow">
                <span class="text-sm font-medium">{{ t('sharing_screen') }}: </span>
                <span class="text-lg font-bold">{{ label }}</span>
            </div>
        </div>
        <div :class="{'fixed bottom-4 right-4 z-[51]': expanded, 'absolute bottom-0 right-0 p-4': !expanded}">
            <button class="btn btn-theme rounded btn-sm p-0 h-10 w-10" :class="{'btn-error': expanded, 'btn-warning': !expanded}" @click="toggleExpand">
                <i class="text-2xl" :class="{'fa fa-expand': !expanded, 'fa fa-compress': expanded}"></i>
            </button>
        </div>
    </div>
</template>
<script setup>
import { ref, onMounted, watch, onBeforeUnmount } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const emit = defineEmits(['expand', 'screen-ended']);
const props = defineProps({
    screen: {
        type: Object,
        required: true
    },
    expanded: {
        type: Boolean,
        default: false
    }
});

const video = ref(null);
const label = ref('');

onMounted(() => {
    if (props.screen && props.screen.stream) {
        video.value.srcObject = props.screen.stream;
        label.value = props.screen.name || props.screen.participant?.name || 'Unknown';
        
        // Handle stream ending from browser UI
        const videoTrack = props.screen.stream.getVideoTracks()[0];
        if (videoTrack) {
            videoTrack.onended = () => {
                console.log('Screen sharing ended for:', label.value);
                handleScreenEnd();
            };
        }
        
        // Also listen for when all tracks end
        props.screen.stream.getTracks().forEach(track => {
            track.onended = () => {
                console.log('Track ended for:', label.value);
                handleScreenEnd();
            };
        });
    }
});

const handleScreenEnd = () => {
    console.log('SharedScreen: handleScreenEnd called for:', label.value);
    
    // First collapse if expanded
    if (props.expanded) {
        emit('expand', false);
    }
    
    // Clear the video source
    if (video.value) {
        video.value.srcObject = null;
    }
    
    // Emit a custom event to notify parent that sharing has ended
    emit('screen-ended', props.screen);
};

onBeforeUnmount(() => {
    if (props.expanded) {
        emit('expand', false);
    }
    // Clean up video stream
    if (video.value && video.value.srcObject) {
        video.value.srcObject.getTracks().forEach(track => track.stop());
        video.value.srcObject = null;
    }
});

const toggleExpand = () => {
    emit('expand', !props.expanded);
};

watch(() => props.screen.stream, (newStream) => {
    if (video.value && newStream) {
        video.value.srcObject = newStream;
        label.value = props.screen.name || props.screen.participant?.name || 'Unknown';
    }
}, { immediate: true });

watch(() => props.screen.name, (newName) => {
    if (newName) {
        label.value = newName;
    }
}, { immediate: true });

</script>
