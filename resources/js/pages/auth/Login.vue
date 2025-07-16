<template>
    <GuestLayout>
        <Login/>
    </GuestLayout>
</template>
<script setup>
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useAuthStore } from '@/stores/auth';
import { useRouter, useRoute } from 'vue-router';
import GuestLayout from '@/layouts/GuestLayout.vue';
import Login from '@/components/auth/Login.vue';

const { t } = useI18n();
const authStore = useAuthStore();
const router = useRouter();
const route = useRoute();

</script>