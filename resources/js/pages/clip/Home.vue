<template>
    <div v-if="loading" class="flex h-screen">
        <Loading />
    </div>
    <div class="flex px-4 mt-10 items-center justify-between w-full" v-else>
        <div class="mx-auto w-full">
            <div v-if="filteredClips && filteredClips.length" class="w-full flex justify-end items-center mb-8">
                <button class="btn btn-theme btn-md px-10 clip-text mx-2" @click="recordClip">
                    {{ t('clip.create') }}
                </button>
            </div>
            <div class="container mx-auto h-[60vh] overflow-auto custom-scrollbar">
                <div v-if="filteredClips && filteredClips.length" class=" flex flex-col justify-center items-center w-full my-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4 gap-5 w-full px-4">
                        <div v-for="item in filteredClips" :key="item.id" class="cursor-pointer" @click="viewClip(item)">
                            <VideoPlayerCard :video-url="item.video" :clip="item" @onDestroy="getList" @onSaved="getList()">
                                <template #header>
                                    <h3 v-if="item.title" class="clip-text text-center text-md md:text-lg">
                                        <b>{{ item.title }}</b>
                                    </h3>
                                </template>
                                <!-- <template #content>
                                    <p v-if="item.content" class="text-xs md:text-sm clip-text text-center px-2 lg:px-4 py-6">
                                        {{ item.content }}
                                    </p>
                                </template> -->
                            </VideoPlayerCard>
                        </div>
                    </div>
                    <!-- <div v-else class="text-center w-full md:col-span-2 xl:col-span-3 2xl:col-span-4">
                        <h3 class="text-2xl font-semibold">{{ t('clip.list_empty') }}</h3>
                    </div> -->
                </div>

                <div v-else class=" flex flex-col justify-center items-center w-full h-full gap-6 max-w-4xl mx-auto">
                    <div class="grid grid-cols-1 gap-5 w-full px-2 lg:px-10">
                        <MessageCard>
                            <template #header>
                            <h3 class="clip-text text-center text-xl md:text-3xl">
                                <b>{{ t('clip.home_card_header') }}</b>
                            </h3>
                            </template>
                            
                            <template #content>
                                <p class="text-sm md:text-lg clip-text text-center px-2 lg:px-4 py-8"
                                v-html="t('clip.home_card_content')"
                                >
                                </p>
                            </template>
                        </MessageCard>
                    </div>

                    <button class="btn btn-theme btn-lg px-10 clip-text mx-2" @click="recordClip">
                        <i class="fas fa-plus text-lg font-bold"></i>
                        {{ t('clip.create_first') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import VideoPlayerCard from '@/components/clip/VideoPlayerCard.vue';
import { useClipStore } from '@/stores/clip/clip';
import MessageCard from '@/components/global/MessageCard.vue';

const clipStore = useClipStore();
const { t } = useI18n();
const router = useRouter();
const authStore = useAuthStore();
const loading = ref(false);
//const fakeVideo = ref('https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4');
const clips = ref([]);
const pagination = ref({
    current_page: 1,
    last_page: 0,
    per_page: -1,
    total_pages: 0,
    total: 0,
    from: 0,
    to: 0
});

const searchClip = ref('');

const filteredClips = computed(() => {
    return clips.value.filter(clip => clip && clip.video && clip.title.toLowerCase().includes(searchClip.value.toLowerCase()));
});

const getList = async () => {
    try {
        loading.value = true;
        let params = {
            per_page: -1,
        };
        const response = await clipStore.getList(params);
        if (response.success) {
            clips.value = response.data.clips.data;
            console.log(clips.value);
        } else {
            let message = response.message || t('crud_messages.retrive_error', { model: t('clips')});
            Toast.fire({icon: 'error', title: message, timer: 3000});

        }
    } catch (error) {
        console.error(error);
        Toast.fire({icon: 'error', title: t('crud_messages.retrive_error', { model: t('clips') }), timer: 3000});
    } finally {
        loading.value = false;
    }
}

const recordClip = () => {
    router.push({ name: 'clip.record' })
}

const viewClip = (item = null) => {
    router.push({ name: 'clip.view', params: { slug: item.slug }})
    //router.push({ name: 'clip.view' })
}

onMounted(() => {
    getList();
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

</style>