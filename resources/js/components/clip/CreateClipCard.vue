<template>
    <NormalCard class="bg-yellow-200 max-w-lg w-full mx-auto">
        <template #header>
            <h3 class="text-4xl lg:text-4xl clip-text">{{ editMode ? t('clip.edit') : t('clip.create') }}</h3>
        </template>
        <template #content="{ hide }">
            <form @submit.prevent="addClip">
                <div class="grid grid-cols-1 gap-6 mt-5">
                    <div>
                        <input type="text" v-model="form.title" class="input-theme w-full p-1 rounded-md shadow px-2"
                            :placeholder="t('clip.title_placeholder')" :disabled="loading"/>
                        <ValidationMessage key="title_error" :modelValue="v$.title" :label="t('clip.title')" 
                            :show="v$.title.error" />
                    </div>
                    <div>
                        <textarea v-model="form.content" class="input-theme w-full p-1 rounded-md shadow px-2"
                            :placeholder="t('clip.content_placeholder')" :disabled="loading"></textarea>
                        <ValidationMessage key="content_error" :modelValue="v$.content" :label="t('clip.content')" 
                            :show="v$.content.error" />
                    </div>
                    <div class="flex flex-col justify-start items-center w-full gap-2">
                        <div class="flex justify-start items-center w-full gap-2">
                            <label class="text-sm clip-text" for="comments">{{ t('clip.disable_comments') }}</label>
                            <input class="form-check-input" type="checkbox" v-model="form.disable_comments">
                        </div>
                        <div class="flex justify-start items-center w-full gap-2">
                            <label class="text-sm clip-text" for="comments">{{ t('clip.private_comments') }}</label>
                            <input class="form-check-input" type="checkbox" v-model="form.private_comments">
                        </div>
                    </div>
                    <!-- Processing Status -->
                    <div v-if="processingStatus" class="bg-blue-100 border border-blue-300 rounded-md p-3">
                        <div class="flex items-center gap-2">
                            <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-500"></div>
                            <span class="text-sm text-blue-700">{{ processingStatus }}</span>
                        </div>
                    </div>
                    
                    <div class="flex justify-end items-center w-full gap-4">
                        <button type="button" class="btn btn-theme btn-sm" :disabled="loading" @click="close">
                            {{ t('cancel') }}
                        </button>
                        <button class="btn btn-theme btn-primary btn-sm" :disabled="loading" @click="addClip">
                            {{ loading ? t('saving') : (editMode ? t('save') : t('clip.add')) }}
                        </button>
                    </div>
                </div>
            </form>
        </template>
    </NormalCard>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { useVuelidate } from "@vuelidate/core";
import { useRouter } from 'vue-router';
import { required } from "@vuelidate/validators";
import { useAuthStore } from '@/stores/auth';
import { useClipStore } from '@/stores/clip/clip';

const { t } = useI18n();
const router = useRouter();
const authStore = useAuthStore();
const clipStore = useClipStore();

const props = defineProps({
    videoBlob: {
        type: Object,
        default: null
    },
    editMode: {
        type: Boolean,
        default: false
    },
    record: {
        type: Object,
        default: null
    }
});

const emit = defineEmits(['close', 'saved']);

const form = ref({
    title: null,
    content: null,
    disable_comments: false,
    private_comments: false
});

const rules = computed(() => ({
    title: { required },
    content: { },
    disable_comments: { },
    private_comments: { }
}));

const v$ = useVuelidate(rules, form);

const loading = ref(false);
const processingStatus = ref(null);

const close = () => {
    if(loading.value){
       return 
    }
    emit('close');
}

const addClip = async () => {
    v$.value.$touch();
    if (v$.value.$error) {
        Toast.fire({ icon: 'info', title: t('saving_in_progress'), timer: 3000 });
        return;
    }
    
    loading.value = true;
    try {
        let response;
        
        if (!props.editMode && props.videoBlob && typeof props.videoBlob === 'object' && 
            (props.videoBlob.webcam || props.videoBlob.screen)) {
            
            // Use dual stream upload API (legacy support)
            console.log('🎬 DETECTED DUAL STREAM FORMAT - Using dual stream upload API');
            console.log('🔍 Dual stream details:', {
                has_webcam: !!props.videoBlob.webcam,
                has_screen: !!props.videoBlob.screen,
                webcam_size: props.videoBlob.webcam?.size || 0,
                screen_size: props.videoBlob.screen?.size || 0,
                metadata_exists: !!props.videoBlob.metadata
            });
            response = await uploadDualStreams();
            
        } else {
            console.log('📹 Using single video upload');
            response = await uploadSingleVideo();
        }
        
        if (response.success) {
            Toast.fire({ icon: 'success', title: t('crud_messages.save_success', { model: 'clip' }), timer: 3000 });
            saved();
        } else {
            if (response.errors) {
                for (let key in response.errors) {
                    v$.value['clipTitle'].$serverError = response.errors[key];
                    v$.value['clipTitle'].error = true;
                }
            } else {
                Toast.fire({ icon: 'error', title: t('crud_messages.save_error', { model: 'clip' }), timer: 3000 });
            }
        }
    } catch (e) {
        console.error(e);
        Toast.fire({ icon: 'error', title: t('crud_messages.save_error', { model: 'clip' }), timer: 3000 });
    } finally {
        loading.value = false;
    }
};

const uploadDualStreams = async () => {
    const formData = new FormData();
    
    // Add clip data
    formData.append('title', form.value.title ?? '');
    formData.append('content', form.value.content ?? '');
    formData.append('disable_comments', form.value.disable_comments ? 1 : 0);
    formData.append('private_comments', form.value.private_comments ? 1 : 0);
    
    // Show upload progress
    processingStatus.value = t('clip.uploading_streams');
    
    // Add video streams with proper file extensions
    if (props.videoBlob.webcam) {
        // Determine file extension from MIME type
        let webcamExtension = 'mp4'; // Default
        if (props.videoBlob.webcam.type.includes('webm')) {
            webcamExtension = 'webm';
        } else if (props.videoBlob.webcam.type.includes('mp4')) {
            webcamExtension = 'mp4';
        }
        
        const webcamFile = new File([props.videoBlob.webcam], `webcam.${webcamExtension}`, {
            type: props.videoBlob.webcam.type || 'video/mp4',
            lastModified: new Date().getTime()
        });
        formData.append('webcam_video', webcamFile);
        console.log('Added webcam stream:', {
            size: webcamFile.size,
            type: webcamFile.type,
            name: webcamFile.name,
            originalType: props.videoBlob.webcam.type
        });
    }
    
    if (props.videoBlob.screen) {
        // Determine file extension from MIME type
        let screenExtension = 'mp4'; // Default
        if (props.videoBlob.screen.type.includes('webm')) {
            screenExtension = 'webm';
        } else if (props.videoBlob.screen.type.includes('mp4')) {
            screenExtension = 'mp4';
        }
        
        const screenFile = new File([props.videoBlob.screen], `screen.${screenExtension}`, {
            type: props.videoBlob.screen.type || 'video/mp4',
            lastModified: new Date().getTime()
        });
        formData.append('screen_video', screenFile);
        console.log('Added screen stream:', {
            size: screenFile.size,
            type: screenFile.type,
            name: screenFile.name,
            originalType: props.videoBlob.screen.type
        });
    }
    
    // Add metadata
    if (props.videoBlob.metadata) {
        formData.append('metadata', JSON.stringify(props.videoBlob.metadata));
        console.log('Added metadata:', props.videoBlob.metadata);
    }
    
    // Update status to merging
                processingStatus.value = t('clip.merging_streams');
    
    try {
        // Use the new dual stream upload endpoint
        const response = await clipStore.uploadStreams(formData);
                    processingStatus.value = t('clip.merge_completed');
        return response;
    } catch (error) {
        processingStatus.value = null;
        throw error;
    }
};

const uploadSingleVideo = async () => {
    const formData = new FormData();
    
    // Add clip data
    formData.append('title', form.value.title ?? '');
    formData.append('content', form.value.content ?? '');
    formData.append('disable_comments', form.value.disable_comments ? 1 : 0);
    formData.append('private_comments', form.value.private_comments ? 1 : 0);

    if (!props.editMode && props.videoBlob) {
        if (props.videoBlob instanceof File) {
            console.log('Using pre-created File object with name:', props.videoBlob.name, 'and type:', props.videoBlob.type);
            formData.append('video', props.videoBlob);
        } else {
            let extension = 'mp4';
            if (props.videoBlob.type.includes('webm')) {
                extension = 'webm';
            } else if (props.videoBlob.type.includes('mp4')) {
                extension = 'mp4';
            }
            
            const filename = `recording.${extension}`;
            const file = new File([props.videoBlob], filename, { 
                type: props.videoBlob.type,
                lastModified: new Date().getTime()
            });
            console.log('Uploading video with filename:', filename, 'and type:', props.videoBlob.type);
            formData.append('video', file);
        }
    }
    
    return props.editMode ? await clipStore.update(props.record.id, formData) : await clipStore.store(formData);
};

const saved = () => {
    emit('saved');
    if(!props.editMode){
        router.push({ name: 'clip.home' });
    } else {
        close();
    }
}

const setData = (data) => {
    form.value.title = data.title;
    form.value.content = data.content;
    form.value.disable_comments = data.disable_comments == 1 ? true : false;
    form.value.private_comments = data.private_comments == 1 ? true : false;
}

onMounted(() => {
    if(props.editMode){
        setData(props.record);
    }
});

</script>

<style scoped>
</style>