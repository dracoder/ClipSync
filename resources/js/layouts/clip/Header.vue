<template>
    <div class="fixed py-2 flex items-center justify-between px-2 w-full">
        <div class="flex md:hidden">
            <button @click="toggleSidebar" class="btn btn-circle btn-sm btn-theme rounded dark:text-white">
                <label class="swap swap-rotate " :class="{'swap-active':sidebar}">
                    <i class="swap-on fas fa-arrow-left text-xl"></i>
                    <i class="swap-off fas fa-bars text-xl"></i>
                </label>
            </button>
        </div>
        <div class="flex pr-5 rounded-b-2xl pt-2 -mt-2 cursor-pointer group" @click="goHome">
            <img :src="'/img/clipsync.svg'" alt="ClipSync Logo" class="h-14 w-14 transition-all group-hover:scale-125" />
            <div class="flex flex-col items-end justify-center -ml-3">
                <h1 class="text-4xl font-bold clip-text">CLIPSYNC</h1>
                <h1 class="text-2xl font-bold clip-text -mt-2">{{ t('clips') }}</h1>
            </div>
        </div>
        <div class="flex flex-row h-full items-center gap-2 md:px-4">
            <LanguageSwitcher image-class="w-5 h-3 md:w-9 md:h-5 " />
            <button v-if="authStore.isAuthenticated" @click="logout" class="pr-2 md:px-4 flex items-center h-full group">
                <span class="hidden md:flex text font-semibold uppercase mr-1">{{ t('logout') }}</span>
                <i class="iconoir-log-out text-2xl transition-all group-hover:translate-x-1 "></i>
            </button>
        </div>
    </div>
</template>
<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useAuthStore } from '@/stores/auth';

const { t } = useI18n();
const authStore = useAuthStore();

const emit = defineEmits(['toggle-sidebar']);

const props = defineProps({
    sidebar: Boolean
});

const toggleSidebar = () => {
    emit('toggle-sidebar');
}

const logout = async () => {
    try {
        await authStore.logout();
    } catch (e) {
        console.error(e);
    }
    window.location.href = "/login";
}

const goHome = () => {
    window.location.href = "/c/home";
}

</script>