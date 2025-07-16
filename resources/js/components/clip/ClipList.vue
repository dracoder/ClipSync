<template>
    <div class="flex px-4 mt-10 items-center justify-between w-full">
        <div class="mx-auto w-full">
            <div class="w-full flex justify-center items-center mb-12">
                <button class="btn btn-theme btn-lg px-10 clip-text mx-2">
                    {{ t('clip.create') }}
                </button>
            </div>
            <div class="container mx-auto h-[60vh] overflow-auto">
                <div class=" flex flex-col justify-center items-center w-full my-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4 gap-5 w-full px-4">
                        <div v-for="(item, index) in 10" :key="index">
                            <VideoPlayerCard :videoUrl="'https://archive.org/download/BigBuckBunny_124/Content/big_buck_bunny_720p_surround.mp4'">
                                <template #header>
                                    <h3 class="clip-text text-center text-md md:text-lg">
                                        <b>Test Video</b>
                                    </h3>
                                </template>
                                <template #content>
                                    <p class="text-xs md:text-sm clip-text text-center px-2 lg:px-4 py-6">
                                        Test Content
                                    </p>
                                </template>
                            </VideoPlayerCard>
                        </div>
                    </div>
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

const { t } = useI18n();
const router = useRouter();
const authStore = useAuthStore();

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

const getList = async () => {

}

onMounted(() => {
    getList();
});

</script>