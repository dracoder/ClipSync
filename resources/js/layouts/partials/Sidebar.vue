<template>
    <div class="flex flex-col gap-5">
        <div v-for="menu in menus" :key="menu.name">
            <div class="flex items-center justify-between px-2 py-2 bg-yellow-300 shadow-brutal rounded-lg cursor-pointer transition-all hover:translate-x-2" :class="{ 'bg-yellow-500 border-2 border-yellow-600': menu.is_active() }"
             @click="menuClick(menu)">
                <span class="text-lg font-semibold uppercase">{{ menu.name }}</span>
                <i v-if="menu.icon" class="mr-2" :class="menu.icon"></i>
                <template v-if="menu.svg">
                    <component :is="menu.svg" class="w-8 h-8" />
                </template>
            </div>
        </div>
    </div>
</template>
<script setup>
import { ref, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import IconStudio from '@/components/icons/IconStudio.vue';
import IconClip from '@/components/icons/IconClip.vue';

const { t } = useI18n();
const route = useRoute();
const router = useRouter();

const menus = computed(() => [
    {
        name: t('studio'),
        svg: IconStudio,
        route: { name: 'studio.list' },
        is_active: () => {
            return route.name === 'studio.list' || route.name === 'studio.room';
        }
    },
    {
        name: t('clips'),
        svg: IconClip,
        route: { name: 'clip.home' },
        is_active: () => {
            return route.name.includes('clip.');
        }
    },
    {
        name: t('profile'),
        icon: 'iconoir-user-badge-check text-2xl',
        route: { name: 'profile' },
        is_active: () => {
            return route.name === 'profile';
        }
    },

]);

const menuClick = (menu) => {
    if (menu.route) {
        router.push(menu.route);
    } else if (menu.action) {
        menu.action();
    }
}

</script>   