<template>
    <div class="w-full p-5 shadow-brutal rounded-xl h-full overflow-hidden">
        <div class="flex w-full h-full items-center justify-center cursor-pointer transition-all  relative"
            :class="{ '-translate-y-full': show, 'translate-y-0': !show }" @click="show = true">
            <slot name="header"></slot>
        </div>
        <div class="flex flex-col justify-center w-full transition-all h-full"
            :class="{ '-translate-y-full': show, 'translate-y-0': !show }">
            <slot name="content" :hide="hide"></slot>
        </div>
    </div>
</template>
<script setup>
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n();

const show = ref(false);

const hide = () => {
    show.value = false;
}

</script>