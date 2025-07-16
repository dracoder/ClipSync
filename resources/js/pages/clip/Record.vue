<template>
    <RecordClipNew :is-modal="false"/>
</template>

<script setup>
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useAuthStore } from '@/stores/auth';
import { useRouter, useRoute } from 'vue-router';
import RecordClipNew from '@/components/clip/RecordClipNew.vue';

const { t } = useI18n();
const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();

</script>