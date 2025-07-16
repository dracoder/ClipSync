<template>
     <div class="comments-list flex-grow my-4">
        <h3 class="text-lg mb-4 text-gray-800 clip-text">{{ t('clip.comments') }}</h3>
        <div 
            class="flex flex-col gap-2 w-full overflow-y-auto max-h-80 custom-scrollbar" 
            ref="commentsList"
            @scroll="handleScroll"
        >
            <div v-for="(comment, index) in displayedComments" :key="`comment-${comment.id}-${refreshKey}`" class="flex flex-row gap-1 justify-between bg-gray-100 px-4 py-3 rounded-md text-wrap shadow-sm transition duration-200 hover:bg-gray-200 border border-gray-200">
               <div class="flex flex-col gap-1 justify-start items-start w-full">
                    <p class="text-gray-700 text-sm"><strong class="text-sm clip-text pr-2">{{ comment.user_name }}:</strong> {{ comment.message }}</p>
                    <!-- <i v-if="comment.video" class="iconoir-play text-md text-black cursor-pointer p-2 border border-black shadow-brutal bg-yellow-200 rounded-md" @click="viewVideo(comment.video)"></i> -->
                    <div v-if="comment.video" class="flex flex-row gap-1 justify-start items-start w-52 h-full">
                        <VideoPlayerCard :key="`video-${comment.id}-${refreshKey}`" :video-url="comment.video" :is-thumbnail="true" @viewVideo="viewVideo(comment.video)"/>
                    </div>
               </div>
                
                <div class="flex flex-row gap-1 justify-end items-center">
                    <span v-if="comment.privacy == 'private'" class="text-sm text-blue-500" @click.stop="displayPrivacyInfo">
                        <i class="iconoir-warning-circle-solid"></i>
                    </span>
                    <span @click.stop="destroy(comment)" class="cursor-pointer" v-if="authStore.isAuthenticated && (comment.user_id == authStore.user.id || comment.clip_user_id == authStore.user.id)">
                        <i class="text-sm iconoir-trash-solid text-error"></i>
                    </span>
                </div>
            </div>
            
            <!-- Infinite scroll loading indicator -->
            <div v-if="loadingMore && hasMoreComments" class="flex justify-center items-center py-4">
                <div class="flex items-center gap-2 text-gray-600">
                    <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-current"></div>
                    <span class="text-sm clip-text">{{ t('loading') }}...</span>
                </div>
            </div>
            
            <!-- Manual Load More Button (fallback) -->
            <div v-if="hasMoreComments && !autoLoadEnabled" class="flex flex-col items-center mt-4 gap-2">
                <button 
                    class="btn btn-theme btn-sm px-6 py-2 clip-text transition-all duration-200 hover:scale-105" 
                    @click="loadMoreComments"
                    :disabled="loadingMore"
                >
                    <span v-if="loadingMore" class="flex items-center gap-2">
                        <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-current"></div>
                        {{ t('loading') }}...
                    </span>
                    <span v-else class="flex items-center gap-2">
                        <i class="iconoir-arrow-down text-sm"></i>
                        {{ t('view_more_comments') }}
                    </span>
                </button>
                
                <!-- Comments counter -->
                <div class="text-xs text-gray-600 clip-text">
                    {{ t('showing_comments', { shown: comments.length, total: totalComments }) }}
                </div>
            </div>
            
            <!-- Comments counter for infinite scroll -->
            <div v-if="autoLoadEnabled && comments.length > 0 && hasMoreComments" class="flex justify-center mt-2">
                <div class="text-xs text-gray-600 clip-text">
                    {{ t('showing_comments', { shown: comments.length, total: totalComments }) }}
                </div>
            </div>
            
            <!-- No more comments indicator -->
            <div v-if="!hasMoreComments && comments.length > 0 && totalComments > perPage" class="flex justify-center mt-4">
                <div class="text-xs text-gray-500 clip-text px-4 py-2 bg-gray-100 rounded-md">
                    {{ t('all_comments_loaded') }}
                </div>
            </div>
        </div>
        <div class="comment-form" :class="comments && comments.length ? 'mt-6' : ''">
            <!-- Infinite Scroll Toggle -->
            <!-- <div v-if="comments.length > 0" class="flex justify-center mb-4">
                <button 
                    @click="toggleAutoLoad"
                    class="btn btn-sm px-4 py-2 text-xs clip-text transition-all duration-200"
                    :class="autoLoadEnabled ? 'btn-theme' : 'btn-theme btn-warning'"
                >
                    <i class="iconoir-refresh-double text-sm mr-1"></i>
                    {{ autoLoadEnabled ? t('disable_auto_load') : t('enable_auto_load') }}
                </button>
            </div> -->
            
            <!-- <div class="p-3 rounded-md shadow-lg w-full mb-2 transition duration-300 hover:shadow-xl clip-text">
                <span class="text-sm font-medium text-gray-800">
                {{ t('name') + ': ' + authStore.user.name }}
                </span>
            </div> -->
            <!-- <i class="text-sm iconoir-trash-solid text-error flex justify-end "></i> -->
            <!-- <div class="flex justify-end mb-2">
                <i v-if="!videoBlob" class="iconoir-video-camera text-md text-black cursor-pointer p-1 border border-black shadow-brutal bg-theme rounded-md"  @click="recordClip"></i>
                <div v-else class="flex justify-center items-center gap-2">                 
                    <span class="text-xs text-gray-800 clip-text">{{ t('clip.recorded_response') }}</span>
                    <i class="iconoir-play text-md text-black cursor-pointer p-1 border border-black shadow-brutal bg-theme rounded-md" @click="viewVideo(videoBlob)"></i>
                    <i class="iconoir-trash-solid text-md text-error cursor-pointer p-1 border border-black shadow-brutal bg-theme rounded-md" @click="deleteRecordedVideo"></i>
                </div>
            </div> -->
            <div class="grid grid-cols-2 gap-2 mt-2 items-center text-center">
                <div 
                    class="shadow-brutal rounded-md px-4 py-3 clip-text mx-2 my-4 border cursor-pointer hover:border-white hover:bg-black hover:text-white"
                    :class="commentType !== 'message' ? 'bg-white border-black' : 'border-white bg-black text-white'"
                    @click="setReplyType('comment')"
                    >
                    {{ t('send_a_message') }}
                </div>
                <div 
                    class="shadow-brutal rounded-md px-4 py-3 clip-text mx-2 my-4 border hover:border-white hover:bg-black hover:text-white cursor-pointer"
                    :class="commentType !== 'clip' ? 'bg-white border-black' : 'border-white bg-black text-white'"
                                          @click="setReplyType('clip')"
                    >
                                          {{ t('send_a_clip') }}
                </div>
            </div>

            <textarea
                v-if="commentType == 'message'"
                :placeholder="t('clip.your_comment')"
                class="input-theme shadow px-2 border border-gray-300 p-2 rounded-md w-full mb-2 text-sm focus:outline-none focus:ring-2"
                :class="v$.message.error && submitted ? 'focus:ring-red-500' : 'focus:ring-yellow-500'"
                v-model="form.message"
                rows="2"
            ></textarea>           
                            <!-- <ValidationMessage key="message_error" :modelValue="v$.message" :label="t('clip.comment')" 
                                :show="v$.message.error && submitted" /> -->
            <div v-if="commentType == 'message'" class="flex w-full justify-between items-end gap-2 mt-2 flex-wrap"> 
                <div class="flex flex-col gap-2 justify-start items-start">
                    <label class="text-xs clip-text" for="privacy">{{ t('privacy') }}</label>
                    <select v-model="form.privacy" class="input-theme shadow-brutal px-2 border border-gray-300 py-1 rounded-md focus:outline-none focus:ring-2" style="font-size: 0.9rem;">
                        <option value="public">{{ t('public') }}</option>
                        <option value="private">{{ t('private') }}</option>
                    </select>
                </div>
                <button type="button" class="btn btn-theme btn-sm" @click="addComment">{{ t('clip.add_comment') }}</button> 
            </div>
        </div>
        <div v-if="showLoginModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-[1000]">
            <Login :is-modal="true" @close="close" />
        </div>
        <div v-if="showRecordModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-[1000]">
            <RecordClipNew :is-modal="true" :module-type="'clip_comment'" :clip-id="clipId" @close="close" @recordCompleted="recordCompleted" />
        </div>
        <div v-if="showVideoModal.show" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-[1000]">
            <div class="flex flex-row gap-1 justify-center items-center w-[95vw] lg:w-[80vw] xl:w-[70vw] 2xl:w-[60vw] h-auto px-4">
                <VideoPlayerCard :video-url="showVideoModal.video" :is-modal="true" @close="close" />
            </div>
        </div>
        <!-- <div v-if="showPreviewModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-[1000]">
            <div class="bg-yellow-200 p-6 rounded-lg w-[90vw] lg:w-[70vw] xl:w-[60vw] 2xl:w-[50vw] flex flex-col gap-6">
                <h3 class="text-lg mb-4 text-gray-800 clip-text">{{ t('clip.preview_recorded_video') }}</h3>
                
                <VideoPlayerCard :video-url="videoBlob" @close="closePreviewModal" />
                
                
                <div class="flex flex-col gap-2">
                    <textarea
                        v-model="form.message"
                        :placeholder="t('clip.your_comment')"
                        class="input-theme shadow px-2 border border-gray-300 p-2 rounded-md w-full mb-4 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-500"
                        rows="2"
                    ></textarea>

                    <div class="flex w-full justify-between items-end gap-2 flex-wrap"> 
                        <div class="flex flex-col gap-2 justify-start items-start">
                            <label class="text-xs clip-text" for="privacy">{{ t('privacy') }}</label>
                            <select v-model="form.privacy" class="input-theme shadow-brutal px-2 border border-gray-300 py-1 rounded-md focus:outline-none focus:ring-2" style="font-size: 0.9rem;">
                                <option value="public">{{ t('public') }}</option>
                                <option value="private">{{ t('private') }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <button 
                        class="btn btn-theme btn-sm" 
                        @click="submitRecordedVideo"
                    >
                        {{ t('clip.add_comment') }}
                    </button>
                    <button 
                        class="btn btn-theme btn-error btn-sm" 
                        @click="deleteRecordedVideo"
                    >
                        {{ t('cancel') }}
                    </button>
                </div>
            </div>
        </div> -->
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { useI18n } from 'vue-i18n';
import { useVuelidate } from "@vuelidate/core";
import { useRouter } from 'vue-router';
import { required } from "@vuelidate/validators";
import { useAuthStore } from '@/stores/auth';
import { useCommentStore } from '@/stores/clip/comment';
import Login from '@/components/auth/Login.vue';
import RecordClipNew from '@/components/clip/RecordClipNew.vue';
import VideoPlayerCard from '@/components/clip/VideoPlayerCard.vue';

const { t } = useI18n();
const router = useRouter();
const authStore = useAuthStore();
const commentStore = useCommentStore();
const comments = ref([]);
const modulePlural = ref('clip.comments');
const module =  ref('clip.comment');
const submitted = ref(false);
const commentsList = ref(null);
const showLoginModal = ref(false);
const showRecordModal = ref(false);
const commentType = ref('');
const videoBlob = ref(null);
const showPreviewModal = ref(false);
const previewMessage = ref('');
const showVideoModal = ref({
    show: false,
    video: null
});

// Pagination and refresh state
const refreshKey = ref(0);
const currentPage = ref(1);
const perPage = ref(5); // Show 5 comments initially
const loadingMore = ref(false);
const hasMoreComments = ref(false);
const totalComments = ref(0);

// Infinite scroll state
const autoLoadEnabled = ref(true); // Enable infinite scroll by default
const scrollThreshold = ref(50); // Pixels from bottom to trigger load
const isScrolling = ref(false);
let scrollTimeout = null;

const props = defineProps({
    clipId: Number
});

const emit = defineEmits(['close', 'save', 'scrollDown']);

const form = ref({
    message: '',
    privacy: 'public',
});

const rules = computed(() => ({
     message: { required },
     privacy: { },
}));

const v$ = useVuelidate(rules, form);

const loading = ref(false);

// Show all loaded comments (no slicing needed)
const displayedComments = computed(() => {
    return comments.value;
});

const addComment = async () => {
    v$.value.$touch();
    submitted.value = true;
    if (v$.value.$error) {
        return;
    }
    if(!authStore.isAuthenticated) {
        showLoginModal.value = true;
        return Toast.fire({ icon: 'info', title: t('auth_to_comment'), timer: 3000 });
    }
    submitted.value = false;
    loading.value = true;
    try {
        let data = new FormData();
        data.append('user_id', authStore.user.id);
        data.append('message', form.value.message ?? '');
        data.append('privacy', form.value.privacy ?? 'public');
        data.append('clip_id', props.clipId);
        if (videoBlob.value) {
            data.append('video', videoBlob.value);
        }
        const response = await commentStore.store(data);
        if (response.success) {
            resetForm();
            Toast.fire({ icon: 'success', title: t('crud_messages.save_success', { model: t(module.value) }), timer: 3000 });
            if (commentsList.value) {
                commentsList.value.scrollTop = 0;
            }
            // Refresh comments and reset pagination
            await refreshComments();
        } else {
            if (response.errors) {
                for (let key in response.errors) {
                    v$.value['content'].$serverError = response.errors[key];
                    v$.value['content'].error = true;
                }
                submitted.value = true;
            } else {
                Toast.fire({ icon: 'error', title: t('crud_messages.save_error', { model: t(module.value) }), timer: 3000 });
            }
        }
    } catch (e) {
        console.error(e);
        Toast.fire({ icon: 'error', title: t('crud_messages.save_error', { model: t(module.value) }), timer: 3000 });
    } finally {
        loading.value = false;
        emit('commentAdded');
    }
};

const getList = async (page = 1, append = false) => {
    try {
        if (!append) {
            loading.value = true;
        } else {
            loadingMore.value = true;
        }
        
        let params = {
            page: page,
            per_page: perPage.value, // Proper pagination - get only perPage items per request
        };
        
        console.log('Loading comments - Page:', page, 'Per Page:', perPage.value, 'Append:', append);
        
        const response = await commentStore.getList(props.clipId, params);
        if (response.success) {
            const newComments = response.data && response.data.comments ? response.data.comments.data : [];
            const pagination = response.data && response.data.comments && response.data.comments.pagination ? response.data.comments.pagination : {};
            
            totalComments.value = pagination.total || 0;
            
            console.log('Received comments:', newComments.length, 'Total:', totalComments.value);
            console.log('Pagination data:', pagination);
            
            if (append) {
                // For "load more", append new comments to existing ones
                comments.value = [...comments.value, ...newComments];
                console.log('Appended comments. Total loaded:', comments.value.length);
            } else {
                // For initial load or refresh, replace the list
                comments.value = newComments;
                currentPage.value = 1;
                console.log('Initial load. Comments loaded:', comments.value.length);
            }
            
            // Check if there are more comments to load
            hasMoreComments.value = comments.value.length < totalComments.value;
            
            console.log('Has more comments:', hasMoreComments.value, 'Loaded:', comments.value.length, 'Total:', totalComments.value);
            
            // Force Vue to re-render video components
            refreshKey.value++;
        } else {
            let message = response.message || t('crud_messages.retrive_error', { model: t(modulePlural.value)});
            Toast.fire({icon: 'error', title: message, timer: 3000});
        }
    } catch (error) {
        console.error('Error loading comments:', error);
        Toast.fire({icon: 'error', title: t('crud_messages.retrive_error', { model: t(modulePlural.value) }), timer: 3000});
    } finally {
        loading.value = false;
        loadingMore.value = false;
    }
}

const loadMoreComments = async () => {
    if (loadingMore.value || !hasMoreComments.value) {
        return;
    }
    
    console.log('Loading more comments...');
    currentPage.value++;
    await getList(currentPage.value, true);
};

const refreshComments = async () => {
    console.log('Refreshing comments...');
    currentPage.value = 1;
    await getList(1, false);
};

// Toggle between infinite scroll and manual load more
const toggleAutoLoad = () => {
    autoLoadEnabled.value = !autoLoadEnabled.value;
    console.log('Auto-load toggled:', autoLoadEnabled.value);
};

// Cleanup function for scroll timeout
const cleanupScrollTimeout = () => {
    if (scrollTimeout) {
        clearTimeout(scrollTimeout);
        scrollTimeout = null;
    }
};

const destroy = (record) => {
    Swal.fire({
        title: t('are_you_sure'),
        text: `${t('crud_messages.delete_confirm', { model: t('this_comment') })}`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: t('yes_delete'),
        cancelButtonText: t('cancel'),
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                let response = await commentStore.destroy(record.id);
                if (response.success) {
                    Toast.fire({ icon: 'success', title: response.message, timer: 3000 })
                    // Refresh comments after deletion
                    await refreshComments();
                } else {
                    Toast.fire({ icon: 'error', title: response.message ?? t('crud_messages.delete_error', { model: t('this_comment') }), timer: 3000 });
                }
            } catch (e) {
                console.error(e);
                Toast.fire({ icon: 'error', title: t('crud_messages.delete_error', { model: t('this_comment') }), timer: 3000 });
            }
        }
    });
  }

  const displayPrivacyInfo = () => {
            Toast.fire({ icon: 'info', title: t('clip.comment_privacy_info'), timer: 3000 });
  }

  const recordClip = () => {
    showRecordModal.value = true;
  }

  const recordCompleted = async (video) => {
    console.log('Record completed in ClipComments, video:', video);
    showRecordModal.value = false;
    
    if (video === null) {
      await refreshComments();
      emit('commentAdded');
    } else {
      videoBlob.value = video;
      showPreviewModal.value = true;
    }
  }

  const deleteRecordedVideo = () => {
    Swal.fire({
        title: t('are_you_sure'),
                    text: `${t('crud_messages.delete_confirm', { model: t('clip.recorded_response') })}`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: t('yes_delete'),
        cancelButtonText: t('cancel'),
    }).then(async (result) => {
        if (result.isConfirmed) {
            videoBlob.value = null;
            resetForm();
        }
    });
  }

  const viewVideo = (video = null) => {
    showVideoModal.value = {
        show: true,
        video: video ?? videoBlob.value
    }
  }
  
  const close = () => {
    showVideoModal.value = {
        show: false,
        video: null
    };
    showRecordModal.value = false;
    showLoginModal.value = false;
    commentType.value = '';
    showPreviewModal.value = false;
  }

  const setReplyType = (val) => {
    if(val == 'clip'){
        commentType.value = 'clip';
        recordClip();
    } else {
        commentType.value = 'message';
    }
  }

  const resetForm = () => {
    form.value = {
        message: '',
        privacy: 'public',
    }
    commentType.value = '';
    videoBlob.value = null;
    showPreviewModal.value = false;
  }

  const submitRecordedVideo = async () => {
    // Use the existing addComment function to submit the video comment
    await addComment();
  }

  const closePreviewModal = () => {
    showPreviewModal.value = false;
    videoBlob.value = null;
  }

  // Throttled scroll handler for infinite scroll
  const handleScroll = () => {
    if (!autoLoadEnabled.value || loadingMore.value || !hasMoreComments.value) {
        return;
    }
    
    const container = commentsList.value;
    if (!container) return;
    
    // Clear existing timeout
    if (scrollTimeout) {
        clearTimeout(scrollTimeout);
    }
    
    // Throttle scroll events
    scrollTimeout = setTimeout(() => {
        const scrollTop = container.scrollTop;
        const scrollHeight = container.scrollHeight;
        const clientHeight = container.clientHeight;
        
        // Calculate distance from bottom
        const distanceFromBottom = scrollHeight - (scrollTop + clientHeight);
        
        console.log('Scroll event:', {
            scrollTop,
            scrollHeight,
            clientHeight,
            distanceFromBottom,
            threshold: scrollThreshold.value
        });
        
        // Load more when near bottom
        if (distanceFromBottom <= scrollThreshold.value) {
            console.log('Triggering infinite scroll load...');
            loadMoreComments();
        }
    }, 100); // 100ms throttle
  }

onMounted(() => {
    getList();
});

onBeforeUnmount(() => {
    cleanupScrollTimeout();
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
</style>