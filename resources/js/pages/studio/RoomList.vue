<template>
    <div class="flex px-4 mt-6 items-center justify-between w-full">
        <div class="mx-auto w-full">
           <!--  <div v-if="rooms.length" class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <button @click="showNewStudioModal" class="btn btn-theme uppercase">{{ t('new_studio') }}</button>
                </div>
                <div class="flex justify-end items-center">
                    <form @submit.prevent="joinStudio">
                        <div class="relative shadow-brutal">
                            <input type="text" class="input-theme p-1 pl-2 pr-14 rounded-none"
                                :placeholder="t('enter_studio')" v-model="joinStudioName" required>
                            <button @click="joinStudio"
                                class="absolute right-[2px] top-[2px] h-[90%] w-auto min-w-12 flex justify-center items-center bg-yellow-400 font-semibold hover:bg-yellow-500 transition-all uppercase border-l-2 border-black px-1">
                                {{ t('join') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div> -->
            <div class="container mx-auto">
                <div class=" flex flex-col md:flex-row justify-between items-center w-full h-full">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 w-full md:px-10">
                        <div class="w-full">
                            <CreateCard :save="true" packageType="free"/>
                        </div>
                        <div>
                            <JoinCard />
                        </div>
                    </div>
                </div>
            </div>
            <div v-if="rooms.length" class="container mx-auto mt-10">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                    <div>
                        <h2 class="text-3xl font-semibold mb-6">{{ t('my_studios') }}</h2>
                    </div>
                    <div class="text-right">
                        <div class="relative">
                            <input type="text" class="input-theme p-1 pl-2 pr-14 rounded-lg shadow-brutal" :placeholder="t('search')" v-model="searchRoom" required>
                            <i class="absolute right-2 top-[0.6rem] text-xl iconoir-search"></i>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-5 mt-5">
                    <template v-if="filteredRooms.length">
                        <div v-for="room in filteredRooms" :key="room.id">
                           <RoomCard :room="room" @deleted="roomDeleted" />
                        </div>
                    </template>
                    <template v-else>
                        <div class="text-center w-full md:col-span-2 xl:col-span-3 2xl:col-span-4">
                            <h3 class="text-2xl font-semibold">{{ t('no_studio_found') }}</h3>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
    <dialog class="modal" :class="{ 'modal-open': newStudioModal.show }">
        <div class="modal-box theme-dialog">
            <button class="absolute right-5 top-5 text-xl font-bold" @click="hideNewStudioModal">✕</button>
            <div class="modal-header">
                <h3 class="modal-title font-semibold uppercase">{{ t('new_studio') }}</h3>
            </div>
            <div class="modal-body">
                <div class="grid grid-cols-1 gap-5 mt-5">
                    <div>
                        <input type="text" id="name" v-model="newStudioModal.name"
                            class="input-theme w-full p-1 rounded-md px-2" :placeholder="t('studio_name')">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <div class="flex justify-end items-center w-full gap-4 mt-5">
                    <button class="btn btn-theme btn-sm" @click="hideNewStudioModal">{{ t('cancel') }}</button>
                    <button class="btn btn-theme btn-sm btn-primary" @click="hideNewStudioModal">{{ t('build') }}</button>
                </div>
            </div>
        </div>
    </dialog>
</template>
<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router';
import CreateCard from '@/components/studio/CreateCard.vue';
import JoinCard from '@/components/studio/JoinCard.vue';
import { useStudioRoomStore } from '@/stores/studio/room';
import { useAuthStore } from '@/stores/auth';
import RoomCard from '@/components/studio/list/RoomCard.vue';

const { t } = useI18n();
const router = useRouter();
const studioRoomStore = useStudioRoomStore();
const authStore = useAuthStore();

const newStudioModal = ref({
    show: false,
    name: ''
});
const rooms = ref([]);
const pagination = ref({
    current_page: 1,
    last_page: 0,
    per_page: -1,
    total_pages: 0,
    total: 0,
    from: 0,
    to: 0
});
const joinStudioName = ref('');
const searchRoom = ref('');

const filteredRooms = computed(() => {
    return rooms.value.filter(room => room.name.toLowerCase().includes(searchRoom.value.toLowerCase()));
});

const showNewStudioModal = () => {
    newStudioModal.value.show = true;
}

const hideNewStudioModal = () => {
    newStudioModal.value.show = false;
}

const joinStudio = () => {
    if (joinStudioName.value) {
        return router.push({ name: 'studio.room', params: { room: joinStudioName.value } });
    }
}
const getList = async () => {
    try{
        const response = await studioRoomStore.getList({
            per_page: -1
        });
        rooms.value = response.data.rooms.data;
    } catch (error) {
        console.error(error);
        Toast.fire({ icon: 'error', title: t('something_went_wrong') });
    }
}

const roomDeleted = (id) => {
    rooms.value = rooms.value.filter(room => room.id !== id);
}
onMounted(() => {    
    getList();
});

</script>