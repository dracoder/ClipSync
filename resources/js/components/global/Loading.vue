<template>
    <div class="flex w-full h-full items-center justify-center">
        <div class="flex flex-col items-center justify-center">
            <div class="flex items-center justify-center relative">
                <div class="absolute animate-spin rounded-full h-32 w-32 border-t-2 border-b-2 border-gray-900">
                </div>
                <div class="absolute h-36 w-36 rounded-full flex items-center justify-center">    
                    <img :src="'/img/clipsync.svg'" alt="ClipSync Logo" class="h-24 w-24 animate-pulse"/>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup>
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
</script>