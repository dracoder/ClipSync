<template>
    <div class="w-full p-5 shadow-brutal rounded-xl h-full overflow-hidden">
        <div class="flex w-full h-full items-center justify-center relative">
            <slot name="header"></slot>
        </div>
        <div class="flex flex-col justify-center w-full h-full">
            <slot name="content"></slot>
        </div>
    </div>
</template>
<script setup>
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n();

</script>