<template>
  <div v-if="loading" class="flex justify-center items-center h-64">
        <ProgressSpinner />
    </div>
    <div v-else class="flex px-4 mt-10 items-center justify-between w-full h-full" ref="viewClipContainer">
        <div class="mx-auto w-full">
            <!-- <div class="w-full flex justify-center items-center mb-6">
                <button class="btn btn-theme btn-lg px-10 clip-text mx-2" @click="recordClip">
                    {{ t('clip.create') }}
                </button>
            </div> -->
            <div v-if="clip && clip.id" class="flex flex-col 2xl:flex-row gap-6 md:gap-3 items-center 2xl:items-start justify-center w-full h-full">
               <div class="w-[90vw] md:w-[60vw] px-4">
                  <VideoPlayerCard :video-url="clip.video" :clip="clip" @onDestroy="onDestroy" @onSaved="getBySlug">
                    <template #header>
                      <h3 class="clip-text text-center text-md md:text-lg text-wrap">
                        <b>{{ clip.title }}</b>
                      </h3>
                    </template>
                    <template #content>
                      <p class="text-2xs md:text-xs clip-text text-start px-2 lg:px-4 py-6 text-wrap">
                        {{ clip.content }}
                      </p>
                    </template>
                  </VideoPlayerCard>
               </div>
                                 <div v-if="clip && clip.id" class="w-[90vw] md:w-[60vw] 2xl:w-1/3 p-5 shadow-brutal rounded-xl bg-yellow-200 h-full flex flex-col lg:mb-0 mb-6">
                  <ClipComments v-if="!clip.disable_comments" :clip-id="clip.id" @commentAdded="onScrollDown"/>
                  <div v-else class="flex flex-col gap-4 p-4">
                    <p class="text-lg clip-text text-center bg-gray-100 px-4 py-3 rounded-md">{{ t('clip.comments_disabled') }}</p>
                  </div>
                </div>
            </div>
            <div v-else class="flex flex-col items-center justify-center w-full h-full mt-10">
                <p class="text-2xl clip-text">{{ t('clip.not_found') }}</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import VideoPlayerCard from '@/components/clip/VideoPlayerCard.vue';
import ClipComments from '@/components/clip/ClipComments.vue';
import { useClipStore } from '@/stores/clip/clip';
import axios from 'axios';

const { t } = useI18n();
const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();
const clipStore = useClipStore();
const viewClipContainer = ref(null);

const clipSlug = computed(() => {
    return (route.params.slug ?? null)
});
//const fakeVideo = ref('https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4');
const clip = ref({});
const loading = ref(false);

const getBySlug = async() => {
  try {
        loading.value = true;
        let response = await clipStore.getById(clipSlug.value);
        if (response.success) {
          clip.value = response.data.clip;
        } else {
            console.error(response);
            Toast.fire({ icon: 'error', title: response.message, timer: 3000 });
        }
    }
    catch (error) {
        console.error(error);
        Toast.fire({ icon: 'error', title: t('crud_messages.retrive_error', { model: t('clip') }), timer: 3000 });
    } finally {
        loading.value = false;
        trackView(clip.value.id);
    }
}

const trackView = async (clipId) => {
  try {
            await clipStore.trackView({
      clip_id: clipId,
      user_id: authStore.user ? authStore.user.id : null
    });
  } catch (error) {
    console.error(error);
  }
}

const onDestroy = () => {
      router.push({ name: 'clip.home' })
}
const onScrollDown = () => {
  const contentContainer = document.getElementById('content-container');
  if (contentContainer) {
    contentContainer.scrollTop = contentContainer.scrollHeight;
  }
}
onMounted(async() => {
  await getBySlug();
});

onBeforeUnmount(() => {

});
</script>