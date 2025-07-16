<template>
    <SliderCard class="bg-yellow-100">
        <template #header>
            <h3 class="text-4xl lg:text-6xl clip-text text-center">
                <span v-if="props.room">
                    {{ t('join_studio_card.title_name', {name : props.room}) }}
                </span>
                <span v-else>
                {{ t('join_studio_card.title') }}
                </span>
            </h3>
        </template>
        <template #content="{ hide }">
            <form @submit.prevent="joinRoom">
                <div class="grid grid-cols-1 gap-6 mt-5">
                    <div class="flex justify-between items-center w-full">
                        <h3 class="text-2xl clip-text">
                            <span v-if="props.room">
                                {{ t('join_studio_card.title_name', {name : props.room}) }}
                            </span>
                            <span v-else>
                                {{ t('join_studio_card.title') }}
                            </span>
                        </h3>
                    </div>
                    <div>
                        <input type="text" v-model="form.name" class="input-theme w-full p-1 rounded-md shadow px-2" :placeholder="t('your_name')">
                        <ValidationMessage key="name_error" :modelValue="v$.name" :label="t('your_name')" :show="v$.name.error" />
                    </div>
                    <div v-if="!props.room">
                        <input type="text" v-model="form.room" class="input-theme w-full p-1 rounded-md shadow px-2" :placeholder="t('studio_name')" :disabled="props.room!=''">
                        <ValidationMessage key="room_error" :modelValue="v$.room" :label="t('studio_name')" :show="v$.room.error" />
                    </div>
                    <div class="flex justify-end items-center w-full gap-4 ">
                        <button type="button" class="btn btn-theme btn-sm" @click="hide">{{ t('cancel') }}</button>
                        <button class="btn btn-theme btn-primary btn-sm" @click="joinRoom">{{ t('join') }}</button>
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


const { t } = useI18n();
const router = useRouter();

const emit = defineEmits(['joined']);
const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    room: {
        type: String,
        default: ''
    }
});

const form = ref({
    name: '',
    room: props.room,
});


const rules = computed(() => ({
    name: { required },
    room: { required },
}));


const v$ = useVuelidate(rules, form);

const joinRoom = () => {
    v$.value.$touch();
    if (v$.value.$error) {
        return;
    }

    const cookies = new Cookies();
    let studioRooms = cookies.get("studioRooms") || {};
    studioRooms[form.value.room] = {
        name: form.value.name,
        room: form.value.room,
    };
    cookies.set("studioRooms", studioRooms, { path: "/" });
    emit('joined');
    return router.push({ name: 'studio.room', params: { room: form.value.room } });

}


</script>