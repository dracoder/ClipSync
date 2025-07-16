<template>
    <SliderCard class="bg-yellow-200 ">
        <template #header>
            <h3 class="text-4xl lg:text-6xl clip-text">{{ t('create_studio_card.title') }}</h3>
        </template>
        <template #content="{ hide }">
            <form @submit.prevent="createRoom">
                <div class="grid grid-cols-1 gap-6 mt-5">
                    <div class="flex justify-between items-center w-full">
                        <h3 class="text-2xl clip-text">{{ t('create_studio_card.title') }}</h3>
                    </div>
                    <div>
                        <input type="text" v-model="form.room" class="input-theme w-full p-1 rounded-md shadow px-2"
                            :placeholder="t('studio_name')">
                        <ValidationMessage key="room_error" :modelValue="v$.room" :label="t('studio_name')"
                            :show="v$.room.error" />
                    </div>
                    <div class="flex justify-end items-center w-full gap-4 ">
                        <button type="button" class="btn btn-theme btn-sm" :disabled="loading" @click="hide">{{
                            t('cancel') }}</button>
                        <button class="btn btn-theme btn-primary btn-sm" :disabled="loading" @click="createRoom">{{
                            t('create') }}</button>
                    </div>
                </div>
            </form>
        </template>
    </SliderCard>
</template>
<script setup>
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router';
import { useVuelidate } from "@vuelidate/core"
import { required } from "@vuelidate/validators";
import Cookies from 'universal-cookie';
import { useAuthStore } from '@/stores/auth';
import { useStudioRoomStore } from '@/stores/studio/room';


const { t } = useI18n();
const router = useRouter();
const authStore = useAuthStore();
const studioRoomStore = useStudioRoomStore();

const props = defineProps({
    save: {
        type: Boolean,
        required: false,
        default: false
    }
});


const form = ref({
    room: ''
});
const loading = ref(false);

const rules = computed(() => ({
    room: { required },
}));


const v$ = useVuelidate(rules, form);

const save = async () => {
    loading.value = true;
    try {
        let data = {
            name: form.value.room,
        }
        const response = await studioRoomStore.store(data);
        if (response.success) {
            return true;
        }else{
            if (response.errors) {
                for (let key in response.errors) {
                    let error = response.errors[key];
                    v$.value['room'].$serverError = response.errors[key]
                    v$.value['room'].error = true
                }
            }else{
                Toast.fire({ icon: 'error', title: t('crud_messages.save_error', { model: "studio" }) });
            }
        }
    } catch (e) {
        console.error(e);
        Toast.fire({ icon: 'error', title: t('crud_messages.save_error', { model: "studio" }) });
    } finally {
        loading.value = false;
    }
    return false;
}

const createRoom = async () => {
    v$.value.$touch();
    if (v$.value.$error) {
        return;
    }
    if (props.save) {
        let saved = await save();
        if (!saved) {
            // Toast.fire({ icon: 'error', title: t('crud_messages.save_error', { model: "studio" }) });
            return;
        }
    }

    const cookies = new Cookies();
    let studioRooms = cookies.get("studioRooms") || {};
    studioRooms[form.value.room] = {
        room: form.value.room,
        name: authStore.user.name,
        isOwner: true
    };
    cookies.set("studioRooms", studioRooms, { path: "/" });
    return router.push({ name: 'studio.room',  params: { room: form.value.room, free: true } });

}


</script>