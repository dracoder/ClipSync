<template>
    <div class="card flex justify-center">
        <div v-if="selectedLanguage" ref="dropdown"  class="dropdown" :class="{ 'dropdown-open': open }" >
            <div class="border-2 border-black p-1 rounded-md bg-white" @click="toggleDropdown">
                <img :src="'/img/flags/' + selectedLanguage.value + '.svg'" :alt="selectedLanguage.name" :class="imageClass" />
            </div>
            <ul  class="dropdown-content bg-white border-2 border-black p-1 flex flex-col gap-1 w-full rounded-md mt-1 z-10">
                <li v-for="(lang, index) in languages" :key="index">
                    <a @click="changeLanguage(lang)" class="cursor-pointer">
                        <img :src="'/img/flags/' + lang.value + '.svg'" :alt="lang.name" class="w-8 border-2 border-black" />
                    </a>
                </li>
            </ul>
        </div>
        <div ref="dropdown" class="hidden"></div>
    </div>
</template>
<script setup>
import { ref, onMounted, computed, nextTick } from 'vue'
import { useI18n } from 'vue-i18n'
import { onClickOutside } from '@vueuse/core'
import Cookies from 'universal-cookie';

const { t, locale, availableLocales } = useI18n();

const cookies = new Cookies();
const languages = ref([]);
const selectedLanguage = ref(null);
const dropdown = ref(null);
const open = ref(false);

onClickOutside(dropdown, event => {
    open.value = false;
})

const props = defineProps({
    imageClass: {
        type: String,
        default: 'w-8 h-5'
    }
});

const initialLocale = () => {
    nextTick(() => {
        let lang = locale.value || localStorage.getItem("lang");
        if (lang == null || lang == undefined) {
            lang = 'en';
        }
        
        selectedLanguage.value = { name: lang.toUpperCase(), value: lang.toLowerCase() };
    })
}


const changeLanguage = async (langauge) => {
    let lang = langauge.value;
    localStorage.setItem("lang", lang);
    locale.value = lang
    cookies.set('locale', lang, { path: '/' });
    selectedLanguage.value = { name: lang.toUpperCase(), value: lang.toLowerCase() };
    open.value = false;
}

const toggleDropdown = () => {
    open.value = !open.value;
}

onMounted(() => {
    initialLocale();

    availableLocales.forEach(lang => {
        languages.value.push({
            name: lang.trim().toUpperCase(),
            value: lang.trim().toLowerCase()
        });
    });
})


</script>
<style scoped></style>
