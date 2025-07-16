<template>
    <NormalCard class="bg-yellow-200 max-w-lg w-full mx-auto">
        <template #header>
            <h3 class="text-4xl lg:text-4xl clip-text">{{ t('clip.add_comment') }}</h3>
        </template>
        <template #content="{ hide }">
                <div class="grid grid-cols-1 gap-6 mt-5">
                    <!-- Video Preview -->
                    <div v-if="videoBlob && videoUrl" class="w-full">
                        <h4 class="text-lg clip-text mb-2">{{ t('clip.preview_recorded_video') }}</h4>
                        <div class="w-full" style="max-height: 300px;" @click.stop>
                            <VideoPlayerCard 
                                :video-url="videoUrl" 
                                :is-thumbnail="false"
                                custom-style="max-height: 300px;"
                                @click.stop
                            />
                        </div>
                    </div>
                    
                    <!-- Comment Message -->
                    <div>
                        <textarea 
                            v-model="form.message" 
                            class="input-theme w-full p-3 rounded-md shadow px-2"
                            :placeholder="t('clip.your_comment')" 
                            :disabled="loading"
                            rows="3"
                        ></textarea>
                        <ValidationMessage 
                            key="message_error" 
                            :modelValue="v$.message" 
                            :label="t('clip.comment')" 
                            :show="v$.message.error" 
                        />
                    </div>
                    
                    <!-- Privacy Setting -->
                    <div class="flex flex-col gap-2 justify-start items-start">
                        <label class="text-sm clip-text" for="privacy">{{ t('privacy') }}</label>
                        <select 
                            v-model="form.privacy" 
                            class="input-theme shadow-brutal px-2 border border-gray-300 py-2 rounded-md focus:outline-none focus:ring-2"
                            :disabled="loading"
                        >
                            <option value="public">{{ t('public') }}</option>
                            <option value="private">{{ t('private') }}</option>
                        </select>
                    </div>
                    
                    <!-- Processing Status -->
                    <div v-if="processingStatus" class="bg-blue-100 border border-blue-300 rounded-md p-3">
                        <div class="flex items-center gap-2">
                            <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-500"></div>
                            <span class="text-sm text-blue-700">{{ processingStatus }}</span>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="flex justify-end items-center w-full gap-4">
                        <button type="button" class="btn btn-theme btn-sm" :disabled="loading" @click="close">
                            {{ t('cancel') }}
                        </button>
                        <button class="btn btn-theme btn-primary btn-sm" :disabled="loading" @click="addComment">
                            {{ loading ? t('saving') : t('clip.add_comment') }}
                        </button>
                    </div>
                </div>
        </template>
    </NormalCard>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { useVuelidate } from "@vuelidate/core";
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useCommentStore } from '@/stores/clip/comment';
import NormalCard from '@/components/global/NormalCard.vue';
import ValidationMessage from '@/components/global/ValidationMessage.vue';
import VideoPlayerCard from '@/components/clip/VideoPlayerCard.vue';

const { t } = useI18n();
const router = useRouter();
const authStore = useAuthStore();
const commentStore = useCommentStore();

const props = defineProps({
    videoBlob: {
        type: Object,
        default: null
    },
    clipId: {
        type: Number,
        required: true
    }
});

const emit = defineEmits(['close', 'saved']);

const form = ref({
    message: '',
    privacy: 'public'
});

const rules = computed(() => ({
    message: { },
    privacy: { }
}));

const v$ = useVuelidate(rules, form);

const loading = ref(false);
const processingStatus = ref(null);
const videoUrl = ref(null);

// Create video URL for preview
const createVideoUrl = () => {
    if (props.videoBlob && !videoUrl.value) {
        videoUrl.value = URL.createObjectURL(props.videoBlob);
        console.log('Created video URL for preview:', videoUrl.value);
    }
};

const cleanupVideoUrl = () => {
    if (videoUrl.value) {
        try {
            URL.revokeObjectURL(videoUrl.value);
            console.log('Cleaned up video URL:', videoUrl.value);
        } catch (error) {
            console.warn('Error cleaning up video URL:', error);
        } finally {
            videoUrl.value = null;
        }
    }
};

const close = () => {
    if (loading.value) {
        return;
    }
    
    cleanupVideoUrl();
    emit('close');
};

const addComment = async () => {
    v$.value.$touch();
    
    if (v$.value.$error) {
        return;
    }
    
    loading.value = true;
    processingStatus.value = t('saving_in_progress');
    
    try {
        const formData = new FormData();
        
        // Add comment data
        formData.append('user_id', authStore.user.id);
        formData.append('clip_id', props.clipId);
        formData.append('message', form.value.message || '');
        formData.append('privacy', form.value.privacy || 'public');
        
        // Add video if present
        if (props.videoBlob) {
            // Determine file extension from MIME type
            let extension = 'mp4'; // Default
            if (props.videoBlob.type.includes('webm')) {
                extension = 'webm';
            } else if (props.videoBlob.type.includes('mp4')) {
                extension = 'mp4';
            }
            
            const videoFile = new File([props.videoBlob], `comment-video.${extension}`, {
                type: props.videoBlob.type || 'video/mp4',
                lastModified: new Date().getTime()
            });
            
            formData.append('video', videoFile);
            console.log('Added video to comment:', {
                size: videoFile.size,
                type: videoFile.type,
                name: videoFile.name
            });
        }
        
        const response = await commentStore.store(formData);
        
        if (response.success) {
            Toast.fire({ 
                icon: 'success', 
                                    title: t('crud_messages.save_success', { model: t('clip.comment') }), 
                timer: 3000 
            });
            
            // Emit saved event first, then close
            emit('saved');
            
            // Close the modal after a short delay to ensure the saved event is processed
            setTimeout(() => {
                close();
            }, 100);
            
        } else {
            if (response.errors) {
                for (let key in response.errors) {
                    if (v$.value[key]) {
                        v$.value[key].$serverError = response.errors[key];
                        v$.value[key].error = true;
                    }
                }
            } else {
                Toast.fire({ 
                    icon: 'error', 
                    title: t('crud_messages.save_error', { model: t('clip.comment') }), 
                    timer: 3000 
                });
            }
        }
    } catch (e) {
        console.error('Error saving comment:', e);
        Toast.fire({ 
            icon: 'error', 
                                title: t('crud_messages.save_error', { model: t('clip.comment') }), 
            timer: 3000 
        });
    } finally {
        loading.value = false;
        processingStatus.value = null;
    }
};

onMounted(() => {
    // Create video URL for preview
    createVideoUrl();
});

onUnmounted(() => {
    // Ensure cleanup happens even if component is destroyed unexpectedly
    cleanupVideoUrl();
});
</script>

<style scoped>
</style> 