<template>
    <div class="relative flex justify-between items-center bg-yellow-100 shadow-brutal rounded-md p-5">
        <h3 class="text-2xl font-semibold">{{ room.name }}</h3>
        <button @click="joinMyStudio(room.slug)" class="btn btn-theme btn-sm uppercase">{{ t('join') }}</button>
       
        <div class="dropdown  dropdown-hover absolute top-2 left-2">
            <i tabindex="0" role="button" class="fas fa-ellipsis-v text-lg  w-5 cursor-pointer"></i>
            <ul tabindex="0" class="dropdown-content z-[1] w-52 shadow-brutal bg-yellow-200 border border-black p-2 rounded-md">
                <li class="hover:bg-yellow-300 p-1 cursor-pointer" @click="deleteRoom">
                    <i class="fas fa-trash-alt text-red-500"></i>
                    {{ t('delete') }}
                </li>
                <li class="hover:bg-yellow-300 p-1 cursor-pointer" @click="configureRoom">
                    <i class="fas fa-cog text-blue-500"></i>
                    {{ t('configure') }}
                </li>
            </ul>
        </div>
    </div>
</template>
<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import CryptoJS from 'crypto-js';
import { useRouter } from 'vue-router';
import Cookies from 'universal-cookie';
import { useAuthStore } from '@/stores/auth';
import { useStudioRoomStore } from '@/stores/studio/room';
import Swal from 'sweetalert2';

const { t } = useI18n();
const router = useRouter();
const authStore = useAuthStore();
const studioRoomStore = useStudioRoomStore();

const emit = defineEmits(['deleted']);

const props = defineProps({
    room: {
        type: Object,
        required: true
    }
});

const joinMyStudio = (slug) => {
    const cookies = new Cookies();
    let studioRooms = cookies.get("studioRooms") || {};
    studioRooms[slug] = {
        room: slug,
        name: authStore.user.name,
        isOwner: true
    };
    cookies.set("studioRooms", studioRooms, { path: "/" });
    return router.push({ name: 'studio.room', params: { room: slug } });
}

const deleteRoom = () => {
    Swal.fire({
        title: t('are_you_sure'),
        text: `${t('crud_messages.delete_confirm', { model: props.room.name })}`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: t('yes_delete'),
        cancelButtonText: t('cancel'),
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                let response = await studioRoomStore.destroy(props.room.id);
                if (response.success) {
                    Toast.fire({ icon: 'success', title: response.message, timer: 3000 })
                    emit('deleted', room.id);
                } else {
                    Toast.fire({ icon: 'error', title: response.message ?? t('crud_messages.delete_error', { model: props.room.name }) });
                }
            } catch (e) {
                console.error(e);
                Toast.fire({ icon: 'error', title: t('crud_messages.delete_error', { model: props.room.name }) });
            }
        }
    });
}
const configureRoom = () => {

    const encryptedRoomId = CryptoJS.AES.encrypt(props.room.id.toString(), 'secret').toString();
    router.push({ 
        name: 'configuration', 
        query: { roomId: encryptedRoomId } 
    });
};


</script>