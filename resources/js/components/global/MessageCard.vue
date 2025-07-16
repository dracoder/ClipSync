<template>
    <div class="w-full p-5 shadow-brutal rounded-xl h-full overflow-hidden bg-yellow-200">
            <slot name="header"></slot>
        <div class="flex flex-col gap-2 bg-yellow-100 rounded-lg items-center justify-center w-full my-4">
            <slot name="content"></slot>
        </div>
    </div>
</template>
<script setup>
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n();

</script>